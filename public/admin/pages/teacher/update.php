<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_POST['id']) && is_scalar($_POST['id']) ? filter_var((string)$_POST['id'], FILTER_VALIDATE_INT) : false;
$name = isset($_POST['name']) && is_scalar($_POST['name']) ? trim((string)$_POST['name']) : '';
$surname = isset($_POST['surname']) && is_scalar($_POST['surname']) ? trim((string)$_POST['surname']) : '';
$patronymic = isset($_POST['patronymic']) && is_scalar($_POST['patronymic']) ? trim((string)$_POST['patronymic']) : '';
$ageInput = isset($_POST['age']) && is_scalar($_POST['age']) ? trim((string)$_POST['age']) : '';
$birthday = isset($_POST['birthday']) && is_scalar($_POST['birthday']) ? trim((string)$_POST['birthday']) : '';
$gender = isset($_POST['gender']) && is_scalar($_POST['gender']) ? (string)$_POST['gender'] : '';
$subjectIdInput = isset($_POST['subject_id']) && is_scalar($_POST['subject_id']) ? (string)$_POST['subject_id'] : '';
$category = isset($_POST['category_of_specialty']) && is_scalar($_POST['category_of_specialty']) ? (string)$_POST['category_of_specialty'] : '';
$token = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['teacher_csrf_token'] ?? '';
$errors = [];
$ageValue = null;
$subjectId = filter_var($subjectIdInput, FILTER_VALIDATE_INT);
$birthDate = DateTime::createFromFormat('!Y-m-d', $birthday);
$dateErrors = DateTime::getLastErrors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $errors[] = 'Некорректный способ отправки формы.';
}
if ($id === false || $id === null || $id < 1) $errors[] = 'Некорректный ID учителя.';
if ($name === '') $errors[] = 'Имя обязательно.';
if ($surname === '') $errors[] = 'Фамилия обязательна.';
if ($birthday === '' || !$birthDate || $birthDate->format('Y-m-d') !== $birthday || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $birthDate > new DateTime('today')) {
    $errors[] = 'Укажите корректную дату рождения.';
}
if (!in_array($gender, ['male', 'female'], true)) $errors[] = 'Выберите корректный пол.';
if ($subjectId === false || $subjectId < 1) $errors[] = 'Выберите существующий предмет.';
if (!in_array($category, ['1', '2', 'highest'], true)) $errors[] = 'Выберите корректную категорию.';
if ($ageInput !== '') {
    $validatedAge = filter_var($ageInput, FILTER_VALIDATE_INT);
    if ($validatedAge === false || $validatedAge < 18 || $validatedAge > 80) {
        $errors[] = 'Возраст должен быть от 18 до 80 лет.';
    } else {
        $ageValue = $validatedAge;
    }
}
if (!is_string($token) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
    $errors[] = 'Форма устарела. Обновите страницу и повторите отправку.';
}

if (!$errors) {
    try {
        $subjectStmt = $conn->prepare('SELECT id FROM subjects WHERE id = ?');
        $subjectStmt->bind_param('i', $subjectId);
        $subjectStmt->execute();
        $validSubject = $subjectStmt->get_result()->fetch_assoc();
        $subjectStmt->close();
        if (!$validSubject) {
            $errors[] = 'Выбранный предмет не найден.';
        } else {
            $teacherCheck = $conn->prepare('SELECT id FROM teachers WHERE id = ?');
            $teacherCheck->bind_param('i', $id);
            $teacherCheck->execute();
            $teacherExists = $teacherCheck->get_result()->fetch_assoc();
            $teacherCheck->close();
            if (!$teacherExists) {
                $errors[] = 'Учитель не найден.';
            } else {
                $stmt = $conn->prepare(
                    'UPDATE teachers SET name = ?, surname = ?, patronymic = ?, age = ?, birth = ?, gender = ?, subject_id = ?, category_of_specialty = ? WHERE id = ?'
                );
                $stmt->bind_param('sssissisi', $name, $surname, $patronymic, $ageValue, $birthday, $gender, $subjectId, $category, $id);
                $stmt->execute();
                $stmt->close();

                echo '<script>window.location.replace("?page=11&saved=1");</script>';
                return;
            }
        }
    } catch (mysqli_sql_exception $error) {
        error_log('Teacher update failed: ' . $error->getMessage());
        $errors[] = 'Не удалось сохранить изменения. Повторите попытку.';
    }
}

echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars(implode(' ', $errors), ENT_QUOTES, 'UTF-8') . ' <a href="?page=12&amp;id=' . (int)$id . '">Вернуться к форме</a></div>';
