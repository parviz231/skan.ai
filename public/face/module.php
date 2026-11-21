<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . "/practice_data.php");

// Пускаем всех авторизованных
if (!$isLoggedIn) {
    header("Location: login.php");
    exit;
}

$topic_id     = isset($_GET['id'])        ? intval($_GET['id'])
              : (isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0);
$targetUserId = isset($_GET['user_id'])   ? intval($_GET['user_id']) : intval($userId);
$isTeacher    = isset($_SESSION['is_teacher']) && $_SESSION['is_teacher'] === true;

if ($topic_id === 0) die("Тема не выбрана");

// ---------- Тема ----------
$stmt = $conn->prepare("
    SELECT t.id, t.title, t.subject_id, s.name AS subject_name
    FROM topics t
    JOIN subjects s ON s.id = t.subject_id
    WHERE t.id = ? LIMIT 1
");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$topic = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$topic) die("Тема не найдена");

// ---------- Вопросы ТОЛЬКО текущей темы ----------
$stmt = $conn->prepare("
    SELECT id, title AS question_text, options, correct_answer
    FROM modules
    WHERE topic_id = ? AND module_type = 'test'
    ORDER BY id ASC
");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Разбираем JSON-опции в привычные A/B/C/D
foreach ($questions as &$q) {
    $opts = json_decode($q['options'], true);
    if (!is_array($opts)) $opts = [];

    $map = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];
    foreach ($opts as $i => $line) {
        if ($i > 3) break;
        $letter = chr(65 + $i); // A, B, C, D
        // срезаем префикс "A) ", "A. ", "A:" и т.п.
        $text = preg_replace('/^[A-Da-d][\)\.\:]\s*/u', '', (string)$line);
        $map[$letter] = $text;
    }
    $q['option_a'] = $map['A'];
    $q['option_b'] = $map['B'];
    $q['option_c'] = $map['C'];
    $q['option_d'] = $map['D'];
    $q['correct_option'] = strtoupper(trim((string)$q['correct_answer']));
}
unset($q);

$totalQuestions = count($questions);

// ---------- Результаты пользователя ----------
$resultsMap  = [];
$earnedScore = 0;

if ($targetUserId > 0 && $totalQuestions > 0) {
    $ids = implode(',', array_map('intval', array_column($questions, 'id')));
    $stmt = $conn->prepare("
        SELECT module_id, score
        FROM module_results
        WHERE user_id = ? AND module_id IN ($ids)
    ");
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $resultsMap[(int)$r['module_id']] = (int)$r['score'];
        $earnedScore += (int)$r['score'];
    }
    $stmt->close();
}

$answeredCount = count($resultsMap);
$percentage    = $totalQuestions > 0 ? round(($earnedScore / $totalQuestions) * 100) : 0;

require_once(__DIR__ . "/practice_header.php");
?>

<style>
:root{
    --card:#fff; --ink:#1c1f23; --muted:#8a919b; --line:#e7eaee;
    --accent:#4a6cf7; --accent-soft:#eef1ff;
    --ok:#1f9d68; --ok-soft:#f0fbf5;
    --bad:#dc2626; --bad-soft:#fef2f2;
    --radius:16px;
}
.m-wrap{ max-width:1080px; margin:0 auto; padding:64px 24px 80px;
         font-family:'Inter',-apple-system,'Segoe UI',Roboto,sans-serif; color:var(--ink); }
.m-head{ margin-bottom:32px; }
.m-head-row{ display:flex; align-items:flex-end; justify-content:space-between;
             gap:20px; flex-wrap:wrap; margin-bottom:24px; }
.m-head h1{ font-size:clamp(22px,3vw,30px); font-weight:700;
            letter-spacing:-.02em; margin:0; }
.m-head .sub{ font-size:14px; color:var(--muted); margin-top:6px; }
.m-actions{ display:flex; gap:10px; flex-wrap:wrap; }
.m-btn{ display:inline-flex; align-items:center; gap:8px;
        padding:10px 18px; border-radius:11px; font-size:14px; font-weight:500;
        text-decoration:none; border:1px solid var(--line); background:#fff;
        color:var(--ink); transition:.15s; cursor:pointer; font-family:inherit; }
.m-btn:hover{ border-color:#c8cee0; background:#fafbff; }
.m-btn-primary{ background:var(--accent); color:#fff; border-color:var(--accent); }
.m-btn-primary:hover{ background:#3a5ae0; border-color:#3a5ae0; }

.m-progress{ background:var(--card); border:1px solid var(--line);
             border-radius:var(--radius); padding:20px 24px;
             display:flex; align-items:center; gap:20px; flex-wrap:wrap;
             box-shadow:0 1px 2px rgba(20,25,40,.04); }
.m-progress .bar-wrap{ flex:1; min-width:180px; }
.m-progress .bar-label{ display:flex; justify-content:space-between;
                        font-size:13px; color:var(--muted); margin-bottom:8px; }
.m-progress .bar-label b{ color:var(--ink); font-weight:600; }
.m-progress .bar{ height:8px; background:#eef0f4; border-radius:999px; overflow:hidden; }
.m-progress .bar > span{ display:block; height:100%; background:var(--accent);
                         border-radius:999px; transition:width .4s ease; }
.m-progress .score-pill{ display:flex; flex-direction:column; align-items:center;
                         padding:10px 18px; background:var(--accent-soft);
                         border-radius:12px; min-width:100px; }
.m-progress .score-pill .val{ font-size:22px; font-weight:800;
                              color:var(--accent); line-height:1; }
.m-progress .score-pill .cap{ font-size:11px; text-transform:uppercase;
                              letter-spacing:.08em; color:var(--accent);
                              font-weight:600; margin-top:4px; }

.m-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr));
         gap:18px; margin-top:28px; }
.q-card{ position:relative; background:var(--card); border:1px solid var(--line);
         border-radius:var(--radius); padding:28px 16px 22px; text-align:center;
         cursor:pointer; font:inherit; color:inherit; transition:.25s;
         box-shadow:0 1px 2px rgba(20,25,40,.04); }
.q-card:hover{ transform:translateY(-4px); border-color:var(--accent);
               box-shadow:0 14px 32px -10px rgba(74,108,247,.28); }
.q-card .num{ font-size:28px; font-weight:700; letter-spacing:-.03em;
              color:var(--accent); line-height:1; margin-bottom:10px; }
.q-card .lbl{ font-size:12px; letter-spacing:.12em; text-transform:uppercase;
              color:var(--muted); font-weight:500; }

.q-card.answered{ border-color:var(--ok); background:var(--ok-soft); }
.q-card.answered .num{ color:var(--ok); }
.q-card.answered .lbl{ color:var(--ok); }

.q-card.wrong-answer{ border-color:var(--bad); background:var(--bad-soft); }
.q-card.wrong-answer .num{ color:var(--bad); }
.q-card.wrong-answer .lbl{ color:var(--bad); }

.q-card .badge-score{ position:absolute; top:10px; right:10px;
                      background:var(--ok); color:#fff; font-size:11px;
                      font-weight:700; padding:3px 8px; border-radius:999px; }
.q-card .badge-wrong{ position:absolute; top:10px; right:10px;
                      background:var(--bad); color:#fff; font-size:11px;
                      font-weight:700; padding:3px 8px; border-radius:999px; }

.modal-back{ position:fixed; inset:0; background:rgba(16,20,28,.45);
             backdrop-filter:blur(4px); display:flex; align-items:center;
             justify-content:center; padding:20px; z-index:1000;
             opacity:0; visibility:hidden; transition:.2s; }
.modal-back.open{ opacity:1; visibility:visible; }
.modal-box{ background:#fff; border-radius:20px; width:100%; max-width:620px;
            padding:36px 32px 28px; box-shadow:0 30px 80px -20px rgba(20,25,40,.4);
            transform:translateY(12px) scale(.98); transition:.25s;
            position:relative; max-height:calc(100vh - 40px); overflow-y:auto; }
.modal-back.open .modal-box{ transform:translateY(0) scale(1); }
.modal-close{ position:absolute; top:14px; right:14px; width:34px; height:34px;
              border:0; border-radius:10px; background:transparent;
              color:var(--muted); font-size:20px; cursor:pointer; }
.modal-close:hover{ background:#f1f3f6; color:var(--ink); }
.modal-eyebrow{ font-size:12px; letter-spacing:.12em; text-transform:uppercase;
                color:var(--muted); font-weight:500; margin-bottom:10px; }
.modal-q-text{ font-size:19px; font-weight:600; line-height:1.45;
               margin:0 0 26px; }

.opt{ display:flex; align-items:center; gap:12px; padding:14px 16px;
      border:1px solid var(--line); border-radius:12px; margin-bottom:10px;
      cursor:pointer; transition:.15s; }
.opt:hover{ border-color:#c8cee0; background:#fafbff; }
.opt input{ position:absolute; opacity:0; pointer-events:none; }
.opt .dot{ width:20px; height:20px; border:2px solid #c8cee0; border-radius:50%;
           flex-shrink:0; display:grid; place-items:center; transition:.15s; }
.opt .dot::after{ content:""; width:8px; height:8px; border-radius:50%;
                  background:transparent; transition:.15s; }
.opt .letter{ font-weight:700; font-size:13px; color:var(--muted);
              min-width:16px; text-transform:uppercase; }
.opt .txt{ font-size:15px; line-height:1.45; }
.opt input:checked ~ .dot{ border-color:var(--accent); }
.opt input:checked ~ .dot::after{ background:var(--accent); }
.opt input:checked ~ .letter{ color:var(--accent); }
.opt:has(input:checked){ border-color:var(--accent); background:var(--accent-soft); }

.opt.correct{ border-color:var(--ok); background:var(--ok-soft); }
.opt.correct .dot{ border-color:var(--ok); }
.opt.correct .dot::after{ background:var(--ok); }
.opt.correct .letter{ color:var(--ok); }
.opt.wrong{ border-color:var(--bad); background:var(--bad-soft); }
.opt.wrong .dot{ border-color:var(--bad); }
.opt.wrong .dot::after{ background:var(--bad); }
.opt.wrong .letter{ color:var(--bad); }

.modal-actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:24px; }
.btn{ font:inherit; font-size:15px; font-weight:500; padding:11px 22px;
      border-radius:11px; border:1px solid transparent; cursor:pointer;
      transition:.15s; }
.btn:active{ transform:scale(.98); }
.btn-primary{ background:var(--accent); color:#fff; }
.btn-primary:hover{ background:#3a5ae0; }
.btn-primary:disabled{ opacity:.6; cursor:not-allowed; }
.btn-ghost{ background:transparent; color:var(--muted); border-color:var(--line); }
.btn-ghost:hover{ color:var(--ink); border-color:#c8cee0; }

.toast{ position:fixed; bottom:24px; left:50%;
        transform:translateX(-50%) translateY(20px);
        background:#1c1f23; color:#fff; padding:12px 22px;
        border-radius:12px; font-size:14px; font-weight:500;
        box-shadow:0 12px 40px rgba(0,0,0,.25);
        opacity:0; pointer-events:none; transition:.25s; z-index:1100; }
.toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
.toast.ok{ background:var(--ok); }
.toast.err{ background:var(--bad); }

.m-empty{ text-align:center; padding:80px 20px; color:var(--muted); font-size:15px; }

@media (max-width:520px){
    .m-wrap{ padding:40px 16px 60px; }
    .m-grid{ gap:12px; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); }
    .q-card{ padding:22px 10px 18px; }
    .q-card .num{ font-size:22px; }
    .modal-box{ padding:28px 20px 22px; border-radius:16px; }
    .modal-q-text{ font-size:17px; }
}
</style>

<div class="m-wrap">
    <div class="m-head">
        <div class="m-head-row">
            <div>
                <h1><?= htmlspecialchars($topic['title']) ?></h1>
                <div class="sub">
                    Предмет: <?= htmlspecialchars($topic['subject_name']) ?> ·
                    <?= $totalQuestions ?> вопросов ·
                    пройдено <b><?= $answeredCount ?></b>
                </div>
            </div>
            <div class="m-actions">
                <?php if ($isTeacher): ?>
                    <a href="teacher_result.php?topic_id=<?= $topic_id ?>" class="m-btn">
                        👥 Все ученики
                    </a>
                <?php else: ?>
                    <a href="results.php?topic_id=<?= $topic_id ?>" class="m-btn">
                        👥 Мои одноклассники
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="m-progress">
            <div class="bar-wrap">
                <div class="bar-label">
                    <span>Прогресс</span>
                    <span><b><?= $answeredCount ?></b> / <?= $totalQuestions ?></span>
                </div>
                <div class="bar">
                    <span style="width:<?= $totalQuestions ? round(($answeredCount/$totalQuestions)*100) : 0 ?>%"></span>
                </div>
            </div>
            <div class="score-pill">
                <span class="val"><?= $earnedScore ?></span>
                <span class="cap">баллов</span>
            </div>
            <div class="score-pill" style="background:#f4f6fa;">
                <span class="val" style="color:var(--ink);"><?= $percentage ?>%</span>
                <span class="cap" style="color:var(--muted);">успех</span>
            </div>
        </div>
    </div>

    <?php if ($totalQuestions === 0): ?>
        <div class="m-empty">Для этой темы пока нет вопросов.</div>
    <?php else: ?>
        <div class="m-grid">
            <?php foreach ($questions as $index => $q):
                $qid   = (int)$q['id'];
                $score = $resultsMap[$qid] ?? null;
                $done  = $score !== null;
                $isOk  = $done && $score > 0;

                $cardClass = '';
                if ($isOk)      $cardClass = ' answered';
                elseif ($done)  $cardClass = ' wrong-answer';
            ?>
                <button type="button"
                        class="q-card<?= $cardClass ?>"
                        data-qid="<?= $qid ?>"
                        onclick="openQ(<?= $qid ?>)">
                    <?php if ($isOk): ?>
                        <span class="badge-score">+<?= $score ?></span>
                    <?php elseif ($done): ?>
                        <span class="badge-wrong">✕</span>
                    <?php endif; ?>
                    <div class="num"><?= $index + 1 ?></div>
                    <div class="lbl"><?= $done ? ($isOk ? 'Верно' : 'Неверно') : 'Вопрос' ?></div>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="modal-back" id="qModal" onclick="if(event.target===this)closeQ()">
    <div class="modal-box">
        <button class="modal-close" onclick="closeQ()" aria-label="Закрыть">✕</button>
        <div class="modal-eyebrow" id="qEyebrow">Вопрос</div>
        <p class="modal-q-text" id="qText">—</p>
        <div id="qOptions"></div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="closeQ()">Закрыть</button>
            <button class="btn btn-primary" id="btnSubmit" onclick="submitAnswer()">Ответить</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const QUESTIONS = <?= json_encode($questions, JSON_UNESCAPED_UNICODE) ?>;
const RESULTS   = <?= json_encode((object)$resultsMap) ?>;
const ANSWERS   = {};
let currentId   = null;
let submitLock  = false;

function openQ(id){
    const q = QUESTIONS.find(x => Number(x.id) === Number(id));
    if (!q) return;
    currentId = Number(id);

    const idx = QUESTIONS.indexOf(q);
    document.getElementById('qEyebrow').textContent =
        'Вопрос ' + (idx + 1) + ' из ' + QUESTIONS.length;
    document.getElementById('qText').textContent = q.question_text || '—';

    const opts = { A:q.option_a, B:q.option_b, C:q.option_c, D:q.option_d };
    const already = RESULTS[q.id] !== undefined;
    const chosen  = ANSWERS[q.id];

    let html = '';
    for (const [L, text] of Object.entries(opts)){
        if (!text || !String(text).trim()) continue;

        let cls = 'opt';
        if (already) {
            if (L === q.correct_option) cls += ' correct';
            if (chosen === L && chosen !== q.correct_option) cls += ' wrong';
        }

        const checked = chosen === L ? 'checked' : '';

        html += `
          <label class="${cls}">
            <input type="radio" name="ans_${q.id}" value="${L}" ${checked} ${already ? 'disabled' : ''}>
            <span class="dot"></span>
            <span class="letter">${L}</span>
            <span class="txt">${escapeHtml(String(text))}</span>
          </label>`;
    }
    document.getElementById('qOptions').innerHTML = html;

    if (!already) {
        document.querySelectorAll(`input[name="ans_${q.id}"]`).forEach(inp => {
            inp.addEventListener('change', () => {
                ANSWERS[q.id] = inp.value;
                document.getElementById('btnSubmit').disabled = false;
            });
        });
    }

    const btn = document.getElementById('btnSubmit');
    btn.disabled = already || !ANSWERS[q.id];
    btn.textContent = already ? 'Уже пройдено' : 'Ответить';

    document.getElementById('qModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeQ(){
    document.getElementById('qModal').classList.remove('open');
    document.body.style.overflow = '';
}

async function submitAnswer(){
    if (!currentId || submitLock) return;
    const chosen = ANSWERS[currentId];
    if (!chosen) { showToast('Выберите вариант ответа', 'err'); return; }

    const q = QUESTIONS.find(x => Number(x.id) === Number(currentId));
    if (!q) return;

    const score = (q.correct_option && q.correct_option === chosen) ? 1 : 0;

    submitLock = true;
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.textContent = 'Сохраняем…';

    try {
        const fd = new FormData();
        fd.append('module_id', q.id);
        fd.append('score', score);

        const res  = await fetch('save_result.php', { method:'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success'){
            showToast(score ? 'Верно! +1 балл' : 'Неверно. Балл: 0', score ? 'ok' : 'err');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message || 'Ошибка', 'err');
            btn.disabled = false;
            btn.textContent = 'Ответить';
        }
    } catch (e){
        showToast('Ошибка сети', 'err');
        btn.disabled = false;
        btn.textContent = 'Ответить';
    } finally {
        submitLock = false;
    }
}

function showToast(msg, type){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show ' + (type || '');
    clearTimeout(t._tm);
    t._tm = setTimeout(()=> t.className = 'toast', 2200);
}

function escapeHtml(s){
    return s.replace(/[&<>"']/g, c => (
        {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]
    ));
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQ(); });
</script>

<?php require_once(__DIR__ . "/practice_footer.php"); ?>