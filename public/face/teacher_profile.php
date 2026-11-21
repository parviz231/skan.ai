<?php
require_once(__DIR__ . "/practice_data.php");

if (!isset($_SESSION['is_teacher']) || $_SESSION['is_teacher'] !== true) {
    header("Location: login.php");
    exit;
}

$teacherId = $_SESSION['teacher_id'];

// Получаем данные учителя (включая предмет через JOIN)
$stmt = $conn->prepare("
    SELECT t.*, s.name as subject_name 
    FROM teachers t 
    LEFT JOIN subjects s ON s.id = t.subject_id 
    WHERE t.id = ? LIMIT 1
");
$stmt->bind_param('i', $teacherId); $stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$teacher) {
    die("Учитель не найден в базе данных.");
}

// Функция для выбора иконки (как в списке коллег)
function get_profile_icon($subjectName, $teacherImage) {
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

// ВЫЧИСЛЯЕМ ВОЗРАСТ
$age = '—';
if (!empty($teacher['birth'])) {
    $birthDate = new DateTime($teacher['birth']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y . ' лет';
}

$initials = mb_substr($teacher['name'], 0, 1) . mb_substr($teacher['surname'], 0, 1);
$iconData = get_profile_icon($teacher['subject_name'] ?? '', $teacher['image']);

$pageTitle  = 'Профиль учителя';
$activePage = 'teacher';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8 animate-fade-up">
  
  <!-- Верхняя карточка -->
  <div class="bg-white rounded-[2rem] border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden relative">
    <div class="h-40 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 relative">
      <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
      <div class="absolute top-6 right-6">
        <a href="logout.php" class="bg-white/20 hover:bg-white/30 text-white backdrop-blur-md px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 border border-white/20 shadow-sm">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Выйти
        </a>
      </div>
    </div>
    
    <div class="px-8 sm:px-12 pb-10">
      <div class="flex flex-col sm:flex-row items-center sm:items-end gap-6 -mt-16 sm:-mt-20 mb-8">
        
        <!-- Аватар или Иконка -->
        <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-full border-[6px] border-white shadow-xl flex items-center justify-center text-5xl sm:text-6xl relative z-10 shrink-0 overflow-hidden <?= $iconData['type'] === 'img' ? 'bg-gray-100' : $iconData['bg'] ?>">
          <?php if ($iconData['type'] === 'img'): ?>
            <img src="<?= htmlspecialchars($iconData['value']) ?>" class="w-full h-full object-cover">
          <?php else: ?>
            <i class="fa-solid <?= $iconData['value'] ?>"></i>
          <?php endif; ?>
        </div>
        
        <!-- Имя и предмет -->
        <div class="text-center sm:text-left flex-grow pb-2">
          <h1 class="text-2xl sm:text-4xl font-black text-ink-900 tracking-tight">
            <?= htmlspecialchars($teacher['surname'] . ' ' . $teacher['name']) ?>
          </h1>
          <p class="text-indigo-600 font-bold text-sm sm:text-base mt-1 flex items-center justify-center sm:justify-start gap-2">
            <i class="fa-solid fa-book-open"></i> Учитель: <?= htmlspecialchars($teacher['subject_name'] ?? 'Не назначен') ?>
          </p>
        </div>
      </div>

      <!-- Информация -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        
        <div class="p-5 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col justify-center">
          <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Логин</span>
          <span class="text-sm font-bold text-ink-900 font-mono">@<?= htmlspecialchars($teacher['login']) ?></span>
        </div>

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
          <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Возраст</span>
          <span class="text-sm font-bold text-ink-900"><?= $age ?></span>
        </div>

      </div>
    </div>
  </div>

  <!-- Быстрые действия -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 animate-fade-up delay-100">
    <a href="teacher.php" class="group bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex items-center gap-5 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition-transform"></div>
      <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform relative z-10"><i class="fa-solid fa-chart-pie"></i></div>
      <div class="relative z-10"><h3 class="text-base font-bold text-ink-900">Панель</h3><p class="text-xs text-gray-500 mt-1">Статистика.</p></div>
    </a>

    <a href="practice.php" class="group bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex items-center gap-5 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition-transform"></div>
      <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform relative z-10"><i class="fa-solid fa-graduation-cap"></i></div>
      <div class="relative z-10"><h3 class="text-base font-bold text-ink-900">Практика</h3><p class="text-xs text-gray-500 mt-1">Взгляд ученика.</p></div>
    </a>

    <a href="teachers_list.php" class="group bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex items-center gap-5 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition-transform"></div>
      <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform relative z-10"><i class="fa-solid fa-users"></i></div>
      <div class="relative z-10"><h3 class="text-base font-bold text-ink-900">Коллеги</h3><p class="text-xs text-gray-500 mt-1">Список учителей.</p></div>
    </a>
  </div>

</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>