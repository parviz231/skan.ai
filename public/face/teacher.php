<?php
require_once(__DIR__ . "/practice_data.php");

// Проверка авторизации учителя
if (!isset($_SESSION['is_teacher']) || $_SESSION['is_teacher'] !== true) {
    header("Location: login.php");
    exit;
}

$teacherId = $_SESSION['teacher_id'];

// Получаем информацию об учителе и его предмете
$stmt = $conn->prepare("
    SELECT t.name, t.surname, s.id as subject_id, s.name as subject_name 
    FROM teachers t 
    LEFT JOIN subjects s ON s.id = t.subject_id 
    WHERE t.id = ? LIMIT 1
");
$stmt->bind_param('i', $teacherId); $stmt->execute();
$teacherData = $stmt->get_result()->fetch_assoc(); $stmt->close();

$teacherName = $teacherData['name'] ?? 'Учитель';
$subjectId   = $teacherData['subject_id'] ?? 0;
$subjectName = $teacherData['subject_name'] ?? 'Не назначен';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // УДАЛЕНИЕ РЕЗУЛЬТАТА УЧЕНИКА (опционально, если нужно)
    if (isset($_POST['action']) && $_POST['action'] === 'delete_student_result') {
        $studentId = intval($_POST['student_id']);
        $stmt = $conn->prepare("DELETE FROM module_results WHERE user_id = ?"); 
        $stmt->bind_param('i', $studentId); $stmt->execute(); $stmt->close();
        $message = "Результаты ученика сброшены.";
    }
}

/* =========================================
   СТАТИСТИКА
   ========================================= */
$stats = ['students' => 0, 'tasks_done' => 0, 'avg_score' => 0];

if ($subjectId > 0) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT mr.user_id) as cnt FROM module_results mr JOIN modules m ON m.id = mr.module_id JOIN topics t ON t.id = m.topic_id WHERE t.subject_id = ?");
    $stmt->bind_param('i', $subjectId); $stmt->execute();
    $stats['students'] = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0; $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM module_results mr JOIN modules m ON m.id = mr.module_id JOIN topics t ON t.id = m.topic_id WHERE t.subject_id = ?");
    $stmt->bind_param('i', $subjectId); $stmt->execute();
    $stats['tasks_done'] = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0; $stmt->close();

    $stmt = $conn->prepare("SELECT AVG(mr.score) as avg_s FROM module_results mr JOIN modules m ON m.id = mr.module_id JOIN topics t ON t.id = m.topic_id WHERE t.subject_id = ?");
    $stmt->bind_param('i', $subjectId); $stmt->execute();
    $avg = $stmt->get_result()->fetch_assoc()['avg_s'] ?? 0;
    $stats['avg_score'] = round($avg * 100); $stmt->close();
}

$students = [];
if ($subjectId > 0) {
    $stmt = $conn->prepare("SELECT s.user_id, s.nickname, s.grade, COUNT(mr.id) as total_tasks, IFNULL(SUM(mr.score), 0) as total_score FROM students s JOIN module_results mr ON mr.user_id = s.user_id JOIN modules m ON m.id = mr.module_id JOIN topics t ON t.id = m.topic_id WHERE t.subject_id = ? GROUP BY s.user_id, s.nickname, s.grade ORDER BY total_score DESC");
    $stmt->bind_param('i', $subjectId); $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $students[] = $row;
    $stmt->close();
}

$activities = [];
if ($subjectId > 0) {
    $stmt = $conn->prepare("SELECT mr.*, m.title as module_title FROM module_results mr JOIN modules m ON m.id = mr.module_id JOIN topics t ON t.id = m.topic_id WHERE t.subject_id = ? ORDER BY mr.created_at DESC LIMIT 8");
    $stmt->bind_param('i', $subjectId); $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $activities[] = $row;
    $stmt->close();
}

