<?php
require_once(__DIR__ . "/practice_data.php");

// Проверяем, вошел ли учитель
$isTeacher = isset($_SESSION['is_teacher']) && $_SESSION['is_teacher'] === true;
$currentSubjectId = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$subjects = [];

if ($isTeacher) {
    // === УЧИТЕЛЬ: только свой предмет ===
    $stmt = $conn->prepare("SELECT subject_id FROM teachers WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['teacher_id']); $stmt->execute();
    $teacherSubjectId = $stmt->get_result()->fetch_assoc()['subject_id'] ?? 0;
    $stmt->close();

    if ($teacherSubjectId > 0) {
        $stmt = $conn->prepare("SELECT id, name, category FROM subjects WHERE id = ?");
        $stmt->bind_param('i', $teacherSubjectId); $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $subjects[] = $row;
        $stmt->close();
        $currentSubjectId = $teacherSubjectId;
    }
} else {
    // === УЧЕНИК: все предметы ===
    $res = $conn->query("SELECT id, name, category FROM subjects ORDER BY id ASC");
    if ($res) { while ($row = $res->fetch_assoc()) $subjects[] = $row; }
    if ($currentSubjectId === 0 && !empty($subjects)) $currentSubjectId = intval($subjects[0]['id']);
}

// Находим текущий предмет
$currentSubject = null;
foreach ($subjects as $s) {
    if (intval($s['id']) === $currentSubjectId) { $currentSubject = $s; break; }
}

// Загружаем темы
$topics = [];
if ($currentSubject) {
    $stmt = $conn->prepare("SELECT id, title, description FROM topics WHERE subject_id = ? ORDER BY id ASC");
    $stmt->bind_param('i', $currentSubjectId); $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $topics[] = $row;
    $stmt->close();
}

/* ---------- Иконка + картинка предмета ---------- */
function subject_visuals(string $name): array {
    $possibleDirs = ['/face/img/icons/', '/public/face/img/icons/', '/img/icons/', '/public/img/icons/', '/icons/'];
    $webDir = '/face/img/icons/'; 
    foreach ($possibleDirs as $dir) {
        $physicalPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $dir;
        if (is_dir($physicalPath) && (file_exists($physicalPath . '3.jpg') || file_exists($physicalPath . '3.png'))) { $webDir = $dir; break; }
    }
    $map = [
        'матем' => ['1.jpg', '1.png'], 'алгебр' => ['1.jpg', '1.png'], 'физик' => ['2.jpg', '2.png'],
        'хими' => ['3.jpg', '3.png'], 'биол' => ['4.jpg', '4.png'], 'истор' => ['5.jpg', '5.png'],
        'информ' => ['6.jpg', '6.png'], 'инос' => ['7.jpg', '7.png'], 'литер' => ['8.jpg', '8.png'],
        'геогр' => ['9.jpg', '9.png'], 'обще' => ['10.jpg', '10.png'],
    ];
    $emojiMap = ['матем'=>'📐', 'алгебр'=>'📐', 'физик'=>'⚡', 'хими'=>'🧪', 'биол'=>'🧬', 'истор'=>'🏛️', 'информ'=>'💻', 'инос'=>'🌐', 'литер'=>'📚', 'геогр'=>'🌍', 'обще'=>'⚖️'];
    $gradMap = ['матем'=>'from-blue-600 to-indigo-800', 'алгебр'=>'from-blue-600 to-indigo-800', 'физик'=>'from-amber-500 to-orange-700', 'хими'=>'from-emerald-500 to-teal-700', 'биол'=>'from-green-500 to-emerald-700', 'истор'=>'from-rose-500 to-red-700', 'информ'=>'from-indigo-500 to-purple-700', 'инос'=>'from-pink-500 to-rose-700', 'литер'=>'from-yellow-500 to-amber-700', 'геогр'=>'from-cyan-500 to-blue-700', 'обще'=>'from-purple-500 to-fuchsia-700'];
    $low = mb_strtolower($name, 'UTF-8');
    foreach ($map as $key => $filenames) {
        if (mb_strpos($low, $key) !== false) {
            $imgUrl = ''; foreach ($filenames as $file) { if (file_exists(($_SERVER['DOCUMENT_ROOT'] ?? '') . $webDir . $file)) { $imgUrl = $webDir . $file; break; } }
            return ['img' => $imgUrl, 'emoji' => $emojiMap[$key] ?? '📘', 'grad' => $gradMap[$key] ?? 'from-gray-500 to-gray-700'];
        }
    }
    return ['img' => '', 'emoji' => '📘', 'grad' => 'from-gray-500 to-gray-700'];
}

// Данные для JS
$allTopics = [];
$rAll = $conn->query("SELECT id, subject_id, title, description FROM topics ORDER BY id ASC");
if ($rAll) { while ($row = $rAll->fetch_assoc()) $allTopics[$row['subject_id']][] = $row; }

$pageTitle  = 'Практика — 3D Учебники';
$activePage = 'hub';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

  <?php if ($isTeacher): ?>
    <!-- ========================================== -->
    <!-- ИНТЕРФЕЙС ДЛЯ УЧИТЕЛЯ (Только его темы) -->
    <!-- ========================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 animate-fade-up">
      <div>
        <h1 class="text-3xl font-black text-ink-900 tracking-tight">Ваши темы</h1>
        <p class="text-gray-500 mt-1 text-sm">
          Предмет: <b class="text-indigo-600"><?= htmlspecialchars($currentSubject['name'] ?? '') ?></b>. 
          Всего тем: <?= count($topics) ?>.
        </p>
      </div>
      <div class="flex gap-2">
        <a href="teacher_content.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center gap-2">
          <i class="fa-solid fa-plus"></i> Добавить тему / задание
        </a>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 animate-fade-up delay-100">
      <?php if (empty($topics)): ?>
        <div class="col-span-full bg-white p-8 rounded-2xl border border-gray-200 text-center">
          <p class="text-gray-500">У вас пока нет тем. Нажмите «Добавить тему», чтобы создать первую.</p>
        </div>
      <?php else: ?>
        <?php foreach ($topics as $t): 
          $v = subject_visuals($currentSubject['name']);
          // Считаем статистику по теме
          $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as students, COUNT(id) as tasks FROM module_results WHERE module_id IN (SELECT id FROM modules WHERE topic_id = ?)");
          $stmt->bind_param('i', $t['id']); $stmt->execute();
          $tStats = $stmt->get_result()->fetch_assoc(); $stmt->close();
        ?>
          <div class="bg-white rounded-3xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-sm">
                  <?= $v['emoji'] ?>
                </div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Тема #<?= $t['id'] ?></span>
              </div>
              <h3 class="text-base font-bold text-ink-900 leading-snug mb-2"><?= htmlspecialchars($t['title']) ?></h3>
              <p class="text-xs text-gray-500 line-clamp-2 mb-4"><?= htmlspecialchars($t['description']) ?></p>
            </div>
            
            <div class="space-y-3 pt-4 border-t border-gray-100">
              <div class="flex items-center justify-between text-xs text-gray-500 font-medium">
                <span><i class="fa-solid fa-users text-indigo-400 mr-1"></i> <?= $tStats['students'] ?> уч.</span>
                <span><i class="fa-solid fa-check text-green-400 mr-1"></i> <?= $tStats['tasks'] ?> реш.</span>
              </div>
              <div class="flex gap-2">
                <a href="topic.php?id=<?= $t['id'] ?>" class="flex-1 py-2 text-center bg-gray-50 text-ink-900 text-xs font-bold rounded-lg hover:bg-gray-100 transition">Теория</a>
                <a href="module.php?topic_id=<?= $t['id'] ?>" class="flex-1 py-2 text-center bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg hover:bg-indigo-100 transition">Задания</a>
                <!-- ИЗМЕНЕНО: Кнопка "Результаты" ведет на teacher_results.php -->
                <a href="teacher_results.php?topic_id=<?= $t['id'] ?>" class="flex-1 py-2 text-center bg-green-50 text-green-700 text-xs font-bold rounded-lg hover:bg-green-100 transition">Результаты</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <!-- ========================================== -->
    <!-- ИНТЕРФЕЙС ДЛЯ УЧЕНИКА (Карусель + Темы) -->
    <!-- ========================================== -->
    <div class="card p-6 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-6 animate-fade-up">
      <div class="space-y-3 max-w-2xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 border border-brand-100 text-xs font-semibold text-brand-500">
          <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
          <?= count($subjects) ?> предметов · учебники · лаборатории
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold text-ink-900 leading-tight">
          Интерактивный <span class="cyber-gradient-text">Учебник 3D</span> и Виртуальная лаборатория
        </h1>
        <p class="text-ink-600 text-sm leading-relaxed">
          Выберите предмет и откройте любую тему — аксиомы, формулы, научный разбор и пошаговые задачи.
        </p>
      </div>
      <div class="flex gap-2 shrink-0">
        <a href="<?= $arenaBase ?>" class="btn-primary px-5 py-3 text-sm flex items-center gap-2"><i class="fa-solid fa-play"></i> Начать практику</a>
        <button onclick="openAiModal()" class="btn-secondary px-5 py-3 text-sm flex items-center gap-2"><i class="fa-solid fa-robot text-brand-500"></i> ИИ-репетитор</button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch animate-fade-up delay-200">
      <div class="lg:col-span-6 card p-6 min-h-[720px] flex flex-col justify-between relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
          <span class="text-xs font-bold uppercase tracking-wider text-ink-400 flex items-center gap-2"><i class="fa-solid fa-book-open"></i> 3D-карусель предметов</span>
          <div class="flex items-center gap-2">
            <button onclick="prevBook()" class="w-9 h-9 rounded-lg bg-surface-alt hover:bg-surface-border text-ink-900 flex items-center justify-center transition"><i class="fa-solid fa-chevron-left"></i></button>
            <button onclick="nextBook()" class="w-9 h-9 rounded-lg bg-surface-alt hover:bg-surface-border text-ink-900 flex items-center justify-center transition"><i class="fa-solid fa-chevron-right"></i></button>
          </div>
        </div>
        <div class="perspective-stage py-12 flex items-center justify-center min-h-[400px] relative flex-grow overflow-hidden" id="stage-books-container"></div>
        <div class="pt-4 border-t border-surface-border flex items-center justify-between text-xs text-ink-400 mt-auto">
          <span id="stage-book-counter">Предмет 1 из <?= count($subjects) ?></span>
          <div class="w-48 h-1.5 bg-surface-alt rounded-full overflow-hidden"><div id="stage-progress-bar" class="h-full bg-brand-500 w-1/10 transition-all duration-300"></div></div>
          <span>Цикл</span>
        </div>
      </div>

      <div class="lg:col-span-6 card p-6 flex flex-col h-[720px]">
        <?php if ($currentSubject): $v = subject_visuals($currentSubject['name']); ?>
          <div id="right-preview-box" class="relative h-72 shrink-0 rounded-xl overflow-hidden border border-surface-border flex items-end p-6 bg-gradient-to-br <?= $v['grad'] ?> mb-5 group">
            <?php if (!empty($v['img'])): ?>
              <div class="absolute inset-0 bg-cover bg-center blur-sm scale-110 opacity-60" style="background-image: url('<?= htmlspecialchars($v['img']) ?>');"></div>
              <img id="right-preview-img" src="<?= htmlspecialchars($v['img']) ?>" alt="" class="absolute inset-0 w-full h-full object-contain p-2 drop-shadow-2xl transition-transform duration-500 group-hover:scale-105 z-0" onerror="this.style.display='none'; document.getElementById('right-preview-fallback').style.display='flex';">
              <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-0"></div>
            <?php endif; ?>
            <div id="right-preview-fallback" class="absolute inset-0 <?= empty($v['img']) ? 'flex' : 'hidden' ?> items-center justify-center bg-gradient-to-br <?= $v['grad'] ?> text-8xl z-0"><?= $v['emoji'] ?></div>
            <div class="relative z-10 w-full">
              <span id="right-subj-category" class="inline-block px-3 py-1 rounded-md bg-white/20 text-white text-[11px] font-bold backdrop-blur-md border border-white/20 mb-1 shadow-sm"><?= htmlspecialchars($currentSubject['category'] ?? 'Предмет') ?></span>
              <h2 id="right-subj-title" class="text-3xl sm:text-4xl font-black text-white leading-tight drop-shadow-xl"><?= htmlspecialchars($currentSubject['name']) ?></h2>
            </div>
          </div>

          <div class="flex flex-col flex-grow min-h-0 mb-5">
            <h4 class="text-xs font-bold text-ink-400 uppercase tracking-wider flex items-center justify-between shrink-0 mb-3">
              <span id="right-topics-count">Темы (<?= count($topics) ?>)</span><i class="fa-solid fa-list-ul text-brand-500"></i>
            </h4>
            <div id="active-subj-topics" class="space-y-2 overflow-y-auto custom-scrollbar pr-2 flex-1"></div>
          </div>

          <a id="right-btn-open-first" href="<?= $topicBase ?>?id=<?= !empty($topics) ? intval($topics[0]['id']) : 0 ?>" class="w-full shrink-0 py-3.5 rounded-lg bg-ink-900 text-white font-semibold text-sm hover:bg-brand-500 transition flex items-center justify-center gap-2 <?= empty($topics) ? 'hidden' : '' ?>"><i class="fa-solid fa-book-bookmark"></i> Открыть первую тему</a>
        <?php else: ?>
          <p class="text-xs text-ink-400 text-center py-4">Выберите предмет из карусели</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</main>

<script>
  window.PRACTICE_SUBJECTS = <?= json_encode(array_map(function($s) { $v = subject_visuals($s['name']); return ['id' => intval($s['id']), 'name' => $s['name'], 'category' => $s['category'] ?? '', 'icon' => $v['emoji'], 'img' => $v['img'], 'grad' => $v['grad']]; }, $subjects)) ?>;
  window.ALL_TOPICS = <?= json_encode($allTopics ?? []) ?>;
  window.PRACTICE_CURRENT_SUBJECT_ID = <?= json_encode($currentSubjectId) ?>;
</script>

<?php require(__DIR__ . "/practice_footer.php"); ?>