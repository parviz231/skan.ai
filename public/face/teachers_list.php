<?php
require_once(__DIR__ . "/practice_data.php");

if (!isset($_SESSION['is_teacher']) || $_SESSION['is_teacher'] !== true) {
    header("Location: login.php");
    exit;
}

// Функция для выбора иконки по предмету
function get_teacher_icon($subjectName, $teacherImage) {
    // Если у учителя есть личное фото (ссылка), возвращаем ее
    if (!empty($teacherImage)) {
        return ['type' => 'img', 'value' => $teacherImage];
    }
    
    $name = mb_strtolower($subjectName, 'UTF-8');
    if (mb_strpos($name, 'матем') !== false || mb_strpos($name, 'алгебр') !== false) return ['type' => 'icon', 'value' => 'fa-calculator', 'bg' => 'bg-blue-100 text-blue-600'];
    if (mb_strpos($name, 'физик') !== false) return ['type' => 'icon', 'value' => 'fa-atom', 'bg' => 'bg-amber-100 text-amber-600'];
    if (mb_strpos($name, 'хими') !== false) return ['type' => 'icon', 'value' => 'fa-flask', 'bg' => 'bg-emerald-100 text-emerald-600'];
    if (mb_strpos($name, 'биол') !== false) return ['type' => 'icon', 'value' => 'fa-dna', 'bg' => 'bg-green-100 text-green-600'];
    if (mb_strpos($name, 'истор') !== false) return ['type' => 'icon', 'value' => 'fa-landmark', 'bg' => 'bg-rose-100 text-rose-600'];
    if (mb_strpos($name, 'информ') !== false) return ['type' => 'icon', 'value' => 'fa-laptop-code', 'bg' => 'bg-indigo-100 text-indigo-600'];
    if (mb_strpos($name, 'инос') !== false || mb_strpos($name, 'англ') !== false) return ['type' => 'icon', 'value' => 'fa-language', 'bg' => 'bg-pink-100 text-pink-600'];
    if (mb_strpos($name, 'литер') !== false) return ['type' => 'icon', 'value' => 'fa-book', 'bg' => 'bg-yellow-100 text-yellow-600'];
    if (mb_strpos($name, 'геогр') !== false) return ['type' => 'icon', 'value' => 'fa-earth-americas', 'bg' => 'bg-cyan-100 text-cyan-600'];
    if (mb_strpos($name, 'обще') !== false) return ['type' => 'icon', 'value' => 'fa-balance-scale', 'bg' => 'bg-purple-100 text-purple-600'];
    
    // По умолчанию
    return ['type' => 'icon', 'value' => 'fa-user-tie', 'bg' => 'bg-gray-100 text-gray-600'];
}

$currentTeacherId = $_SESSION['teacher_id'];
$stmt = $conn->prepare("
    SELECT t.id, t.name, t.surname, t.patronymic, t.image, s.name as subject_name 
    FROM teachers t 
    LEFT JOIN subjects s ON s.id = t.subject_id 
    WHERE t.id != ? 
    ORDER BY t.surname ASC
");
$stmt->bind_param('i', $currentTeacherId);
$stmt->execute();
$teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle  = 'Коллеги';
$activePage = 'teacher';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fade-up">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-black text-ink-900 tracking-tight">Коллеги</h1>
      <p class="text-gray-500 mt-1 text-sm">Список всех учителей школы.</p>
    </div>
    <a href="teacher_profile.php" class="px-4 py-2 bg-gray-100 text-ink-900 font-bold text-sm rounded-xl hover:bg-gray-200 transition">← Назад</a>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
    <?php if (empty($teachers)): ?>
      <div class="col-span-full bg-white p-8 rounded-2xl border border-gray-200 text-center text-gray-500">
        Пока нет других учителей.
      </div>
    <?php else: ?>
      <?php foreach ($teachers as $t): 
        $initials = mb_substr($t['name'], 0, 1) . mb_substr($t['surname'], 0, 1);
        $iconData = get_teacher_icon($t['subject_name'] ?? '', $t['image']);
      ?>
        <a href="teacher_view.php?id=<?= $t['id'] ?>" class="group bg-white rounded-3xl border border-gray-200 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center">
          
          <!-- Аватар или Иконка -->
          <div class="w-24 h-24 rounded-full border-4 border-white shadow-md flex items-center justify-center text-3xl font-black mb-4 group-hover:scale-105 transition-transform overflow-hidden <?= $iconData['type'] === 'img' ? 'bg-gray-100' : $iconData['bg'] ?>">
            <?php if ($iconData['type'] === 'img'): ?>
              <img src="<?= htmlspecialchars($iconData['value']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <i class="fa-solid <?= $iconData['value'] ?>"></i>
            <?php endif; ?>
          </div>

          <!-- Имя -->
          <h3 class="text-base font-bold text-ink-900 group-hover:text-indigo-600 transition-colors">
            <?= htmlspecialchars($t['surname'] . ' ' . $t['name']) ?>
          </h3>
          <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($t['patronymic'] ?? '') ?></p>
          
          <!-- Предмет -->
          <span class="mt-3 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase tracking-wider">
            <?= htmlspecialchars($t['subject_name'] ?? 'Не назначен') ?>
          </span>
          
          <div class="mt-4 pt-4 border-t border-gray-100 w-full text-xs font-bold text-gray-400 group-hover:text-indigo-600 flex items-center justify-center gap-2 transition-colors">
            <span>Открыть профиль</span>
            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>