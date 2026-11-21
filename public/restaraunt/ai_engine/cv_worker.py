import asyncio
import json
import os
import time
import threading
import requests
import cv2
import numpy as np
import websockets
from ultralytics import YOLO

# --- НАСТРОЙКИ ---
# Укажи имя видеофайла в папке ai_engine (например, "restaurant.mp4") или 0 для камеры
VIDEO_FILE = os.path.join(os.path.dirname(__file__), "restaurant.mp4")
VIDEO_SOURCE = VIDEO_FILE if os.path.exists(VIDEO_FILE) else 0

PHP_API_URL = "http://project.local/public/restaraunt/api/update_status.php"
WS_PORT = 8765

# Классы COCO в YOLO11:
PERSON_CLASS = 0
TABLE_CLASS = 60                          # dining table (автопоиск столов)
DISH_CLASSES = {39, 40, 41, 42, 43, 44, 45}  # bottle, wine glass, cup, fork, knife, spoon, bowl
PERSONAL_ITEM_CLASSES = {24, 26, 28, 67}  # backpack, handbag, suitcase, cell phone

SIT_CONFIRM_SEC = 3.0
WAITER_MIN_SEC = 2.0
LEAVE_CONFIRM_SEC = 3.0


def send_status_to_php(table_id, status, guests_count=0, wait_seconds=0):
    def _post():
        try:
            requests.post(PHP_API_URL, json={
                "table_id": table_id,
                "status": status,
                "guests_count": guests_count,
                "wait_seconds": int(wait_seconds)
            }, timeout=2.0)
        except Exception:
            pass
    threading.Thread(target=_post, daemon=True).start()


class TableStateMachine:
    def __init__(self, table_id, polygon):
        self.id = table_id
        self.polygon = np.array(polygon, dtype=np.int32)
        self.status = "FREE"
        self.wait_timer = 0.0
        self.last_update = time.time()
        self.person_enter_time = None
        self.empty_since_time = None
        self.waiter_enter_time = None
        self.base_guests_count = 0

    def _change_status(self, new_status, guests_count=0):
        if self.status != new_status:
            print(f"[AI EVENT] Стол #{self.id}: {self.status} -> {new_status} (Гостей: {guests_count})")
            self.status = new_status
            send_status_to_php(self.id, new_status, guests_count, self.wait_timer)

    def update(self, num_persons, num_dishes, num_items):
        now = time.time()
        dt = now - self.last_update
        self.last_update = now

        if self.status == "FREE":
            if num_persons > 0:
                if self.person_enter_time is None:
                    self.person_enter_time = now
                elif now - self.person_enter_time >= SIT_CONFIRM_SEC:
                    self.wait_timer = 0.0
                    self.base_guests_count = num_persons
                    self.person_enter_time = None
                    self._change_status("WAITING", num_persons)
            else:
                self.person_enter_time = None

        elif self.status == "WAITING":
            self.wait_timer += dt
            if num_persons == 0:
                if self.empty_since_time is None:
                    self.empty_since_time = now
                elif now - self.empty_since_time >= LEAVE_CONFIRM_SEC:
                    self.wait_timer = 0.0
                    self.empty_since_time = None
                    if num_items > 0:
                        self._change_status("AWAY", 0)
                    elif num_dishes > 0:
                        self._change_status("DIRTY", 0)
                    else:
                        self._change_status("FREE", 0)
            else:
                self.empty_since_time = None
                if num_persons > self.base_guests_count:
                    if self.waiter_enter_time is None:
                        self.waiter_enter_time = now
                    elif now - self.waiter_enter_time >= WAITER_MIN_SEC:
                        self.wait_timer = 0.0
                        self.waiter_enter_time = None
                        self._change_status("SERVED", self.base_guests_count)
                else:
                    self.waiter_enter_time = None

        elif self.status == "SERVED":
            if num_persons == 0:
                if self.empty_since_time is None:
                    self.empty_since_time = now
                elif now - self.empty_since_time >= LEAVE_CONFIRM_SEC:
                    self.empty_since_time = None
                    if num_items > 0:
                        self._change_status("AWAY", 0)
                    elif num_dishes > 0:
                        self._change_status("DIRTY", 0)
                    else:
                        self._change_status("FREE", 0)
            else:
                self.empty_since_time = None
                self.base_guests_count = num_persons

        elif self.status == "AWAY":
            if num_persons > 0:
                self.empty_since_time = None
                self._change_status("SERVED", num_persons)
            elif num_items == 0:
                if self.empty_since_time is None:
                    self.empty_since_time = now
                elif now - self.empty_since_time >= LEAVE_CONFIRM_SEC:
                    self.empty_since_time = None
                    self._change_status("DIRTY" if num_dishes > 0 else "FREE", 0)

        elif self.status == "DIRTY":
            if num_persons == 0 and num_dishes == 0 and num_items == 0:
                if self.empty_since_time is None:
                    self.empty_since_time = now
                elif now - self.empty_since_time >= 2.0:
                    self.empty_since_time = None
                    self._change_status("FREE", 0)
            else:
                self.empty_since_time = None

        return {
            "id": self.id,
            "status": self.status,
            "wait_sec": round(self.wait_timer, 1),
            "guests": num_persons
        }


