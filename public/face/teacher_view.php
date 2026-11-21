<?php
require_once(__DIR__ . "/practice_data.php");

if (!isset($_SESSION['is_teacher']) || $_SESSION['is_teacher'] !== true) {
    header("Location: login.php"); exit;
}

$viewId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($viewId === 0) { header("Location: teachers_list.php"); exit; }

$stmt = $conn->prepare("
    SELECT t.*, s.name as subject_name 
    FROM teachers t 
    LEFT JOIN subjects s ON s.id = t.subject_id 
    WHERE t.id = ? LIMIT 1
");
$stmt->bind_param('i', $viewId); $stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$teacher) { die("Учитель не найден."); }

// Функция иконки
function get_view_icon($subjectName, $teacherImage) {
    if (!empty($teacherImage)) return ['type' => 'img', 'value' => $teacherImage, 'bg' => 'bg-gray-100'];
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
    return ['type' => 'icon', 'value' => 'fa-user-tie', 'bg' => 'bg-gray-100 text-gray-600'];
}

$initials = mb_substr($teacher['name'], 0, 1) . mb_substr($teacher['surname'], 0, 1);
$iconData = get_view_icon($teacher['subject_name'] ?? '', $teacher['image']);

$pageTitle  = 'Профиль — ' . $teacher['surname'];
$activePage = 'teacher';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8 animate-fade-up">
  <a href="teachers_list.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-ink-900 font-medium transition">
    <i class="fa-solid fa-arrow-left"></i> Назад к списку
  </a>

  <div class="bg-white rounded-[2rem] border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden relative">
    <div class="h-40 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 relative"></div>
    
    <div class="px-8 sm:px-12 pb-10">
      <div class="flex flex-col sm:flex-row items-center sm:items-end gap-6 -mt-16 sm:-mt-20 mb-8">
        
        <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-full border-[6px] border-white shadow-xl flex items-center justify-center text-5xl sm:text-6xl relative z-10 shrink-0 overflow-hidden <?= $iconData['type'] === 'img' ? 'bg-gray-100' : $iconData['bg'] ?>">
          <?php if ($iconData['type'] === 'img'): ?>
            <img src="<?= htmlspecialchars($iconData['value']) ?>" class="w-full h-full object-cover">
          <?php else: ?>
            <i class="fa-solid <?= $iconData['value'] ?>"></i>
          <?php endif; ?>
        </div>
        
        <div class="text-center sm:text-left flex-grow pb-2">
          <h1 class="text-2xl sm:text-4xl font-black text-ink-900 tracking-tight">
            <?= htmlspecialchars($teacher['surname'] . ' ' . $teacher['name']) ?>
          </h1>
          <p class="text-indigo-600 font-bold text-sm sm:text-base mt-1 flex items-center justify-center sm:justify-start gap-2">
            <i class="fa-solid fa-book-open"></i> Учитель: <?= htmlspecialchars($teacher['subject_name'] ?? 'Не назначен') ?>
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col justify-center">
          <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Отчество</span>
          <span class="text-sm font-bold text-ink-900"><?= htmlspecialchars($teacher['patronymic'] ?: '—') ?></span>
        </div>
        <div class="p-5 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col justify-center">
          <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Категория</span>
          <span class="text-sm font-bold text-indigo-600 bg-indigo-50 w-max px-2 py-0.5 rounded-md mt-1">
            <?php 
              $cat = $teacher['category_of_specialty'];
              if ($cat === 'highest') echo 'Высшая';
              elseif ($cat === '1') echo 'Первая';
              elseif ($cat === '2') echo 'Вторая';
              else echo 'Нет';
            ?>
          </span>
        </div>
        <div class="p-5 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col justify-center">
          <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Дата рождения</span>
          <span class="text-sm font-bold text-ink-900"><?= $teacher['birth'] ? date('d.m.Y', strtotime($teacher['birth'])) : '—' ?></span>
        </div>
        <div class="p-5 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col justify-center">
          <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Возраст</span>
          <span class="text-sm font-bold text-ink-900"><?= $teacher['age'] ? $teacher['age'] . ' лет' : '—' ?></span>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>