<?php
require_once(__DIR__ . "/practice_data.php");

// Текущий предмет из URL (?subject=ID) или первый из БД
$currentSubjectId = isset($_GET['subject']) ? intval($_GET['subject']) : 0;

// Все предметы
$subjects = [];
$res = $conn->query("SELECT id, name, category FROM subjects ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) $subjects[] = $row;
}

if ($currentSubjectId === 0 && !empty($subjects)) {
    $currentSubjectId = intval($subjects[0]['id']);
}

// Темы текущего предмета (для ссылки на теорию)
$topics = [];
if ($currentSubjectId > 0) {
    $stmt = $conn->prepare("SELECT id, title FROM topics WHERE subject_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param('i', $currentSubjectId);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $topics[] = $row;
    $stmt->close();
}

$firstTopicId = !empty($topics) ? intval($topics[0]['id']) : 0;

$pageTitle  = 'Практика — Арена Задач';
$activePage = 'arena';
require(__DIR__ . "/practice_header.php");
?>

<main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 animate-fade-up">

  <div class="card p-6 flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl">⚡</div>
      <div>
        <h2 class="text-lg font-bold text-ink-900">Арена задач</h2>
        <p class="text-xs text-ink-600">Интересные задачи и загадки на смекалку</p>
      </div>
    </div>

    <div class="relative w-full md:w-72">
      <label class="text-[10px] text-ink-400 font-bold uppercase block mb-1">Категория:</label>
      <button id="custom-dropdown-btn" class="w-full bg-white border border-surface-border2 rounded-lg px-4 py-2.5 text-sm text-ink-900 flex items-center justify-between font-medium">
        <span id="custom-dropdown-selected" class="flex items-center gap-2">
          <span id="custom-subj-icon">📐</span>
          <span id="custom-subj-name">—</span>
        </span>
        <i class="fa-solid fa-chevron-down text-ink-400" id="custom-dropdown-arrow"></i>
      </button>
      <div id="custom-dropdown-menu" class="hidden absolute left-0 right-0 top-full mt-2 z-50 bg-white border border-surface-border rounded-xl shadow-lg max-h-72 overflow-y-auto custom-scrollbar p-1.5 space-y-0.5"></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-fade-up delay-100">
    
    <!-- Основная панель с задачей -->
    <div class="lg:col-span-8 card p-6 space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-surface-border">
        <span id="arena-task-number" class="px-2.5 py-1 rounded-md bg-brand-50 text-brand-500 text-xs font-semibold">Задача 1 из 10</span>
        <span id="arena-task-reward" class="text-xs text-amber-500 font-semibold"><i class="fa-solid fa-bolt mr-1"></i>+50 XP</span>
      </div>

      <div class="space-y-3">
        <h3 id="arena-task-title" class="text-base font-bold text-ink-900 text-lg leading-relaxed">Загрузка...</h3>
        <div id="arena-task-details" class="p-4 rounded-lg bg-surface-alt text-sm text-ink-600 whitespace-pre-line leading-relaxed"></div>
      </div>

      <div class="space-y-3">
        <label class="block text-xs font-bold text-ink-400 uppercase">Ваш ответ:</label>
        <div id="arena-input-container"></div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
          <button onclick="toggleArenaHint()" class="text-xs text-brand-500 hover:underline font-medium">
            <i class="fa-regular fa-lightbulb"></i> Подсказка
          </button>
          <div class="flex gap-2">
            <button onclick="checkArenaAnswer()" class="btn-primary px-5 py-2.5 text-xs flex items-center gap-2">
              <i class="fa-solid fa-check"></i> Проверить
            </button>
            <button onclick="nextArenaTask()" class="btn-secondary px-4 py-2.5 text-xs">
              Следующая <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <div id="arena-hint-box" class="hidden p-3 rounded-lg bg-violet-50 border border-violet-200 text-xs text-violet-700">
          <span class="font-bold block mb-1">💡 Подсказка:</span>
          <p id="arena-hint-text"></p>
        </div>

        <div id="arena-feedback-box" class="hidden p-3 rounded-lg text-xs font-semibold flex items-center gap-2"></div>
      </div>
    </div>

    <!-- Боковая панель -->
    <div class="lg:col-span-4 space-y-4">
      <div class="card p-6 space-y-3">
        <h4 class="text-xs font-bold text-ink-400 uppercase">Прогресс сессии</h4>
        <div class="flex justify-between text-xs">
          <span class="text-ink-600">Решено задач</span>
          <span id="arena-progress-text" class="text-ink-900 font-bold">0 / 0</span>
        </div>
        <div class="w-full h-2 bg-surface-alt rounded-full overflow-hidden">
          <div id="arena-progress-bar" class="h-full bg-brand-500 w-0 transition-all"></div>
        </div>
        <p class="text-[10px] text-ink-400 mt-2">Решайте задачи на время или просто для развлечения!</p>
      </div>

      <?php if ($firstTopicId > 0): ?>
      <a href="topic.php?id=<?= $firstTopicId ?>" class="card p-4 flex items-center gap-3 hover:shadow-md transition">
        <i class="fa-solid fa-book-bookmark text-brand-500"></i>
        <div class="min-w-0">
          <p class="text-[10px] text-ink-400 uppercase font-bold">Перейти к учебе</p>
          <p class="text-xs font-semibold text-ink-900 truncate">Открыть теорию предмета</p>
        </div>
      </a>
      <?php endif; ?>
    </div>
  </div>

</main>

<script>
// БОЛЬШАЯ БАЗА ЗАДАЧ (НЕ пересекается с тестами)
// Формат: { id, subject, title, text, answer, hint }
const ARENA_TASKS = [
  // --- ЛОГИКА И СМЕКАЛКА (Подходит для всех) ---
  { id: 1, subject: 'logic', title: 'Загадка про время', text: 'Что можно сломать, даже не прикасаясь к этому?', answer: 'тишину|молчание', hint: 'Это нельзя потрогать, но можно нарушить.' },
  { id: 2, subject: 'logic', title: 'Сложная ситуация', text: 'Вы зашли в темную комнату со спичкой. В ней есть масляная лампа, свеча и камин. Что вы зажжете в первую очередь?', answer: 'спичку|спичка', hint: 'Без этого остальное не загорится.' },
  { id: 3, subject: 'logic', title: 'Математическая шутка', text: 'Сколько месяцев в году имеют 28 дней?', answer: '12|двенадцать|все', hint: 'Подумайте, в каком месяце НЕ бывает 28-го числа.' },
  { id: 4, subject: 'logic', title: 'Профессии', text: 'Человек, который может перепрыгнуть через забор высотой 3 метра, но не может перепрыгнуть через монетку. Кто он?', answer: 'человек|все|обычный', hint: 'Монетка лежит на земле, через нее нельзя перепрыгнуть, через нее можно только перешагнуть.' },
  { id: 5, subject: 'logic', title: 'Парадокс', text: 'Что принадлежит вам, но другие используют это чаще, чем вы?', answer: 'имя|ваше имя', hint: 'Как к вам обращаются люди?' },
  { id: 6, subject: 'logic', title: 'Вода и лед', text: 'В каком стакане вода никогда не замерзнет, даже если поставить его в морозилку?', answer: 'в пустом|пустой', hint: 'А что, если воды там нет?' },
  { id: 7, subject: 'logic', title: 'Движение', text: 'Что всегда идет, но никогда не останавливается и не двигается с места?', answer: 'время|часы', hint: 'Это нельзя остановить.' },
  { id: 8, subject: 'logic', title: 'Размеры', text: 'Что становится больше, если его перевернуть вверх дном?', answer: 'число 6|цифра 6|шестерка', hint: 'Переверните цифру 6, что получится?' },
  
  // --- МАТЕМАТИКА (На логику и нестандарт) ---
  { id: 10, subject: 'math', title: 'Загадка с цифрами', text: 'Расставьте знаки действий (+, -, *, /) между цифрами 5 5 5 5 так, чтобы получилось 100.', answer: '(5+5)*(5+5)|5*5*5-5*5', hint: 'Попробуйте сложить числа, а потом умножить.' },
  { id: 11, subject: 'math', title: 'Возраст', text: 'Сколько лет исполнится мальчику в 2025 году, если он родился в 2015 году?', answer: '10', hint: 'Вычтите год рождения из текущего.' },
  { id: 12, subject: 'math', title: 'Неравенство', text: 'Какой знак нужно поставить между 6 и 7, чтобы число было больше 6, но меньше 7?', answer: 'запятую|,|точку|.', hint: 'Это десятичная дробь.' },
  { id: 13, subject: 'math', title: 'Сколько будет?', text: 'Сколько будет, если сложить все числа от 1 до 100?', answer: '5050', hint: 'Используйте формулу Гаусса: (1+100) * 50.' },
  { id: 14, subject: 'math', title: 'Животные', text: 'У фермера было 10 коров. Все, кроме 9, умерли. Сколько осталось?', answer: '9', hint: 'Прочитайте внимательно: "все, кроме 9".' },
  { id: 15, subject: 'math', title: 'Бревно', text: 'Бревно распилили на 4 части. Сколько распилов сделали?', answer: '3', hint: 'Один распил добавляет одну часть. Было 1, стало 4.' },
  { id: 16, subject: 'math', title: 'Задача с монетами', text: 'У вас есть 2 монеты, которые в сумме дают 15 рублей. Одна из них не 5 рублей. Какие это монеты?', answer: '10 и 5|5 и 10', hint: 'Одна не 5, значит 5 — это другая монета.' },

  // --- ФИЗИКА (Космос, явления, загадки) ---
  { id: 20, subject: 'physics', title: 'Невесомость', text: 'Почему космонавты в космосе находятся в состоянии невесомости?', answer: 'свободное падение|падают|гравитация', hint: 'Они постоянно падают на Землю, но не могут упасть.' },
  { id: 21, subject: 'physics', title: 'Свет', text: 'Что распространяется быстрее: свет или звук?', answer: 'свет', hint: 'Скорость света 300 000 км/с.' },
  { id: 22, subject: 'physics', title: 'Кипение', text: 'При какой температуре кипит вода на высоте 3000 метров?', answer: 'меньше 100|90|80', hint: 'С высотой давление падает, и вода закипает раньше.' },
  { id: 23, subject: 'physics', title: 'Электричество', text: 'Почему птицы могут сидеть на высоковольтных проводах и не получать удар током?', answer: 'нет замыкания|одна точка|ток не идет', hint: 'Ток идет только по пути от высокого напряжения к низкому.' },
  { id: 24, subject: 'physics', title: 'Плотность', text: 'Почему корабль из железа не тонет, хотя железо тяжелее воды?', answer: 'выталкивает|воздух|плотность|архимед', hint: 'Внутри корабля есть воздух.' },

  // --- ИНФОРМАТИКА (IT и алгоритмы) ---
  { id: 30, subject: 'it', title: 'Кодинг', text: 'В Python: если x = 5, то чему равно выражение x ** 2?', answer: '25', hint: 'Это возведение в степень.' },
  { id: 31, subject: 'it', title: 'Логика', text: 'В двоичной системе 1010 — это какое число в десятичной?', answer: '10', hint: 'Считаем: 1*8 + 0*4 + 1*2 + 0*1.' },
  { id: 32, subject: 'it', title: 'Строки', text: 'Сколько символов в строке Python "Hello"?', answer: '5', hint: 'Посчитайте буквы.' },
  { id: 33, subject: 'it', title: 'Что выведет?', text: 'Что выведет код: print(len([1, 2, 3, 4]))?', answer: '4', hint: 'len() возвращает длину списка.' },
  { id: 34, subject: 'it', title: 'Хакеры', text: 'Как называется человек, который ищет уязвимости в системе для ее защиты?', answer: 'белый хакер|whitehat|пентестер', hint: 'Они не злодеи, они защитники.' },

  // --- ИСТОРИЯ / ГУМАНИТАРНЫЕ (Забавные факты) ---
  { id: 40, subject: 'history', title: 'Факт', text: 'Как назывался первый в мире компьютерный вирус, созданный в 1971 году?', answer: 'creaper|creeper', hint: 'Название намекает на то, что он "ползет".' },
  { id: 41, subject: 'history', title: 'Ошибка', text: 'Почему високосный год имеет 366 дней?', answer: 'земля|вращение|лишний', hint: 'Земля делает оборот чуть больше, чем за 365 дней.' },
  { id: 42, subject: 'history', title: 'Космос', text: 'Кто первым в истории человечества сказал: "Это один маленький шаг для человека, но гигантский скачок для человечества"?', answer: 'нил армстронг|армстронг|neil', hint: 'Он был первым на Луне.' },

  // --- ОБЩЕСТВОЗНАНИЕ / ПРАВО (Задачки на логику) ---
  { id: 50, subject: 'social', title: 'Права', text: 'С какого возраста в России человек получает паспорт?', answer: '14|14 лет|четырнадцать', hint: 'Вы уже почти взрослый.' },
  { id: 51, subject: 'social', title: 'Конституция', text: 'Какой документ является основным законом страны?', answer: 'конституция', hint: 'В этот документ вносят поправки.' },
  { id: 52, subject: 'social', title: 'Символы', text: 'Как называется главная песня страны, исполняемая на торжественных мероприятиях?', answer: 'гимн', hint: 'Его поют стоя.' },

  // --- БИОЛОГИЯ (Загадки о природе) ---
  { id: 60, subject: 'biology', title: 'Сердце', text: 'Сколько камер в сердце человека?', answer: '4|четыре', hint: 'Два предсердия и два желудочка.' },
  { id: 61, subject: 'biology', title: 'ДНК', text: 'Как расшифровывается аббревиатура ДНК?', answer: 'дезоксирибонуклеиновая кислота', hint: 'Очень длинное название.' },
  { id: 62, subject: 'biology', title: 'Клетки', text: 'Какой органоид клетки называют "энергетической станцией"?', answer: 'митохондрия', hint: 'Там вырабатывается энергия.' },

  // --- ГЕОГРАФИЯ (О странах и местах) ---
  { id: 70, subject: 'geo', title: 'Страны', text: 'Самая большая страна в мире по площади?', answer: 'россия|russia', hint: 'Она находится сразу в двух частях света.' },
  { id: 71, subject: 'geo', title: 'Глубина', text: 'Какой океан самый глубокий в мире?', answer: 'тихий|pacific', hint: 'В нем находится Марианская впадина.' },
  { id: 72, subject: 'geo', title: 'Столицы', text: 'Какой город является столицей Австралии?', answer: 'канберра|canberra', hint: 'Это не Сидней и не Мельбурн.' },
];

// Текущая выбранная категория
const urlParams = new URLSearchParams(window.location.search);
let currentCategory = urlParams.get('subject') || 'logic'; 
let filteredTasks = [];
let currentTaskIdx = 0;
let solvedCount = 0;

const catMap = {
  '1': 'math', '2': 'physics', '3': 'chemistry', '4': 'biology', 
  '5': 'history', '6': 'it', '7': 'lang', '8': 'literature', 
  '9': 'geo', '10': 'social', 'logic': 'logic'
};
let activeCat = catMap[currentCategory] || 'logic';

function setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function playClick() { try { const ctx = new (window.AudioContext || window.webkitAudioContext)(); const osc = ctx.createOscillator(); const gain = ctx.createGain(); osc.connect(gain); gain.connect(ctx.destination); osc.type = 'sine'; osc.frequency.setValueAtTime(800, ctx.currentTime); gain.gain.setValueAtTime(0.04, ctx.currentTime); gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.04); osc.start(); osc.stop(ctx.currentTime + 0.04); } catch (e) {} }
function playSuccess() { try { const ctx = new (window.AudioContext || window.webkitAudioContext)(); const osc = ctx.createOscillator(); const gain = ctx.createGain(); osc.connect(gain); gain.connect(ctx.destination); osc.type = 'triangle'; osc.frequency.setValueAtTime(523.25, ctx.currentTime); osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1); gain.gain.setValueAtTime(0.15, ctx.currentTime); gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35); osc.start(); osc.stop(ctx.currentTime + 0.35); } catch (e) {} }

