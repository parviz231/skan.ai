<?php
require_once(__DIR__ . "/practice_data.php");

if (!isset($_SESSION['is_teacher']) || $_SESSION['is_teacher'] !== true) {
    header("Location: login.php");
    exit;
}

$teacherId = $_SESSION['teacher_id'];
$stmt = $conn->prepare("SELECT subject_id FROM teachers WHERE id = ?");
$stmt->bind_param('i', $teacherId); $stmt->execute();
$subjectId = $stmt->get_result()->fetch_assoc()['subject_id'] ?? 0;
$stmt->close();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ДОБАВЛЕНИЕ ТЕМЫ
    if (isset($_POST['action']) && $_POST['action'] === 'add_topic') {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        if ($title && $desc) {
            $stmt = $conn->prepare("INSERT INTO topics (title, description, subject_id) VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $title, $desc, $subjectId);
            $stmt->execute(); $stmt->close();
            $message = "Тема «$title» добавлена!";
        }
    }

    // 2. УДАЛЕНИЕ ТЕМЫ
    if (isset($_POST['action']) && $_POST['action'] === 'delete_topic') {
        $tid = intval($_POST['topic_id']);
        // Удаляем тему и все связанные модули (каскадно)
        $stmt = $conn->prepare("DELETE FROM topics WHERE id = ? AND subject_id = ?");
        $stmt->bind_param('ii', $tid, $subjectId); $stmt->execute(); $stmt->close();
        $message = "Тема удалена.";
    }

    // 3. ДОБАВЛЕНИЕ ЗАДАНИЯ
    if (isset($_POST['action']) && $_POST['action'] === 'add_module') {
        $tid     = intval($_POST['topic_id'] ?? 0);
        $title   = trim($_POST['module_title'] ?? '');
        $type    = $_POST['module_type'] ?? 'question';
        $intro   = trim($_POST['introduction'] ?? '');
        $correct = trim($_POST['correct_answer'] ?? '');
        $options = trim($_POST['options'] ?? '');
        
        if ($tid > 0 && $title && $intro && $correct) {
            $stmt = $conn->prepare("INSERT INTO modules (topic_id, title, module_type, introduction, correct_answer, options) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isssss', $tid, $title, $type, $intro, $correct, $options);
            $stmt->execute(); $stmt->close();
            $message = "Задание «$title» добавлено!";
        }
    }

    // 4. УДАЛЕНИЕ ЗАДАНИЯ
    if (isset($_POST['action']) && $_POST['action'] === 'delete_module') {
        $mid = intval($_POST['module_id']);
        $stmt = $conn->prepare("DELETE FROM modules WHERE id = ?"); $stmt->bind_param('i', $mid); $stmt->execute(); $stmt->close();
        $message = "Задание удалено.";
    }
}

// Получаем темы
$topics = [];
$stmt = $conn->prepare("SELECT id, title FROM topics WHERE subject_id = ? ORDER BY id ASC");
$stmt->bind_param('i', $subjectId); $stmt->execute();
$res = $stmt->get_result(); while ($row = $res->fetch_assoc()) $topics[] = $row; $stmt->close();

// Получаем задания
$modules = [];
$stmt = $conn->prepare("SELECT m.id, m.title, m.module_type, t.title as topic_title FROM modules m JOIN topics t ON t.id = m.topic_id WHERE t.subject_id = ? ORDER BY m.id DESC");
$stmt->bind_param('i', $subjectId); $stmt->execute();
$res = $stmt->get_result(); while ($row = $res->fetch_assoc()) $modules[] = $row; $stmt->close();

$pageTitle  = 'Управление контентом';
$activePage = 'teacher';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fade-up">
  
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-black text-ink-900 tracking-tight">Управление контентом</h1>
      <p class="text-gray-500 mt-1 text-sm">Добавление и удаление тем и заданий.</p>
    </div>
    <a href="teacher.php" class="px-4 py-2 bg-gray-100 text-ink-900 font-bold text-sm rounded-xl hover:bg-gray-200 transition">← Назад</a>
  </div>

  <?php if ($message): ?>
    <div class="p-4 rounded-xl text-sm font-bold flex items-center gap-3 bg-green-50 text-green-700 border border-green-200"><?= $message ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Форма добавления темы -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
      <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2"><i class="fa-solid fa-book-medical text-indigo-500"></i> Добавить тему</h2>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_topic">
        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Название</label><input type="text" name="title" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm"></div>
        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Теория</label><textarea name="description" required rows="3" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm"></textarea></div>
        <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition">Создать тему</button>
      </form>
    </div>

    <!-- Форма добавления задания -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
      <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2"><i class="fa-solid fa-circle-question text-amber-500"></i> Добавить задание</h2>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_module">
        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Тема</label>
          <select name="topic_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
            <option value="">Выбрать...</option>
            <?php foreach ($topics as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Тип</label><select name="module_type" class="w-full border border-gray-200 rounded-lg px-2 py-2.5 text-sm"><option value="question">Вопрос</option><option value="test">Тест</option></select></div>
          <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ответ</label><input type="text" name="correct_answer" required class="w-full border border-gray-200 rounded-lg px-2 py-2.5 text-sm font-mono"></div>
        </div>
        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Варианты (через запятую)</label><input type="text" name="options" placeholder="1, 2, 3" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-mono"></div>
        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Условие</label><textarea name="introduction" required rows="2" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm"></textarea></div>
        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Заголовок</label><input type="text" name="module_title" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm"></div>
        <button type="submit" class="w-full bg-amber-500 text-white font-bold py-3 rounded-xl hover:bg-amber-600 transition">Добавить задание</button>
      </form>
    </div>

  </div>

  <!-- Списки для удаления -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
    
    <!-- Темы -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
      <h2 class="text-lg font-bold text-ink-900 mb-4">Ваши темы</h2>
      <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
        <?php foreach ($topics as $t): ?>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <span class="text-sm font-semibold"><?= htmlspecialchars($t['title']) ?></span>
            <form method="POST" onsubmit="return confirm('Удалить тему и все ее задания?');"><input type="hidden" name="action" value="delete_topic"><input type="hidden" name="topic_id" value="<?= $t['id'] ?>"><button type="submit" class="text-red-500 hover:text-red-700 px-2"><i class="fa-solid fa-trash"></i></button></form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Задания -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
      <h2 class="text-lg font-bold text-ink-900 mb-4">Ваши задания</h2>
      <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
        <?php foreach ($modules as $m): ?>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <div>
              <span class="text-sm font-semibold block"><?= htmlspecialchars($m['title']) ?></span>
              <span class="text-[10px] text-gray-500"><?= htmlspecialchars($m['topic_title']) ?></span>
            </div>
            <form method="POST" onsubmit="return confirm('Удалить задание?');"><input type="hidden" name="action" value="delete_module"><input type="hidden" name="module_id" value="<?= $m['id'] ?>"><button type="submit" class="text-red-500 hover:text-red-700 px-2"><i class="fa-solid fa-trash"></i></button></form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>