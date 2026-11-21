<?php require_once(__DIR__ . "/practice_data.php"); ?>

<!-- TOAST УВЕДОМЛЕНИЕ -->
<div id="toast-notify" class="fixed bottom-6 right-6 z-50 hidden bg-ink-900 text-white px-5 py-3 rounded-lg shadow-xl flex items-center gap-3 text-sm font-medium transition-all duration-300 transform translate-y-10 opacity-0">
  <i class="fa-solid fa-circle-check text-green-400 text-lg"></i>
  <span id="toast-msg">Сохранено!</span>
</div>

<!-- МОДАЛЬНОЕ ОКНО ПОРТФЕЛЯ -->
<div id="modal-bag" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl p-6 space-y-4">
    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-xl">🎒</div>
        <div>
          <h3 class="text-lg font-bold text-ink-900">Мой Портфель</h3>
          <p class="text-xs text-ink-400">Сохранённые темы</p>
        </div>
      </div>
      <button onclick="closeBagModal()" class="w-8 h-8 rounded-full hover:bg-gray-100 text-ink-400 flex items-center justify-center transition">✕</button>
    </div>
    
    <div id="bag-items-list" class="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-2"></div>
    
    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
      <button onclick="clearBag()" class="text-xs text-red-500 hover:text-red-600 font-medium">Очистить всё</button>
      <button onclick="downloadBag()" class="px-5 py-2.5 bg-ink-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition flex items-center gap-2">
        <i class="fa-solid fa-download"></i> Скачать (.txt)
      </button>
    </div>
  </div>
</div>

<script>
  // 1. Красивое уведомление
  function showToast(msg) {
    const t = document.getElementById('toast-notify');
    const m = document.getElementById('toast-msg');
    if (!t || !m) return;
    m.textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.remove('translate-y-10', 'opacity-0'), 10);
    setTimeout(() => {
      t.classList.add('translate-y-10', 'opacity-0');
      setTimeout(() => t.classList.add('hidden'), 300);
    }, 3000);
  }
  window.showToast = showToast;

  // 2. Управление портфелем
  function getBag() {
    try { return JSON.parse(localStorage.getItem('practice_bag') || '[]'); } 
    catch { return []; }
  }
  function setBag(bag) {
    localStorage.setItem('practice_bag', JSON.stringify(bag));
    updateBagUI();
  }
  function updateBagUI() {
    const bag = getBag();
    const list = document.getElementById('bag-items-list');
    if (!list) return;

    if (bag.length === 0) {
      list.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">Портфель пока пуст</p>';
    } else {
      list.innerHTML = bag.map((item, i) => `
        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 border border-gray-100">
          <div>
            <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-0.5">${item.subj}</p>
            <p class="text-sm font-semibold text-ink-900">${item.topic}</p>
          </div>
          <button onclick="removeFromBag(${i})" class="text-gray-400 hover:text-red-500 transition px-2">✕</button>
        </div>
      `).join('');
    }
  }
  function removeFromBag(idx) {
    const bag = getBag(); bag.splice(idx, 1); setBag(bag);
  }
  function clearBag() {
    setBag([]);
  }
  function openBagModal() {
    updateBagUI();
    document.getElementById('modal-bag').classList.remove('hidden');
  }
  function closeBagModal() {
    document.getElementById('modal-bag').classList.add('hidden');
  }

  // 3. СКАЧИВАНИЕ ФАЙЛА (.txt)
  function downloadBag() {
    const bag = getBag();
    if (bag.length === 0) {
      showToast('Портфель пуст, нечего скачивать');
      return;
    }

    // Формируем текст файла
    let text = "=========================================\n";
    text += "     МОЙ УЧЕБНЫЙ ПЛАН (EduMind)          \n";
    text += "=========================================\n\n";
    
    bag.forEach((item, i) => {
      text += `${i + 1}. [${item.subj}]\n   Тема: ${item.topic}\n\n`;
    });

    // Создаем файл для скачивания
    const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'my_edu_plan.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showToast('Файл успешно скачан!');
  }

  // Экспорт в window
  window.openBagModal = openBagModal;
  window.closeBagModal = closeBagModal;
  window.removeFromBag = removeFromBag;
  window.clearBag = clearBag;
  window.downloadBag = downloadBag;
</script>

<script>
  window.PRACTICE_SERVER_STATE = {
    isLoggedIn: <?= $isLoggedIn ? 'true' : 'false' ?>,
    userName:   <?= json_encode($userName) ?>,
    userId:     <?= json_encode($userId) ?>,
    lang:       <?= json_encode($lang) ?>,
    pages: { hub: '<?= $practiceBase ?>', arena: '<?= $arenaBase ?>', labs: '<?= $labsBase ?>' }
  };
</script>
<script src="practice_app.js"></script>
</body>
</html>