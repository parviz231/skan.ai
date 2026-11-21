<?php
require_once(__DIR__ . "/practice_data.php");

$isTeacher = isset($_SESSION['is_teacher']) && $_SESSION['is_teacher'] === true;

// Для ученика берем его класс из базы, для учителя - выбранный фильтр
$filterClass = isset($_GET['class']) ? $_GET['class'] : '';
$studentClass = '';

if (!$isTeacher && $isLoggedIn) {
    $stmt = $conn->prepare("SELECT grade FROM students WHERE user_id = ?");
    $stmt->bind_param('i', $userId); $stmt->execute();
    $studentClass = $stmt->get_result()->fetch_assoc()['grade'] ?? '';
    $stmt->close();
    $filterClass = $studentClass; // Ученик видит только свой класс
}

// Получаем список классов для фильтра (только для учителя)
$classes = [];
if ($isTeacher) {
    $res = $conn->query("SELECT DISTINCT grade FROM students WHERE grade IS NOT NULL ORDER BY grade ASC");
    if ($res) { while ($row = $res->fetch_assoc()) $classes[] = $row['grade']; }
}

// Формируем запрос
$where = "WHERE s.grade IS NOT NULL";
if ($filterClass) {
    $where .= " AND s.grade = '" . $conn->real_escape_string($filterClass) . "'";
}

$sql = "
    SELECT 
        s.nickname,
        s.grade,
        COUNT(mr.id) as total_tasks,
        IFNULL(SUM(mr.score), 0) as total_score
    FROM students s
    LEFT JOIN module_results mr ON mr.user_id = s.user_id
    $where
    GROUP BY s.user_id, s.nickname, s.grade
    ORDER BY total_score DESC, total_tasks DESC
    LIMIT 50
";
$res = $conn->query($sql);
$leaders = [];
if ($res) { while ($row = $res->fetch_assoc()) $leaders[] = $row; }

$pageTitle  = 'Рейтинг';
$activePage = 'leaderboard';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6 animate-fade-up">
  
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-3xl font-black text-ink-900">Таблица лидеров</h1>
      <p class="text-gray-500 text-sm mt-1">
        <?= $isTeacher ? 'Общий рейтинг всех учеников' : 'Рейтинг вашего класса (' . htmlspecialchars($studentClass) . ')' ?>
      </p>
    </div>

    <!-- Фильтр по классу (только для учителя) -->
    <?php if ($isTeacher): ?>
      <form method="GET" class="flex items-center gap-2 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
        <i class="fa-solid fa-filter text-gray-400 ml-2"></i>
        <select name="class" onchange="this.form.submit()" class="bg-transparent border-none text-sm font-bold text-ink-900 focus:outline-none pr-4 cursor-pointer">
          <option value="">Все классы</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $filterClass === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm text-gray-600">
      <thead class="bg-gray-50 border-b border-gray-100 text-xs uppercase font-bold text-gray-400">
        <tr>
          <th class="px-6 py-4 text-center w-16">Место</th>
          <th class="px-6 py-4">Ученик</th>
          <?php if ($isTeacher): ?><th class="px-6 py-4">Класс</th><?php endif; ?>
          <th class="px-6 py-4 text-center">Решено</th>
          <th class="px-6 py-4 text-right">Баллы</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php if (empty($leaders)): ?>
          <tr><td colspan="<?= $isTeacher ? 5 : 4 ?>" class="px-6 py-8 text-center text-gray-400">Нет данных за этот период.</td></tr>
        <?php else: ?>
          <?php foreach ($leaders as $index => $lead): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4 text-center">
                <?php if ($index === 0): ?><span class="text-2xl">🥇</span>
                <?php elseif ($index === 1): ?><span class="text-2xl">🥈</span>
                <?php elseif ($index === 2): ?><span class="text-2xl">🥉</span>
                <?php else: ?><span class="text-sm font-bold text-gray-400"><?= $index + 1 ?></span><?php endif; ?>
              </td>
              <td class="px-6 py-4 font-bold text-ink-900 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold"><?= mb_substr($lead['nickname'], 0, 1) ?></div>
                <?= htmlspecialchars($lead['nickname']) ?>
              </td>
              <?php if ($isTeacher): ?>
                <td class="px-6 py-4 text-xs font-semibold text-gray-500"><?= htmlspecialchars($lead['grade']) ?></td>
              <?php endif; ?>
              <td class="px-6 py-4 text-center font-semibold"><?= $lead['total_tasks'] ?></td>
              <td class="px-6 py-4 text-right font-black text-green-600">+<?= $lead['total_score'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>