function renderTask() {
  if (!filteredTasks.length) {
    setText('arena-task-title', 'Задачи для этой категории пока не добавлены.');
    return;
  }
  const t = filteredTasks[currentTaskIdx];
  setText('arena-task-number', `Задача ${currentTaskIdx + 1} из ${filteredTasks.length}`);
  setText('arena-task-title', t.title);
  setText('arena-task-details', t.text);
  setText('arena-hint-text', t.hint);
  
  document.getElementById('arena-hint-box').classList.add('hidden');
  document.getElementById('arena-feedback-box').classList.add('hidden');
  
  const ic = document.getElementById('arena-input-container');
  ic.innerHTML = `<input type="text" id="arena-user-ans" placeholder="Введите ваш ответ..." class="w-full bg-white border border-surface-border2 rounded-lg px-4 py-3 text-sm text-ink-900 focus:outline-none focus:border-brand-500 font-mono" autocomplete="off">`;
  
  setText('arena-progress-text', `${solvedCount} / ${filteredTasks.length}`);
  const bar = document.getElementById('arena-progress-bar');
  if (bar) bar.style.width = `${(solvedCount / filteredTasks.length) * 100}%`;
}

function checkAnswer() {
  const t = filteredTasks[currentTaskIdx];
  if (!t) return;
  const input = document.getElementById('arena-user-ans');
  if (!input) return;
  const val = (input.value || '').trim().toLowerCase().replace(/ё/g, 'е');
  if (!val) return;

  const fb = document.getElementById('arena-feedback-box');
  fb.classList.remove('hidden');

  // Разбиваем правильные ответы по символу | (на случай нескольких вариантов)
  const correctVariants = t.answer.toLowerCase().split('|').map(s => s.trim().replace(/ё/g, 'е'));
  
  let correct = false;
  for (let c of correctVariants) {
    if (val === c || val.includes(c) || c.includes(val)) { correct = true; break; }
  }

  if (correct) {
    fb.className = 'p-4 rounded-lg text-sm font-semibold flex items-center gap-2 bg-green-50 text-green-700 border border-green-200';
    fb.innerHTML = `<i class="fa-solid fa-circle-check text-lg"></i> <div>Верно! Отличная работа. +50 XP</div>`;
    playSuccess();
    if (!t.solved) { t.solved = true; solvedCount++; }
    setText('arena-progress-text', `${solvedCount} / ${filteredTasks.length}`);
    const bar = document.getElementById('arena-progress-bar');
    if (bar) bar.style.width = `${(solvedCount / filteredTasks.length) * 100}%`;
  } else {
    fb.className = 'p-4 rounded-lg text-sm font-semibold flex items-center gap-2 bg-red-50 text-red-700 border border-red-200';
    fb.innerHTML = `<i class="fa-solid fa-circle-xmark text-lg"></i> <div>Неверно. Попробуйте ещё раз или откройте подсказку.</div>`;
  }
}

