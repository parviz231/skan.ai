import os
import time

# Если Python добавлен в системный PATH, можно указать просто 'python'
python_path = r'C:/Users/Parviz/Python310/python.exe'
path = r"C:/OSPanel/home/project.local/public/admin/Face_/"

files_to_run = ["recognition_server.py", "main.py"]

for file in files_to_run:
    cmd = f'start cmd /k "{python_path} \"{path + file}\""'
    print(f"Запуск: {cmd}")
    os.system(cmd)
    
    # Даем 7 секунд серверу на загрузку моделей face_recognition перед запуском main.py
    if file == "recognition_server.py":
        time.sleep(7)