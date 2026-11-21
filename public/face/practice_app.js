const SERVER = window.PRACTICE_SERVER_STATE || { isLoggedIn: false, userName: null, userId: null };
let currentSubjectIdx = 0;
let currentTopicIdx = 0;
let carouselSubjects = [];
let carouselIdx = 0;

/* ---------- Звуки ---------- */
function playAudio(type) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator(); const gain = ctx.createGain();
    osc.connect(gain); gain.connect(ctx.destination);
    if (type === 'success') {
      osc.type = 'triangle'; osc.frequency.setValueAtTime(523.25, ctx.currentTime);
      osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1);
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
      osc.start(); osc.stop(ctx.currentTime + 0.35);
    } else if (type === 'click') {
      osc.type = 'sine'; osc.frequency.setValueAtTime(800, ctx.currentTime);
      gain.gain.setValueAtTime(0.04, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.04);
      osc.start(); osc.stop(ctx.currentTime + 0.04);
    }
  } catch (e) {}
}

/* ---------- Инициализация при загрузке ---------- */
window.addEventListener('load', function() {
  if (document.getElementById('stage-books-container') && window.PRACTICE_SUBJECTS) {
    initSubjectsCarousel();
  }
  
  if (document.getElementById('topic3dCanvas')) {
    renderTopicPage3D();
  }
});

/* ---------- Карусель 3D ---------- */
function initSubjectsCarousel() {
  carouselSubjects = window.PRACTICE_SUBJECTS || [];
  const curId = parseInt(window.PRACTICE_CURRENT_SUBJECT_ID || 0);
  
  carouselIdx = carouselSubjects.findIndex(s => parseInt(s.id) === curId);
  if (carouselIdx === -1) carouselIdx = 0;

  render3DBooksFromDB();
  updateRightPanel(carouselIdx); 
}

function render3DBooksFromDB() {
  const c = document.getElementById('stage-books-container'); 
  if (!c) return;
  c.innerHTML = '';
  
  carouselSubjects.forEach((subj, idx) => {
    const book = document.createElement('div');
    
    // УВЕЛИЧИЛИ КАРТОЧКИ: w-56 h-72. Текст по центру!
    book.className = `book-3d absolute w-56 h-72 rounded-2xl cursor-pointer p-6 flex flex-col items-center justify-between text-center text-white shadow-2xl bg-gradient-to-br ${subj.grad} border border-white/20`;
    
    book.onclick = () => {
      playAudio('click');
      carouselIdx = idx;
      update3DBooksFromDB();
      updateRightPanel(idx);
    };

    book.innerHTML = `
      <div class="flex justify-center w-full mt-2">
        <span class="text-5xl drop-shadow-lg">${subj.icon}</span>
      </div>
      
      <div class="w-full px-2">
        <h3 class="text-xl font-black leading-tight drop-shadow-md">${subj.name}</h3>
        <p class="text-[11px] text-white/80 mt-2 font-medium">Нажмите, чтобы открыть</p>
      </div>
      
      <div class="w-full pt-3 border-t border-white/30 flex justify-center items-center text-xs font-bold gap-2">
        <span>Открыть</span>
        <span class="text-base">→</span>
      </div>`;
      
    c.appendChild(book);
  });
  
  update3DBooksFromDB();
}

function update3DBooksFromDB() {
  const books = document.querySelectorAll('.book-3d'); 
  const N = carouselSubjects.length;
  if (N === 0) return;

  books.forEach((book, i) => {
    let diff = (i - carouselIdx) % N;
    if (diff < -N / 2) diff += N; 
    if (diff > N / 2) diff -= N;

    if (diff === 0) {
      book.style.transform = `translate3d(0,0,140px) rotateY(0deg) scale(1.15)`;
      book.style.opacity = '1'; 
      book.style.zIndex = '40';
    } else {
      const posX = diff * 180; 
      const rotY = diff < 0 ? 35 : -35;
      const scale = 0.85 - Math.abs(diff) * 0.08;
      const opacity = Math.abs(diff) > 2 ? 0 : (1 - Math.abs(diff) * 0.3);
      
      book.style.transform = `translate3d(${posX}px,0,-100px) rotateY(${rotY}deg) scale(${scale})`;
      book.style.opacity = opacity; 
      book.style.zIndex = `${30 - Math.abs(diff)}`;
    }
  });

  const ctr = document.getElementById('stage-book-counter');
  if (ctr) ctr.textContent = `Предмет ${carouselIdx + 1} из ${N}`;
  
  const bar = document.getElementById('stage-progress-bar');
  if (bar) bar.style.width = `${((carouselIdx + 1) / N) * 100}%`;
}

function nextBook() {
  if (carouselSubjects.length === 0) return;
  playAudio('click');
  carouselIdx = (carouselIdx + 1) % carouselSubjects.length;
  update3DBooksFromDB();
  updateRightPanel(carouselIdx);
}

function prevBook() {
  if (carouselSubjects.length === 0) return;
  playAudio('click');
  carouselIdx = (carouselIdx - 1 + carouselSubjects.length) % carouselSubjects.length;
  update3DBooksFromDB();
  updateRightPanel(carouselIdx);
}