function nextTask() { 
  playClick(); 
  if (!filteredTasks.length) return; 
  currentTaskIdx = (currentTaskIdx + 1) % filteredTasks.length; 
  renderTask(); 
}

function toggleHint() { 
  playClick(); 
  const el = document.getElementById('arena-hint-box'); 
  if (el) el.classList.toggle('hidden'); 
}

function buildDropdown() {
  const menu = document.getElementById('custom-dropdown-menu');
  if (!menu) return;
  
  const categories = [
    { id: 'logic', name: 'Логика и загадки', icon: '🧠', cat: 'Смекалка' },
    { id: '1', name: 'Математика', icon: '📐', cat: 'Точные науки' },
    { id: '6', name: 'Информатика', icon: '💻', cat: 'IT' },
    { id: '2', name: 'Физика', icon: '⚡', cat: 'Точные науки' },
    { id: '4', name: 'Биология', icon: '🧬', cat: 'Естественные' },
    { id: '9', name: 'География', icon: '🌍', cat: 'Естественные' },
    { id: '5', name: 'История', icon: '🏛️', cat: 'Гуманитарные' },
    { id: '10', name: 'Обществознание', icon: '⚖️', cat: 'Гуманитарные' }
  ];

  menu.innerHTML = '';
  categories.forEach(c => {
    const item = document.createElement('a');
    item.href = `?subject=${c.id}`;
    item.className = 'w-full p-2.5 rounded-lg hover:bg-brand-50 flex items-center justify-between text-xs text-ink-900 transition font-medium cursor-pointer';
    item.innerHTML = `<span class="flex items-center gap-2"><span>${c.icon}</span><span>${c.name}</span></span><span class="text-[9px] px-2 py-0.5 rounded bg-surface-alt text-ink-600 font-mono">${c.cat}</span>`;
    menu.appendChild(item);
  });

  const cur = categories.find(c => c.id === currentCategory) || categories[0];
  setText('custom-subj-icon', cur.icon);
  setText('custom-subj-name', cur.name);
}

