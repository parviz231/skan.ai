<?php
require_once(__DIR__ . "/practice_data.php");

if (!isset($_SESSION['is_teacher']) && !isset($_SESSION['id1'])) {
    header("Location: login.php");
    exit;
}

$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($topicId === 0) { die("Тема не выбрана. <a href='practice.php'>Назад</a>"); }

// 1. Получаем тему
$stmt = $conn->prepare("SELECT title, subject_id FROM topics WHERE id = ?");
$stmt->bind_param('i', $topicId);
$stmt->execute();
$topic = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$topic) { die("Тема не найдена. <a href='practice.php'>Назад</a>"); }

// 2. Узнаем, сколько всего вопросов в этом предмете
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM questions WHERE subject_id = ?");
$stmt->bind_param('i', $topic['subject_id']);
$stmt->execute();
$totalQuestions = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// 3. Получаем результаты и группируем по классам
$students_by_grade = [];

// Запрос: выбираем уникальных учеников, их класс и сумму баллов
$sql = "
    SELECT 
        u.id as user_id, 
        u.surname, 
        u.name, 
        u.patronymic, 
        u.grade, 
        u.class, 
        COALESCE(SUM(r.score), 0) as total_score, 
        COUNT(DISTINCT r.module_id) as answered
    FROM module_results r
    JOIN users u ON u.id = r.user_id
    GROUP BY u.id, u.surname, u.name, u.patronymic, u.grade, u.class
    ORDER BY u.grade ASC, total_score DESC, u.surname ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    // Кладем ученика в массив его класса
    $grade = (int)$row['grade'];
    $students_by_grade[$grade][] = $row;
}
$stmt->close();

$pageTitle = 'Результаты — ' . $topic['title'];
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 py-10 space-y-8">
  
  <div class="flex items-center justify-between">
      <a href="practice.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-ink-900 font-medium transition">
        <i class="fa-solid fa-arrow-left"></i> Назад к темам
      </a>
      <div class="text-sm text-gray-500">
          Всего вопросов: <span class="font-bold text-ink-900"><?= $totalQuestions ?></span>
      </div>
  </div>

  <div>
    <h1 class="text-3xl font-black text-ink-900">Результаты: <?= htmlspecialchars($topic['title']) ?></h1>
    <p class="text-gray-500 mt-1 text-sm">Выберите класс, чтобы посмотреть успеваемость учеников.</p>
  </div>

  <?php for ($grade = 5; $grade <= 11; $grade++): ?>
      <?php 
      // Считаем, сколько учеников именно в этом классе
      $count = isset($students_by_grade[$grade]) ? count($students_by_grade[$grade]) : 0; 
      ?>
      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
          
          <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex items-center justify-between">
              <h2 class="text-lg font-bold text-ink-900"><?= $grade ?> класс</h2>
              <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full">
                  Решали: <?= $count ?> чел.
              </span>
          </div>

          <?php if ($count === 0): ?>
              <div class="px-6 py-8 text-center text-gray-400 text-sm">
                  В этом классе пока никто не проходил эту тему.
              </div>
          <?php else: ?>
              <table class="w-full text-left text-sm text-gray-600">
                <thead class="text-xs uppercase text-gray-400 border-b border-gray-50">
                  <tr>
                    <th class="px-6 py-3 font-bold">Ученик</th>
                    <th class="px-6 py-3 font-bold text-center">Буква</th>
                    <th class="px-6 py-3 font-bold text-center">Решено</th>
                    <th class="px-6 py-3 font-bold text-center">Баллы</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <?php foreach ($students_by_grade[$grade] as $s): 
                      $name = trim($s['surname'] . ' ' . $s['name'] . ' ' . ($s['patronymic'] ?? ''));
                      $score = (int)$s['total_score'];
                      $scoreColor = $score >= 9 ? 'text-green-600' : ($score >= 5 ? 'text-amber-500' : 'text-red-500');
                  ?>
                    <tr class="hover:bg-gray-50 transition">
                      <td class="px-6 py-3 font-bold text-ink-900"><?= htmlspecialchars($name) ?></td>
                      <td class="px-6 py-3 text-center text-gray-500"><?= htmlspecialchars($s['class']) ?></td>
                      <td class="px-6 py-3 text-center"><?= (int)$s['answered'] ?> / <?= $totalQuestions ?></td>
                      <td class="px-6 py-3 text-center font-black <?= $scoreColor ?>"><?= $score ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
          <?php endif; ?>
      </div>
  <?php endfor; ?>

</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>