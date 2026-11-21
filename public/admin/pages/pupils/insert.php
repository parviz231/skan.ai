<?php
if (isset($_POST['save'])) {
    require_once('config.php');

    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $birth = $_POST['birthday'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $grade = (int) ($_POST['grade'] ?? 0);
    $class = trim($_POST['class'] ?? '');
    $patronymic = trim($_POST['patronymic'] ?? '');

    if ($name === '' || $surname === '' || $birth === '' || $gender === '' || $grade < 1 || $class === '') {
        echo "<div class='alert alert-danger'>Fill in all required fields.</div>";
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO users (name, surname, birth, patronymic, gender, grade, class) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssssis', $name, $surname, $birth, $patronymic, $gender, $grade, $class);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Student added successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>Unable to add the student.</div>";
    }

    $stmt->close();
}