function toggleDropdown() { 
  playClick(); 
  const m = document.getElementById('custom-dropdown-menu'); 
  const a = document.getElementById('custom-dropdown-arrow'); 
  if (m) m.classList.toggle('hidden'); 
  if (a) a.classList.toggle('rotate-180'); 
}

function init() {
  buildDropdown();
  
  // Фильтруем задачи по категории
  filteredTasks = ARENA_TASKS.filter(t => t.subject === activeCat);
  if (filteredTasks.length === 0) filteredTasks = ARENA_TASKS; // Если пусто, показываем все
  
  renderTask();

  const btn = document.getElementById('custom-dropdown-btn');
  if (btn) btn.addEventListener('click', toggleDropdown);
  
  document.addEventListener('click', function(e) {
    const menu = document.getElementById('custom-dropdown-menu');
    const btn = document.getElementById('custom-dropdown-btn');
    if (!menu || !btn) return;
    if (!btn.contains(e.target) && !menu.contains(e.target)) { 
      menu.classList.add('hidden'); 
      const a = document.getElementById('custom-dropdown-arrow'); 
      if (a) a.classList.remove('rotate-180'); 
    }
  });
  
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target && e.target.id === 'arena-user-ans') { 
      e.preventDefault(); 
      checkAnswer(); 
    }
  });
}

window.toggleCustomDropdown = toggleDropdown;
window.toggleArenaHint = toggleHint;
window.nextArenaTask = nextTask;
window.checkArenaAnswer = checkAnswer;

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
</script>

<?php require(__DIR__ . "/practice_footer.php"); ?>