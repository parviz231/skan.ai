<?php
require_once(__DIR__ . "/practice_data.php");

/*
 * ============================================================
 * ЛАБОРАТОРИИ PhET (проверенные ссылки с ?locale=ru)
 * ============================================================
 * Убраны ссылки, которые могли вести на "not found".
 * Оставлены только симуляции, подтверждённые на PhET.
 * Если какая-то ссылка всё равно не работает — просто удалите
 * строку с этой симуляцией из массива.
 */
$phet_labs = [
    // ===== ФИЗИКА =====
    ['id' => 1001, 'title' => 'Energy Skate Park: Basics (Энергия на скейт-парке)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/energy-skate-park-basics/latest/energy-skate-park-basics_all.html?locale=ru'],
    ['id' => 1002, 'title' => 'Circuit Construction Kit: DC (Сборка электрических цепей)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/circuit-construction-kit-dc/latest/circuit-construction-kit-dc_all.html?locale=ru'],
    ['id' => 1003, 'title' => 'Forces and Motion: Basics (Силы и движение)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/forces-and-motion-basics/latest/forces-and-motion-basics_all.html?locale=ru'],
    ['id' => 1004, 'title' => 'Projectile Motion (Движение снаряда)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/projectile-motion/latest/projectile-motion_all.html?locale=ru'],
    ['id' => 1005, 'title' => 'Pendulum Lab (Лаборатория маятника)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/pendulum-lab/latest/pendulum-lab_all.html?locale=ru'],
    ['id' => 1006, 'title' => "Ohm's Law (Закон Ома)", 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/ohms-law/latest/ohms-law_all.html?locale=ru'],
    ['id' => 1007, 'title' => 'Bending Light (Преломление света)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/bending-light/latest/bending-light_all.html?locale=ru'],
    ['id' => 1008, 'title' => 'Wave on a String (Волна на струне)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/wave-on-a-string/latest/wave-on-a-string_all.html?locale=ru'],
    ['id' => 1009, 'title' => 'Gas Properties (Свойства газов)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/gas-properties/latest/gas-properties_all.html?locale=ru'],
    ['id' => 1010, 'title' => 'Gravity Force Lab (Лаборатория гравитации)', 'subject' => 'Физика', 'url' => 'https://phet.colorado.edu/sims/html/gravity-force-lab/latest/gravity-force-lab_all.html?locale=ru'],

    // ===== ХИМИЯ =====
    ['id' => 2001, 'title' => 'Build an Atom (Собери атом)', 'subject' => 'Химия', 'url' => 'https://phet.colorado.edu/sims/html/build-an-atom/latest/build-an-atom_all.html?locale=ru'],
    ['id' => 2002, 'title' => 'pH Scale (Шкала pH)', 'subject' => 'Химия', 'url' => 'https://phet.colorado.edu/sims/html/ph-scale/latest/ph-scale_all.html?locale=ru'],
    ['id' => 2003, 'title' => 'pH Scale: Basics (Шкала pH: основы)', 'subject' => 'Химия', 'url' => 'https://phet.colorado.edu/sims/html/ph-scale-basics/latest/ph-scale-basics_all.html?locale=ru'],
    ['id' => 2004, 'title' => 'Molecule Shapes (Формы молекул)', 'subject' => 'Химия', 'url' => 'https://phet.colorado.edu/sims/html/molecule-shapes/latest/molecule-shapes_all.html?locale=ru'],
    ['id' => 2005, 'title' => 'Balancing Chemical Equations (Балансировка уравнений)', 'subject' => 'Химия', 'url' => 'https://phet.colorado.edu/sims/html/balancing-chemical-equations/latest/balancing-chemical-equations_all.html?locale=ru'],
    ['id' => 2006, 'title' => 'Concentration (Концентрация)', 'subject' => 'Химия', 'url' => 'https://phet.colorado.edu/sims/html/concentration/latest/concentration_all.html?locale=ru'],
    ['id' => 2007, 'title' => 'Reactants, Products and Leftovers (Реагенты, продукты и остатки)', 'subject' => 'Химия', 'url' => 'https://phet.colorado.edu/sims/html/reactants-products-and-leftovers/latest/reactants-products-and-leftovers_all.html?locale=ru'],

    // ===== БИОЛОГИЯ =====
    ['id' => 3001, 'title' => 'Natural Selection (Естественный отбор)', 'subject' => 'Биология', 'url' => 'https://phet.colorado.edu/sims/html/natural-selection/latest/natural-selection_all.html?locale=ru'],
    ['id' => 3002, 'title' => 'Gene Expression Essentials (Экспрессия генов)', 'subject' => 'Биология', 'url' => 'https://phet.colorado.edu/sims/html/gene-expression-essentials/latest/gene-expression-essentials_all.html?locale=ru'],
    ['id' => 3003, 'title' => 'Membrane Transport (Транспорт через мембрану)', 'subject' => 'Биология', 'url' => 'https://phet.colorado.edu/sims/html/membrane-transport/latest/membrane-transport_all.html?locale=ru'],
    ['id' => 3004, 'title' => 'Neuron (Нейрон)', 'subject' => 'Биология', 'url' => 'https://phet.colorado.edu/sims/html/neuron/latest/neuron_all.html?locale=ru'],

    // ===== МАТЕМАТИКА =====
    ['id' => 4001, 'title' => 'Graphing Lines (Построение прямых)', 'subject' => 'Математика', 'url' => 'https://phet.colorado.edu/sims/html/graphing-lines/latest/graphing-lines_all.html?locale=ru'],
    ['id' => 4002, 'title' => 'Area Model Algebra (Модель площади: алгебра)', 'subject' => 'Математика', 'url' => 'https://phet.colorado.edu/sims/html/area-model-algebra/latest/area-model-algebra_all.html?locale=ru'],
    ['id' => 4003, 'title' => 'Fractions Intro (Дроби: введение)', 'subject' => 'Математика', 'url' => 'https://phet.colorado.edu/sims/html/fractions-intro/latest/fractions-intro_all.html?locale=ru'],
    ['id' => 4004, 'title' => 'Equality Explorer: Basics (Исследователь равенств)', 'subject' => 'Математика', 'url' => 'https://phet.colorado.edu/sims/html/equality-explorer-basics/latest/equality-explorer-basics_all.html?locale=ru'],

    // ===== НАУКИ О ЗЕМЛЕ =====
    ['id' => 5001, 'title' => 'Greenhouse Effect (Парниковый эффект)', 'subject' => 'Науки о Земле', 'url' => 'https://phet.colorado.edu/sims/html/greenhouse-effect/latest/greenhouse-effect_all.html?locale=ru'],
    ['id' => 5002, 'title' => 'Plate Tectonics (Тектоника плит)', 'subject' => 'Науки о Земле', 'url' => 'https://phet.colorado.edu/sims/html/plate-tectonics/latest/plate-tectonics_all.html?locale=ru'],
    ['id' => 5003, 'title' => 'Glaciers (Ледники)', 'subject' => 'Науки о Земле', 'url' => 'https://phet.colorado.edu/sims/html/glaciers/latest/glaciers_all.html?locale=ru'],
    ['id' => 5004, 'title' => 'Radioactive Dating Game (Радиоактивное датирование)', 'subject' => 'Науки о Земле', 'url' => 'https://phet.colorado.edu/sims/html/radioactive-dating-game/latest/radioactive-dating-game_all.html?locale=ru'],
    ['id' => 5005, 'title' => 'Gravity and Orbits (Гравитация и орбиты)', 'subject' => 'Науки о Земле', 'url' => 'https://phet.colorado.edu/sims/html/gravity-and-orbits/latest/gravity-and-orbits_all.html?locale=ru'],
];

// Локальные лаборатории (если есть в practice_data.php)
$localLabs = [];
if (isset($practice_data) && is_array($practice_data)) {
    foreach ($practice_data as $subjectKey => $labsInSubject) {
        if (!is_array($labsInSubject)) continue;
        foreach ($labsInSubject as $labId => $lab) {
            $localLabs[] = [
                'id'           => (int)$labId,
                'title'        => $lab['title']   ?? ('Лаборатория #' . $labId),
                'subject_name' => $lab['subject'] ?? $subjectKey,
                'url'          => null,
            ];
        }
    }
}

$allLabs = array_merge($localLabs, $phet_labs);
usort($allLabs, function ($a, $b) {
    $subjA = $a['subject_name'] ?? $a['subject'] ?? '';
    $subjB = $b['subject_name'] ?? $b['subject'] ?? '';
    $cmp = strcmp($subjA, $subjB);
    return $cmp !== 0 ? $cmp : ($a['id'] <=> $b['id']);
});

function get_lab_image($subjectName) {
    $name = mb_strtolower((string)$subjectName, 'UTF-8');
    if (mb_strpos($name, 'физик') !== false) return 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?q=80&w=800&auto=format&fit=crop';
    if (mb_strpos($name, 'хими') !== false) return 'https://images.unsplash.com/photo-1554475900-0a0350e3fc7b?q=80&w=800&auto=format&fit=crop';
    if (mb_strpos($name, 'биол') !== false) return 'https://images.unsplash.com/photo-1628595351029-c2bf17511435?q=80&w=800&auto=format&fit=crop';
    if (mb_strpos($name, 'геогр') !== false || mb_strpos($name, 'земл') !== false) return 'https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?q=80&w=800&auto=format&fit=crop';
    if (mb_strpos($name, 'матем') !== false || mb_strpos($name, 'алгебр') !== false) return 'https://images.unsplash.com/photo-1509228468518-180dd4864904?q=80&w=800&auto=format&fit=crop';
    return 'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?q=80&w=800&auto=format&fit=crop';
}

function get_subject_fallback($subjectName) {
    $name = mb_strtolower((string)$subjectName, 'UTF-8');
    if (mb_strpos($name, 'физик') !== false) return ['icon' => 'fa-atom', 'grad' => 'from-amber-400 to-orange-500'];
    if (mb_strpos($name, 'хими') !== false) return ['icon' => 'fa-flask', 'grad' => 'from-emerald-400 to-teal-500'];
    if (mb_strpos($name, 'биол') !== false) return ['icon' => 'fa-dna', 'grad' => 'from-green-400 to-emerald-600'];
    if (mb_strpos($name, 'геогр') !== false || mb_strpos($name, 'земл') !== false) return ['icon' => 'fa-globe', 'grad' => 'from-sky-400 to-blue-600'];
    if (mb_strpos($name, 'матем') !== false || mb_strpos($name, 'алгебр') !== false) return ['icon' => 'fa-calculator', 'grad' => 'from-blue-500 to-indigo-600'];
    return ['icon' => 'fa-flask', 'grad' => 'from-gray-500 to-gray-700'];
}

$pageTitle  = 'Все лаборатории';
$activePage = 'labs';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
  <div class="animate-fade-up flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-xs font-semibold text-indigo-600 mb-3">
        <i class="fa-solid fa-flask"></i> Интерактивные эксперименты
      </div>
      <h1 class="text-3xl sm:text-4xl font-black text-ink-900 tracking-tight">Виртуальные лаборатории</h1>
      <p class="text-gray-500 mt-2 text-sm">Проводите опыты, меняйте параметры и наблюдайте за результатами в реальном времени.</p>
    </div>
    <div class="bg-white px-4 py-2 rounded-xl border border-gray-200 shadow-sm text-sm font-bold text-ink-900 shrink-0">
      Доступно: <span class="text-indigo-600"><?= count($allLabs) ?></span>
    </div>
  </div>

  <?php if (empty($allLabs)): ?>
      <div class="bg-white p-8 rounded-2xl border border-gray-200 text-center animate-fade-up delay-100">
          <p class="text-gray-500">Пока нет доступных лабораторий.</p>
      </div>
  <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <?php $delay = 100; foreach ($allLabs as $t): $delay += 50;
          $subject    = $t['subject_name'] ?? $t['subject'] ?? 'Предмет';
          $imgUrl     = get_lab_image($subject);
          $fallback   = get_subject_fallback($subject);
          $isPhet     = !empty($t['url']);
          $href       = $isPhet ? $t['url'] : 'lab.php?id=' . (int)($t['id'] ?? 0);
          $target     = $isPhet ? ' target="_blank" rel="noopener"' : '';
          $fallbackId = 'fallback-' . (int)($t['id'] ?? 0);
        ?>
          <a href="<?= htmlspecialchars($href) ?>"<?= $target ?> class="group bg-white rounded-3xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col animate-fade-up" style="animation-delay: <?= $delay ?>ms;">
            <div class="relative h-36 overflow-hidden bg-gray-100">
              <div id="<?= $fallbackId ?>" class="absolute inset-0 bg-gradient-to-br <?= $fallback['grad'] ?> flex items-center justify-center text-white text-5xl opacity-0 transition-opacity duration-300">
                <i class="fa-solid <?= $fallback['icon'] ?>"></i>
              </div>
              <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($t['title']) ?>" class="relative z-10 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.style.display='none'; document.getElementById('<?= $fallbackId ?>').style.opacity='1';">
              <div class="absolute inset-0 z-20 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
              <div class="absolute bottom-3 left-3 z-30">
                <span class="px-2.5 py-1 rounded-md bg-white/20 text-white text-[10px] font-bold backdrop-blur-md border border-white/20 shadow-sm uppercase tracking-wide"><?= htmlspecialchars($subject) ?></span>
              </div>
            </div>
            <div class="p-5 flex flex-col flex-grow">
              <h3 class="text-sm font-bold text-ink-900 leading-snug group-hover:text-indigo-600 transition-colors flex-grow mb-4">
                <?= htmlspecialchars($t['title']) ?>
                <?php if ($isPhet): ?><span class="ml-1 text-[10px] font-normal text-indigo-500">(PhET)</span><?php endif; ?>
              </h3>
              <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs font-bold text-gray-400 group-hover:text-indigo-600 transition-colors">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-play text-[10px]"></i> Запустить</span>
                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
  <?php endif; ?>
</main>

<?php require(__DIR__ . "/practice_footer.php"); ?>