$pageTitle  = 'Панель учителя';
$activePage = 'teacher';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fade-up">
  
  <!-- Шапка -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-3xl font-black text-ink-900 tracking-tight">Панель учителя</h1>
      <p class="text-gray-500 mt-1 text-sm">
        Предмет: <b class="text-indigo-600"><?= htmlspecialchars($subjectName) ?></b>. Добро пожаловать, <?= htmlspecialchars($teacherName) ?>.
      </p>
    </div>
    <div class="flex gap-2">
      <a href="teacher_content.php" class="bg-white border border-gray-200 hover:bg-gray-50 text-ink-900 px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm transition flex items-center gap-2">
        <i class="fa-solid fa-book-medical text-indigo-500"></i> Контент
      </a>
      <!-- Кнопка добавления ученика УБРАНА -->
    </div>
  </div>

  <!-- Уведомления -->
  <?php if ($message): ?>
    <div class="p-4 rounded-xl text-sm font-bold flex items-center gap-3 <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' ?> animate-fade-up">
      <i class="fa-solid <?= $messageType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i><div><?= $message ?></div>
    </div>
  <?php endif; ?>

  <!-- Карточки статистики -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
      <div class="flex items-center justify-between mb-4"><span class="text-sm font-bold text-gray-500">Активных учеников</span><div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center"><i class="fa-solid fa-users"></i></div></div>
      <div class="text-3xl font-black text-ink-900"><?= $stats['students'] ?></div>
      <p class="text-xs text-gray-400 mt-2">Решали задания по предмету</p>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
      <div class="flex items-center justify-between mb-4"><span class="text-sm font-bold text-gray-500">Решено заданий</span><div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center"><i class="fa-solid fa-check-double"></i></div></div>
      <div class="text-3xl font-black text-ink-900"><?= $stats['tasks_done'] ?></div>
      <p class="text-xs text-gray-400 mt-2">Всего по предмету</p>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
      <div class="flex items-center justify-between mb-4"><span class="text-sm font-bold text-gray-500">Средний балл</span><div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center"><i class="fa-solid fa-chart-line"></i></div></div>
      <div class="text-3xl font-black text-ink-900"><?= $stats['avg_score'] ?>%</div>
      <p class="text-xs text-gray-400 mt-2">Успеваемость по предмету</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Таблица учеников -->
    <div class="lg:col-span-8 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
        <h2 class="text-base font-bold text-ink-900">Успеваемость по предмету «<?= htmlspecialchars($subjectName) ?>»</h2>
        <span class="text-xs font-bold text-gray-400"><?= count($students) ?> чел.</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-white border-b border-gray-100 text-xs uppercase font-bold text-gray-400">
            <tr><th class="px-6 py-3">Ученик</th><th class="px-4 py-3">Класс</th><th class="px-4 py-3 text-center">Решено</th><th class="px-4 py-3 text-right">Баллы</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php if (empty($students)): ?>
              <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Пока никто не решал задания.</td></tr>
            <?php else: ?>
              <?php foreach ($students as $s): ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold"><?= mb_substr($s['nickname'], 0, 1) ?></div><span class="font-bold text-ink-900"><?= htmlspecialchars($s['nickname']) ?></span></div></td>
                  <td class="px-4 py-4 text-xs text-gray-500"><?= htmlspecialchars($s['grade'] ?? '—') ?></td>
                  <td class="px-4 py-4 text-center font-semibold"><?= $s['total_tasks'] ?></td>
                  <td class="px-4 py-4 text-right font-black text-green-600">+<?= $s['total_score'] ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Лента активности -->
    <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50"><h2 class="text-base font-bold text-ink-900">Активность</h2><i class="fa-solid fa-clock text-gray-400"></i></div>
      <div class="p-4 space-y-4 max-h-[500px] overflow-y-auto custom-scrollbar">
        <?php if (empty($activities)): ?><p class="text-center text-gray-400 text-sm py-4">Действий пока нет.</p>
        <?php else: ?>
          <?php foreach ($activities as $act): ?>
            <div class="flex gap-3 items-start">
              <div class="w-8 h-8 rounded-full <?= $act['score'] > 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?> flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid <?= $act['score'] > 0 ? 'fa-check' : 'fa-xmark' ?> text-xs"></i></div>
              <div class="flex-grow min-w-0">
                <p class="text-xs text-ink-900 font-bold truncate">Ученик #<?= $act['user_id'] ?></p>
                <p class="text-[11px] text-gray-500 mt-0.5 leading-snug"><?= $act['score'] > 0 ? 'Верно ответил на' : 'Ошибся в' ?> <span class="font-semibold text-ink-900">«<?= htmlspecialchars($act['module_title']) ?>»</span></p>
                <p class="text-[10px] text-gray-400 mt-1 font-mono"><?= date('d.m H:i', strtotime($act['created_at'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>