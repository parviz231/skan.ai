import pandas as pd
import sys
import os


if len(sys.argv) < 2:
    input_file = os.path.join(os.path.dirname(__file__), "student_data.csv")
else:
    input_file = sys.argv[1]

if not os.path.exists(input_file):
    print(f"Ошибка: файл {input_file} не найден.")
    sys.exit(1)

df = pd.read_csv(input_file)


df["grade_value_numeric"] = pd.to_numeric(df["grade_value"], errors="coerce")


bad_data = df[df["grade_value_numeric"].isna()]
if not bad_data.empty:
    bad_file = os.path.join(os.path.dirname(__file__), "bad_data.txt")
    bad_data.to_csv(bad_file, index=False, encoding="utf-8")
    print(f"Некорректные оценки сохранены в {bad_file}")

df_clean = df.dropna(subset=["grade_value_numeric"])

if df_clean.empty:
    print("Нет корректных данных для анализа.")
    sys.exit(1)


subjects = df_clean.groupby("subject")["grade_value_numeric"].mean()


report = []

report.append(
    f"Прогноз для ученика: {df_clean['surname'].iloc[0]} {df_clean['name'].iloc[0]}, "
    f"класс {df_clean['grade'].iloc[0]}{df_clean['class'].iloc[0]}"
)
report.append("")

good, weak = [], []
for subj, grade in subjects.items():
    if grade >= 8:
        good.append(f"{subj} (средний балл: {grade:.1f})")
    elif grade <= 5:
        weak.append(f"{subj} (средний балл: {grade:.1f})")

if good:
    report.append("Хорошие предметы:")
    report += [" - " + g for g in good]
else:
    report.append("Хороших предметов не выявлено.")

if weak:
    report.append("\nСлабые предметы (нужно подтянуть):")
    report += [" - " + w for w in weak]
else:
    report.append("\nСлабых предметов нет.")


if "visit" in df.columns:
    visits = df["visit"].dropna()
    if not visits.empty:
        attendance = (visits == "visited").mean() * 100
        report.append(f"\nПосещаемость: {attendance:.1f}%")
        if attendance < 60:
            report.append("Риск исключения из-за низкой посещаемости.")
        elif attendance < 80:
            report.append("Нужно чаще посещать уроки.")
        else:
            report.append("Хорошая посещаемость.")
    else:
        report.append("\nНет данных о посещаемости.")
else:
    report.append("\nКолонка 'visit' отсутствует в данных.")


ans_file = os.path.join(os.path.dirname(__file__), "ans.txt")
with open(ans_file, 'w', encoding="utf-8") as file:
    file.write("\n".join(report))


print("\n".join(report))
