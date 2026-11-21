from flask import Flask, request, jsonify
import face_recognition
import numpy as np
import cv2
import pathlib
import os
import json
import mysql.connector
from datetime import datetime, timedelta

def load_config(config_path):
    with open(config_path, 'r', encoding='utf-8') as f:
        return json.load(f)

cpath = str(pathlib.Path(__file__).parent.resolve()) + "\\"
config_file = cpath + 'config.json'
config = load_config(config_file)

app = Flask(__name__)

# Подключение к БД MySQL
db = mysql.connector.connect(
    host=config['db_host'],
    user=config['db_user'],
    password=config['db_password'],
    database=config['db_database']
)
cursor = db.cursor()

known_face_encodings = []
known_face_ids = []

# Загрузка эталонных изображений
pictures_path = os.path.join(cpath, 'database', 'pictures')

if os.path.exists(pictures_path):
    for filename in os.listdir(pictures_path):
        if filename.lower().endswith(('.jpg', '.jpeg', '.png')):
            img_path = os.path.join(pictures_path, filename)
            img = face_recognition.load_image_file(img_path)
            enc = face_recognition.face_encodings(img)

            if enc:
                known_face_encodings.append(enc[0])
                # Имя файла без расширения используется как ID
                known_face_ids.append(os.path.splitext(filename)[0])
    print(f"Загружено {len(known_face_encodings)} лиц из базы.")
else:
    print(f"ВНИМАНИЕ: Папка {pictures_path} не найдена!")

# Порог строгости распознавания (0.45 минимизирует ошибки)
TOLERANCE = 0.45

@app.route('/recognize', methods=['POST'])
def recognize():
    if 'image' not in request.files:
        return jsonify({"error": "No image uploaded"}), 400

    file = request.files['image']
    file_bytes = np.frombuffer(file.read(), np.uint8)
    img = cv2.imdecode(file_bytes, cv2.IMREAD_COLOR)

    if img is None:
        return jsonify({"error": "Invalid image"}), 400

    rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    face_locations = face_recognition.face_locations(rgb)
    face_encodings = face_recognition.face_encodings(rgb, face_locations)

    results = []
    now = datetime.now()
    day_start = now.replace(hour=0, minute=0, second=0, microsecond=0)
    day_end = day_start + timedelta(days=1)

    for encoding, loc in zip(face_encodings, face_locations):
        name = "Unknown"
        user_id = None

        if len(known_face_encodings) > 0:
            face_distances = face_recognition.face_distance(known_face_encodings, encoding)
            best_match = np.argmin(face_distances)

            # Проверка по усиленному порогу
            if face_distances[best_match] < TOLERANCE:
                name = known_face_ids[best_match]
                try:
                    user_id = int(name)
                except ValueError:
                    user_id = None

        # Фиксация визита в БД только для распознанного пользователя
        if user_id is not None:
            try:
                cursor.execute("SELECT id FROM users WHERE id = %s", (user_id,))
                user = cursor.fetchone()

                if user is not None:
                    cursor.execute("""
                        SELECT visit_id, visit
                        FROM visits
                        WHERE user_id = %s
                        AND time >= %s
                        AND time < %s
                        ORDER BY time DESC, visit_id DESC
                        LIMIT 1
                    """, (user_id, day_start, day_end))

                    daily_visit = cursor.fetchone()

                    if daily_visit is None:
                        cursor.execute("""
                            INSERT INTO visits (user_id, visit, time)
                            VALUES (%s, 'visited', %s)
                        """, (user_id, now))
                        db.commit()
                    elif daily_visit[1] != 'visited':
                        cursor.execute("""
                            UPDATE visits
                            SET visit = 'visited', time = %s
                            WHERE visit_id = %s
                        """, (now, daily_visit[0]))
                        db.commit()
                else:
                    print(f"Пользователь с ID {user_id} не найден в таблице users")
            except mysql.connector.Error as err:
                print("Ошибка БД:", err)

        results.append({
            "name": name,
            "location": [int(x) for x in loc]
        })

    return jsonify(results)

if __name__ == '__main__':
    app.run(
        host='127.0.0.1',
        port=5500
    )