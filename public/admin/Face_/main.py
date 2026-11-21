import cv2
import requests
import threading
import time
import queue
import json
import pathlib
from PIL import Image, ImageDraw, ImageFont
import numpy as np

def load_config(config_path):
    with open(config_path, 'r', encoding='utf-8') as f:
        return json.load(f)

cpath = str(pathlib.Path(__file__).parent.resolve()) + "\\"
config_file = cpath + 'config.json'
config = load_config(config_file)

# Очередь для передачи данных между потоками
response_queue = queue.Queue()
waiting_for_frame = False

# Функция для рисования текста с использованием Pillow (поддержка кириллицы)
def draw_text(image, text, position, font_size=25, color=(255, 255, 255)):
    pil_image = Image.fromarray(cv2.cvtColor(image, cv2.COLOR_BGR2RGB))
    draw = ImageDraw.Draw(pil_image)

    try:
        font = ImageFont.truetype("arial.ttf", font_size)
    except IOError:
        font = ImageFont.load_default()

    draw.text(position, text, font=font, fill=color)
    return cv2.cvtColor(np.array(pil_image), cv2.COLOR_RGB2BGR)

def send_frame_to_server(frame):
    global waiting_for_frame, config
    try:
        _, jpeg = cv2.imencode('.jpg', frame)
        response = requests.post(
            config['recognition_url'],
            files={'image': ('frame.jpg', jpeg.tobytes(), 'image/jpeg')},
            timeout=5
        )
        waiting_for_frame = False
        
        if response.ok:
            response_queue.put(response.json())
    except Exception as e:
        waiting_for_frame = False
        print("Ошибка при отправке запроса:", e)

def process_video():
    global waiting_for_frame
    video_capture = cv2.VideoCapture(0)
    last_response = []

    while True:
        ret, frame = video_capture.read()
        if not ret:
            continue

        # Отправка кадра на сервер в отдельном потоке
        if not waiting_for_frame:
            waiting_for_frame = True
            threading.Thread(target=send_frame_to_server, args=(frame.copy(),), daemon=True).start()

        # Проверка ответа от сервера
        if not response_queue.empty():
            last_response = response_queue.get()

        # Отрисовка результатов
        for item in last_response:
            name = item['name']
            top, right, bottom, left = item['location']
            
            if name == "Unknown":
                display_text = "Не найден"
                box_color = (0, 0, 255)  # Красная рамка для неизвестных
            else:
                display_text = f"ID: {name}"
                box_color = (0, 255, 0)  # Зеленая рамка для найденных

            cv2.rectangle(frame, (left, top), (right, bottom), box_color, 2)
            frame = draw_text(frame, display_text, (left, max(0, top - 30)), font_size=25, color=(255, 255, 255))

        cv2.imshow("Face Recognition", frame)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    video_capture.release()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    process_video()