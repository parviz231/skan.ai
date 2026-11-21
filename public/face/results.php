<?php
require_once(__DIR__ . "/practice_data.php");

// Проверка, что зашел ученик
if (!isset($_SESSION['id1'])) {
    header("Location: login.php"); exit;
}

$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($topicId === 0) { die("Тема не выбрана. <a href='practice.php'>Назад</a>"); }

// 1. Получаем тему
$stmt = $conn->prepare("SELECT title, subject_id FROM topics WHERE id = ?");
$stmt->bind_param('i', $topicId);
$stmt->execute();
$topic = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$topic) { die("Тема не найдена."); }

// 2. Получаем данные текущего ученика (чтобы узнать его класс)
$studentId = (int)$_SESSION['id1'];
$stmt = $conn->prepare("SELECT grade, class FROM users WHERE id = ?");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) { die("Ученик не найден."); }

$grade = $student['grade'];
$class = $student['class'];

// 3. Считаем общее количество вопросов ТОЛЬКО В ЭТОЙ ТЕМЕ
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM modules WHERE topic_id = ? AND module_type = 'test'");
$stmt->bind_param('i', $topicId);
$stmt->execute();
$totalQuestions = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// 4. Получаем результаты ТОЛЬКО этого класса и ТОЛЬКО по этой теме
// Связываем module_results (r) с modules (m) по ID вопроса
$sql = "
    SELECT 
        u.id as user_id, 
        u.surname, 
        u.name, 
        u.patronymic, 
        COALESCE(SUM(r.score), 0) as total_score, 
        COUNT(DISTINCT r.module_id) as answered
    FROM module_results r
    JOIN users u ON u.id = r.user_id
    JOIN modules m ON m.id = r.module_id AND m.topic_id = ? AND m.module_type = 'test'
    WHERE u.grade = ? AND u.class = ?
    GROUP BY u.id, u.surname, u.name, u.patronymic
    ORDER BY total_score DESC, u.surname ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $topicId, $grade, $class);
$stmt->execute();
$res = $stmt->get_result();
$classmates = [];
while ($row = $res->fetch_assoc()) {
    $classmates[] = $row;
}
$stmt->close();

$pageTitle = 'Результаты класса — ' . $topic['title'];
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 py-10 space-y-8">
  
  <div class="flex items-center justify-between">
      <a href="practice.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-ink-900 font-medium transition">
        <i class="fa-solid fa-arrow-left"></i> Назад к темам
      </a>
  </div>

  <div>
    <h1 class="text-3xl font-black text-ink-900">Результаты: <?= htmlspecialchars($topic['title']) ?></h1>
    <p class="text-gray-500 mt-1 text-sm">Класс: <b><?= htmlspecialchars($grade . $class) ?></b>. Всего вопросов в теме: <?= $totalQuestions ?>.</p>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
      <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-ink-900">Мои одноклассники</h2>
          <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full">
              Решали: <?= count($classmates) ?> чел.
          </span>
      </div>

      <?php if (empty($classmates)): ?>
          <div class="px-6 py-8 text-center text-gray-400 text-sm">
              В вашем классе пока никто не проходил эту тему.
          </div>
      <?php else: ?>
          <table class="w-full text-left text-sm text-gray-600">
            <thead class="text-xs uppercase text-gray-400 border-b border-gray-50">
              <tr>
                <th class="px-6 py-3 font-bold">Ученик</th>
                <th class="px-6 py-3 font-bold text-center">Решено</th>
                <th class="px-6 py-3 font-bold text-center">Баллы</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($classmates as $s): 
                  $name = trim($s['surname'] . ' ' . $s['name'] . ' ' . ($s['patronymic'] ?? ''));
                  $score = (int)$s['total_score'];
                  $scoreColor = $score >= 9 ? 'text-green-600' : ($score >= 5 ? 'text-amber-500' : 'text-red-500');
                  
                  // Подсвечиваем текущего ученика
                  $isMe = ($s['user_id'] == $studentId);
                  $rowClass = $isMe ? 'bg-indigo-50' : 'hover:bg-gray-50';
              ?>
                <tr class="<?= $rowClass ?> transition">
                  <td class="px-6 py-3 font-bold <?= $isMe ? 'text-indigo-700' : 'text-ink-900' ?>">
                      <?= htmlspecialchars($name) ?> 
                      <?= $isMe ? '<span class="text-xs font-normal ml-2 text-indigo-500">(Вы)</span>' : '' ?>
                  </td>
                  <td class="px-6 py-3 text-center"><?= (int)$s['answered'] ?> / <?= $totalQuestions ?></td>
                  <td class="px-6 py-3 text-center font-black <?= $scoreColor ?>"><?= $score ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
      <?php endif; ?>
  </div>

</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>