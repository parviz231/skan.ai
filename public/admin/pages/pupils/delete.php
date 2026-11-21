<?php
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        echo "<div class='alert alert-danger'>Invalid student identifier.</div>";
        return;
    }

    require_once('config.php');

    try {
        $conn->begin_transaction();

        $deleteVisits = $conn->prepare('DELETE FROM visits WHERE user_id = ?');
        $deleteVisits->bind_param('i', $id);
        $deleteVisits->execute();
        $deleteVisits->close();

        $deleteStudent = $conn->prepare('DELETE FROM users WHERE id = ?');
        $deleteStudent->bind_param('i', $id);
        $deleteStudent->execute();
        $deleted = $deleteStudent->affected_rows;
        $deleteStudent->close();

        if ($deleted !== 1) {
            throw new RuntimeException('Student was not found.');
        }

        $conn->commit();
        echo "<script>window.location.href='?page=3';</script>";
    } catch (Throwable $error) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Unable to delete the student. No data was changed.</div>";
    }
}