def auto_detect_table_zones(model, frame):
    """Автоматически находит столы на кадре через YOLO11 без ручных кликов."""
    results = model(frame, verbose=False, conf=0.25)[0]
    zones = []
    if results.boxes is not None:
        for box in results.boxes:
            if int(box.cls[0]) == TABLE_CLASS:
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                # Расширяем зону стола на 25%, чтобы захватывать сидящих вокруг людей
                pad_x = int((x2 - x1) * 0.25)
                pad_y = int((y2 - y1) * 0.25)
                poly = [
                    [max(0, x1 - pad_x), max(0, y1 - pad_y)],
                    [min(frame.shape[1], x2 + pad_x), max(0, y1 - pad_y)],
                    [min(frame.shape[1], x2 + pad_x), min(frame.shape[0], y2 + pad_y)],
                    [max(0, x1 - pad_x), min(frame.shape[0], y2 + pad_y)]
                ]
                zones.append(poly)

    # Если на первом кадре столы перекрыты людьми, делим кадр на 4 логические зоны зала
    if not zones:
        h, w = frame.shape[:2]
        zones = [
            [[int(w*0.05), int(h*0.1)], [int(w*0.48), int(h*0.1)], [int(w*0.48), int(h*0.48)], [int(w*0.05), int(h*0.48)]],
            [[int(w*0.52), int(h*0.1)], [int(w*0.95), int(h*0.1)], [int(w*0.95), int(h*0.48)], [int(w*0.52), int(h*0.48)]],
            [[int(w*0.05), int(h*0.52)], [int(w*0.48), int(h*0.52)], [int(w*0.48), int(h*0.92)], [int(w*0.05), int(h*0.92)]],
            [[int(w*0.52), int(h*0.52)], [int(w*0.95), int(h*0.52)], [int(w*0.95), int(h*0.92)], [int(w*0.52), int(h*0.92)]],
        ]
    print(f"[AI AUTO-CALIBRATION] Автоматически настроено зон столов: {len(zones)}")
    return zones


connected_clients = set()


async def ws_handler(websocket):
    connected_clients.add(websocket)
    try:
        await websocket.wait_closed()
    finally:
        connected_clients.remove(websocket)


async def cv_loop():
    model = YOLO("yolo11n.pt")
    cap = cv2.VideoCapture(VIDEO_SOURCE)
    ret, first_frame = cap.read()
    if not ret:
        print("[ERROR] Видеоисточник не найден!")
        return

    # Автоматическая разметка без кликов мышкой!
    auto_zones = auto_detect_table_zones(model, first_frame)
    tables = [TableStateMachine(i + 1, poly) for i, poly in enumerate(auto_zones[:8])]

    colors = {
        "FREE": (34, 197, 94),
        "WAITING": (68, 68, 239),
        "SERVED": (246, 130, 59),
        "AWAY": (247, 85, 168),
        "DIRTY": (8, 179, 234),
    }

    prev_time = time.time()

    while cap.isOpened():
        ret, frame = cap.read()
        if not ret:
            cap.set(cv2.CAP_PROP_POS_FRAMES, 0)
            continue

        results = model.track(frame, persist=True, verbose=False, conf=0.35)[0]
        persons, dishes, items = [], [], []

        if results.boxes is not None:
            for box in results.boxes:
                cls_id = int(box.cls[0])
                conf = float(box.conf[0])
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                name = model.names[cls_id]

                if cls_id == PERSON_CLASS:
                    cx, cy = (x1 + x2) // 2, int(y1 + (y2 - y1) * 0.65)
                    persons.append((cx, cy))
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (255, 140, 0), 2)
                    cv2.putText(frame, f"{name} {conf:.2f}", (x1, y1 - 6), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 180, 0), 1)
                elif cls_id in DISH_CLASSES:
                    cx, cy = (x1 + x2) // 2, (y1 + y2) // 2
                    dishes.append((cx, cy))
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 230, 255), 1)
                elif cls_id in PERSONAL_ITEM_CLASSES:
                    cx, cy = (x1 + x2) // 2, (y1 + y2) // 2
                    items.append((cx, cy))
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (230, 80, 180), 2)

        tables_payload = []
        for table in tables:
            p_in = sum(1 for p in persons if cv2.pointPolygonTest(table.polygon, p, False) >= 0)
            d_in = sum(1 for d in dishes if cv2.pointPolygonTest(table.polygon, d, False) >= 0)
            i_in = sum(1 for i in items if cv2.pointPolygonTest(table.polygon, i, False) >= 0)

            tables_payload.append(table.update(p_in, d_in, i_in))

            color = colors[table.status]
            cv2.polylines(frame, [table.polygon], True, color, 2)
            label = f"Table #{table.id}: {table.status}"
            if table.status == "WAITING":
                label += f" ({int(table.wait_timer)}s)"
            cv2.putText(frame, label, (table.polygon[0][0] + 6, table.polygon[0][1] + 22), cv2.FONT_HERSHEY_SIMPLEX, 0.55, color, 2)

        now = time.time()
        fps = round(1.0 / max(now - prev_time, 1e-5), 1)
        prev_time = now
        cv2.putText(frame, f"VisionTable AI Enterprise | FPS: {fps}", (15, 28), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 120), 2)

        if connected_clients:
            msg = json.dumps({"fps": fps, "tables": tables_payload})
            await asyncio.gather(*(c.send(msg) for c in connected_clients), return_exceptions=True)

        cv2.imshow("VisionTable AI - Live Stream", frame)
        if cv2.waitKey(1) & 0xFF == 27:
            break

        await asyncio.sleep(0.01)

    cap.release()
    cv2.destroyAllWindows()


async def main():
    async with websockets.serve(ws_handler, "localhost", WS_PORT):
        print(f"[START] ИИ-сервер запущен (ws://localhost:{WS_PORT})")
        await cv_loop()


if __name__ == "__main__":
    asyncio.run(main())