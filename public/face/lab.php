<?php
require_once(__DIR__ . "/practice_data.php");

$topicId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($topicId === 0) { header("Location: labs.php"); exit; }

$labs = [
    1  => ['url' => 'https://phet.colorado.edu/sims/html/graphing-quadratics/latest/graphing-quadratics_ru.html', 'title' => 'Графики квадратичных функций'],
    4  => ['url' => 'https://phet.colorado.edu/sims/html/trig-tour/latest/trig-tour_ru.html', 'title' => 'Тригонометрия: единичная окружность'],
    11 => ['url' => 'https://phet.colorado.edu/sims/html/circuit-construction-kit-dc/latest/circuit-construction-kit-dc_ru.html', 'title' => 'Электрические цепи (Закон Ома)'],
    12 => ['url' => 'https://phet.colorado.edu/sims/html/forces-and-motion-basics/latest/forces-and-motion-basics_ru.html', 'title' => 'Силы и движение (Законы Ньютона)'],
    14 => ['url' => 'https://phet.colorado.edu/sims/html/energy-skate-park-basics/latest/energy-skate-park-basics_ru.html', 'title' => 'Энергия'],
    15 => ['url' => 'https://phet.colorado.edu/sims/html/gases-intro/latest/gases-intro_ru.html', 'title' => 'Свойства газов'],
    19 => ['url' => 'https://phet.colorado.edu/sims/html/bending-light/latest/bending-light_ru.html', 'title' => 'Преломление света'],
    22 => ['url' => 'https://phet.colorado.edu/sims/html/build-an-atom/latest/build-an-atom_ru.html', 'title' => 'Строение атома'],
    23 => ['url' => 'https://phet.colorado.edu/sims/html/molecule-shapes/latest/molecule-shapes_ru.html', 'title' => 'Формы молекул'],
    26 => ['url' => 'https://phet.colorado.edu/sims/html/concentration/latest/concentration_ru.html', 'title' => 'Концентрация растворов'],
    31 => ['url' => 'https://phet.colorado.edu/sims/html/gene-expression-essentials/latest/gene-expression-essentials_ru.html', 'title' => 'Экспрессия генов'],
    37 => ['url' => 'https://phet.colorado.edu/sims/html/neuron/latest/neuron_ru.html', 'title' => 'Нейрон'],
];

$stmt = $conn->prepare("SELECT t.title, s.name AS subject_name FROM topics t JOIN subjects s ON s.id = t.subject_id WHERE t.id = ? LIMIT 1");
$stmt->bind_param('i', $topicId); $stmt->execute();
$topic = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$topic) { die("Тема не найдена"); }

$hasLab = isset($labs[$topicId]);
$currentLab = $hasLab ? $labs[$topicId] : null;

$pageTitle = 'Лаборатория — ' . $topic['title'];
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 animate-fade-up">
    
    <div class="flex items-center justify-between">
        <a href="labs.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-ink-900 font-medium transition">
          <i class="fa-solid fa-arrow-left"></i> Все лаборатории
        </a>
        <a href="topic.php?id=<?= $topicId ?>" class="text-sm text-brand-500 font-bold hover:underline">
            Перейти к теории
        </a>
    </div>

    <?php if ($hasLab): ?>
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden flex flex-col relative w-full h-[75vh] min-h-[600px]">
            
            <div class="absolute top-4 left-4 z-10 bg-white/90 backdrop-blur-md px-4 py-3 rounded-2xl shadow-lg border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-flask"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block"><?= htmlspecialchars($topic['subject_name']) ?></span>
                    <h1 class="text-sm font-bold text-ink-900 leading-tight"><?= $currentLab['title'] ?></h1>
                </div>
            </div>

            <div class="absolute top-4 right-4 z-10">
                <a href="<?= $currentLab['url'] ?>" target="_blank" class="flex items-center gap-2 px-4 py-2.5 bg-ink-900 hover:bg-gray-800 text-white text-xs font-bold rounded-xl shadow-lg transition hover:-translate-y-0.5">
                    <i class="fa-solid fa-expand"></i> На весь экран
                </a>
            </div>

            <div class="w-full h-full relative bg-gray-50">
                <iframe src="<?= $currentLab['url'] ?>" width="100%" height="100%" allowfullscreen class="absolute inset-0 border-0"></iframe>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-white rounded-3xl border border-gray-200 p-8 sm:p-12 shadow-sm text-center space-y-6 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-amber-400 to-orange-500"></div>
            
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-amber-50 text-amber-500 text-5xl mb-4">
                <i class="fa-solid fa-flask-vial"></i>
            </div>

            <div class="space-y-3">
                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[11px] font-bold uppercase tracking-wider"><?= htmlspecialchars($topic['subject_name']) ?></span>
                <h1 class="text-2xl sm:text-3xl font-black text-ink-900">Для этой темы нет лаборатории</h1>
                <p class="text-gray-500 text-sm max-w-md mx-auto">Тема: <?= htmlspecialchars($topic['title']) ?></p>
            </div>

            <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="topic.php?id=<?= $topicId ?>" class="px-6 py-3 rounded-xl bg-ink-900 text-white text-sm font-bold hover:bg-gray-800 transition">
                    Вернуться к теории
                </a>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>