/* ---------- Обновление правой панели (без перезагрузки) ---------- */
function updateRightPanel(idx) {
  const subj = carouselSubjects[idx];
  if (!subj) return;

  const previewBox = document.getElementById('right-preview-box');
  const previewImg = document.getElementById('right-preview-img');
  const previewFallback = document.getElementById('right-preview-fallback');
  const catEl = document.getElementById('right-subj-category');
  const titleEl = document.getElementById('right-subj-title');
  const topicsList = document.getElementById('active-subj-topics');
  const topicsCount = document.getElementById('right-topics-count');
  const btnOpenFirst = document.getElementById('right-btn-open-first');

  if (previewBox) {
    previewBox.className = `relative h-72 shrink-0 rounded-xl overflow-hidden border border-surface-border flex items-end p-6 bg-gradient-to-br ${subj.grad} mb-5 group`;
    
    if (subj.img) {
      if (previewImg) { 
          previewImg.src = subj.img; 
          previewImg.style.display = 'block'; 
          previewImg.parentElement.style.backgroundImage = `url('${subj.img}')`; 
      }
      if (previewFallback) previewFallback.style.display = 'none';
    } else {
      if (previewImg) previewImg.style.display = 'none';
      if (previewFallback) {
        previewFallback.innerHTML = subj.icon;
        previewFallback.style.display = 'flex';
      }
    }
  }

  if (catEl) catEl.textContent = subj.category || 'Предмет';
  if (titleEl) titleEl.textContent = subj.name;

  const topics = (window.ALL_TOPICS && window.ALL_TOPICS[subj.id]) ? window.ALL_TOPICS[subj.id] : [];
  
  if (topicsCount) topicsCount.textContent = `Темы (${topics.length})`;
  
  if (topicsList) {
    if (topics.length === 0) {
      topicsList.innerHTML = `<p class="text-xs text-ink-400 text-center py-4 font-mono">У этого предмета пока нет тем</p>`;
    } else {
      let html = '';
      topics.forEach((t, i) => {
        let shortDesc = t.description ? t.description.replace(/<[^>]+>/g, '').substring(0, 90) : '';
        if (t.description && t.description.length > 90) shortDesc += '...';

        html += `
          <a href="topic.php?id=${t.id}" class="topic-item block p-3 rounded-lg border border-surface-border bg-white hover:bg-brand-50 hover:border-brand-500 transition group">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <span class="block text-xs font-semibold text-ink-900 group-hover:text-brand-700">
                  ${i + 1}. ${t.title}
                </span>
                ${shortDesc ? `<span class="block text-[10px] text-ink-400 mt-1 line-clamp-2">${shortDesc}</span>` : ''}
              </div>
              <i class="fa-solid fa-arrow-right text-ink-400 group-hover:text-brand-500 text-xs mt-1 transition-transform group-hover:translate-x-1"></i>
            </div>
          </a>
        `;
      });
      topicsList.innerHTML = html;
    }
  }

  if (btnOpenFirst) {
    if (topics.length > 0) {
      btnOpenFirst.href = `topic.php?id=${topics[0].id}`;
      btnOpenFirst.style.display = 'flex';
    } else {
      btnOpenFirst.style.display = 'none';
    }
  }
}

/* ---------- 3D анимация для topic.php ---------- */
function renderTopicPage3D() {
  const canvas = document.getElementById('topic3dCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const w = canvas.width, h = canvas.height;
  let rot = 0;
  function loop() {
    ctx.clearRect(0, 0, w, h);
    rot += 0.02;
    const cx = w / 2, cy = h / 2;

    ctx.strokeStyle = 'rgba(0,113,227,0.3)';
    ctx.lineWidth = 1;
    ctx.beginPath(); ctx.arc(cx, cy, 70, 0, Math.PI * 2); ctx.stroke();

    ctx.strokeStyle = '#0071e3';
    ctx.lineWidth = 2;
    ctx.beginPath();
    for (let a = 0; a < Math.PI * 2; a += 0.1) {
      const x = cx + Math.cos(a + rot) * 70;
      const y = cy + Math.sin(a * 2 + rot) * 30;
      if (a === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
    }
    ctx.stroke();

    ctx.fillStyle = '#ff9500';
    ctx.beginPath(); ctx.arc(cx, cy, 12, 0, Math.PI * 2); ctx.fill();

    requestAnimationFrame(loop);
  }
  loop();
}

/* ---------- Сохранение в портфель (ИСПРАВЛЕНО) ---------- */
function saveCurrentTopicToBag() {
  // Проверка авторизации
  if (typeof isAuthed === 'function' && !isAuthed()) { 
      if (typeof openAuthGateModal === 'function') openAuthGateModal(); 
      return; 
  }

  let subjName  = 'Предмет';
  let topicName = 'Тема';

  // Берем данные из глобальной переменной, которую мы создали на странице topic.php
  if (window.TOPIC_DATA) {
    subjName  = window.TOPIC_DATA.subject || 'Предмет';
    topicName = window.TOPIC_DATA.title   || 'Тема';
  } else {
    console.error('Данные темы не найдены (window.TOPIC_DATA пуст)');
    return;
  }

  // Сохраняем в портфель (localStorage)
  const bag = typeof getBag === 'function' ? getBag() : [];
  bag.push({ subj: subjName, topic: topicName });
  
  if (typeof setBag === 'function') setBag(bag);

  // Начисляем XP
  if (typeof addPracticeXP === 'function') addPracticeXP(150);

  // Показываем красивое уведомление
  if (typeof showToast === 'function') {
      showToast(`«${topicName}» сохранено в портфель! (+150 XP)`);
  } else {
      alert(`«${topicName}» сохранено в портфель!`);
  }
}

window.nextBook = nextBook;
window.prevBook = prevBook;
window.saveCurrentTopicToBag = saveCurrentTopicToBag;