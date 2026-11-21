<?php
require_once(__DIR__ . "/practice_data.php");

$isTeacher = isset($_SESSION['is_teacher']) && $_SESSION['is_teacher'] === true;

if (!$isLoggedIn) {
    header("Location: login.php"); exit;
}

$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($topicId === 0) { die("Тема не выбрана"); }

// Тема
$stmt = $conn->prepare("
    SELECT t.id, t.title, t.subject_id, s.name AS subject_name
    FROM topics t
    JOIN subjects s ON s.id = t.subject_id
    WHERE t.id = ? LIMIT 1
");
$stmt->bind_param('i', $topicId);
$stmt->execute();
$topic = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$topic) { die("Тема не найдена"); }

// Всего вопросов
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM questions WHERE subject_id = ?");
$stmt->bind_param('i', $topic['subject_id']);
$stmt->execute();
$totalModules = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Фильтр для ученика
$filterGrade = null;
$filterClass = null;

if (!$isTeacher) {
    $stmt = $conn->prepare("SELECT grade, class FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $me = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($me) {
        $filterGrade = (int)$me['grade'];
        $filterClass = $me['class'];
    }
}

$whereClass = "";
if ($filterGrade !== null && $filterClass !== null) {
    $whereClass = " AND u.grade = " . intval($filterGrade) .
                  " AND u.class = '" . $conn->real_escape_string($filterClass) . "' ";
}

// Список
$sql = "
    SELECT u.id, u.surname, u.name, u.patronymic, u.grade, u.class,
           IFNULL(SUM(r.score), 0) AS total_score,
           COUNT(r.module_id) AS answered_count
    FROM users u
    LEFT JOIN module_results r
      ON r.user_id = u.id
     AND r.module_id IN (SELECT id FROM questions WHERE subject_id = ?)
    WHERE u.grade BETWEEN 5 AND 11
      $whereClass
    GROUP BY u.id
    HAVING answered_count > 0
    ORDER BY total_score DESC, u.surname ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $topic['subject_id']);
$stmt->execute();
$res = $stmt->get_result();
$students = [];
while ($row = $res->fetch_assoc()) $students[] = $row;
$stmt->close();

$pageTitle = 'Результаты — ' . $topic['title'];
$activePage = 'teacher';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-4xl w-full mx-auto px-4 py-10 space-y-8 animate-fade-up">

  <a href="module.php?id=<?= $topicId ?>"
     class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-ink-900 font-medium transition">
    <i class="fa-solid fa-arrow-left"></i> Назад к теме
  </a>

  <div>
    <h1 class="text-3xl font-black text-ink-900">
      <?= $isTeacher ? 'Все ученики' : 'Мои одноклассники' ?>:
      <?= htmlspecialchars($topic['title']) ?>
    </h1>
    <p class="text-gray-500 mt-1 text-sm">
      Предмет: <?= htmlspecialchars($topic['subject_name']) ?> ·
      Всего заданий: <?= $totalModules ?>
      <?php if (!$isTeacher && $filterGrade): ?>
        · Класс: <?= $filterGrade ?> «<?= htmlspecialchars($filterClass) ?>»
      <?php endif; ?>
    </p>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm text-gray-600">
      <thead class="bg-gray-50 border-b border-gray-100 text-xs uppercase font-bold text-gray-400">
        <tr>
          <th class="px-6 py-4">Ученик / Учитель</th>
          <th class="px-6 py-4">Класс</th>
          <th class="px-6 py-4 text-center">Решено</th>
          <th class="px-6 py-4 text-center">Баллы</th>
          <th class="px-6 py-4 text-center">Статус</th>
          <th class="px-6 py-4 text-right">Детали</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php if (empty($students)): ?>
          <tr>
            <td colspan="6" class="px-6 py-8 text-center text-gray-400">
              <?= $isTeacher ? 'Пока никто не проходил эту тему.' : 'Пока никто из вашего класса не проходил эту тему.' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($students as $s):
              $fullName = trim($s['surname'] . ' ' . $s['name'] . ' ' . ($s['patronymic'] ?? ''));
              $grade = (int)$s['total_score'];

              $gradeColor = 'bg-red-100 text-red-700';
              if ($grade >= 9)      $gradeColor = 'bg-green-100 text-green-700';
              elseif ($grade >= 7)  $gradeColor = 'bg-blue-100 text-blue-700';
              elseif ($grade >= 5)  $gradeColor = 'bg-amber-100 text-amber-700';

              $status = 'Не начат';
              $statusColor = 'bg-gray-100 text-gray-600';
              if ($grade > 0 && $grade < $totalModules) {
                  $status = 'В процессе';
                  $statusColor = 'bg-amber-100 text-amber-700';
              } elseif ($grade == $totalModules) {
                  $status = 'Выполнено';
                  $statusColor = 'bg-green-100 text-green-700';
              }
          ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4 font-bold text-ink-900"><?= htmlspecialchars($fullName) ?></td>
              <td class="px-6 py-4 text-xs font-semibold text-gray-500">
                <?= (int)$s['grade'] ?> «<?= htmlspecialchars($s['class']) ?>»
              </td>
              <td class="px-6 py-4 text-center font-semibold">
                <?= (int)$s['answered_count'] ?> / <?= $totalModules ?>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-black <?= $gradeColor ?>">
                  <?= $grade ?>
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?= $statusColor ?>">
                  <?= $status ?>
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <a href="results.php?topic_id=<?= $topicId ?>&user_id=<?= (int)$s['id'] ?>"
                   class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-100 transition">
                  Посмотреть ответы
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>