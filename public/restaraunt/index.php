<?php
// Подключаем базу данных SQLite на стороне сервера PHP
require_once __DIR__ . '/api/db.php';

// Переключаем тип ответа обратно на HTML для браузера
header('Content-Type: text/html; charset=utf-8');

// Загружаем актуальный список столиков из БД при загрузке страницы
$stmt = $pdo->query("SELECT * FROM restaurant_tables ORDER BY id ASC");
$initialTables = $stmt->fetchAll();

// Приводим типы данных для корректной передачи в 3D-движок
foreach ($initialTables as &$t) {
    $t['id'] = (int)$t['id'];
    $t['seats'] = (int)$t['seats'];
    $t['x'] = (float)$t['pos_x'];
    $t['z'] = (float)$t['pos_z'];
    $t['waitTime'] = ($t['status'] === 'WAITING') ? max(0, time() - strtotime($t['updated_at'])) : 0;
    $t['stayRemaining'] = ($t['status'] === 'FREE') ? 0 : 25;
}
unset($t);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VisionTable AI — PHP + Python + SQLite 3D Система</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    body { background: #090d16; color: #f8fafc; overflow: hidden; height: 100vh; display: flex; }

    #canvas-container { flex: 1; position: relative; height: 100%; }

    .top-bar {
      position: absolute; top: 16px; left: 16px; right: 360px;
      display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between;
      background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px);
      padding: 12px 18px; border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.2);
      z-index: 10;
    }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand-dot { width: 10px; height: 10px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; }
    .brand h1 { font-size: 16px; font-weight: 700; }
    .brand span { font-size: 12px; color: #94a3b8; }

    .controls-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .btn {
      background: #1e293b; color: #e2e8f0; border: 1px solid #334155;
      padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
      cursor: pointer; transition: all 0.2s; text-decoration: none;
    }
    .btn:hover { background: #334155; }
    .btn.active { background: #0284c7; border-color: #38bdf8; color: #fff; }
    .btn.sim-active { background: #7c3aed; border-color: #a78bfa; color: #fff; }

    .bottom-hud {
      position: absolute; bottom: 16px; left: 16px; right: 360px;
      display: flex; gap: 14px; align-items: stretch; z-index: 10;
    }
    .hud-card {
      background: rgba(15, 23, 42, 0.88); backdrop-filter: blur(10px);
      border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 14px;
      padding: 12px 16px;
    }
    .legend { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; font-size: 12px; }
    .legend-item { display: flex; align-items: center; gap: 6px; }
    .dot { width: 10px; height: 10px; border-radius: 50%; }
    .dot.free { background: #22c55e; }
    .dot.waiting { background: #ef4444; }
    .dot.served { background: #3b82f6; }
    .dot.away { background: #a855f7; }
    .dot.dirty { background: #eab308; }

    .sidebar {
      width: 344px; background: #0f172a; border-left: 1px solid #1e293b;
      padding: 16px; display: flex; flex-direction: column; gap: 14px;
      z-index: 20; overflow-y: auto;
    }
    .panel-box { background: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 14px; }
    .panel-title { font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
    .status-pill { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }

    .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; }
    .act-btn {
      padding: 8px; border-radius: 8px; border: none; font-size: 11px; font-weight: 600;
      cursor: pointer; color: #fff; transition: opacity 0.2s;
    }
    .act-btn:hover { opacity: 0.9; }
    .act-seat { background: #dc2626; }
    .act-serve { background: #2563eb; }
    .act-away { background: #9333ea; }
    .act-leave { background: #ca8a04; }
    .act-clean { background: #16a34a; grid-column: span 2; }

    .phone-frame {
      flex: 1; background: #020617; border: 3px solid #334155; border-radius: 26px;
      padding: 12px; display: flex; flex-direction: column; min-height: 270px;
    }
    .phone-notch { width: 90px; height: 14px; background: #1e293b; border-radius: 0 0 10px 10px; margin: -12px auto 10px; }
    .phone-header { display: flex; justify-content: space-between; font-size: 12px; color: #94a3b8; padding-bottom: 8px; border-bottom: 1px solid #1e293b; }
    .notif-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
    .notif-card {
      background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.5);
      border-radius: 10px; padding: 10px;
    }
    .notif-card.clean-notif { background: rgba(234, 179, 8, 0.15); border-color: rgba(234, 179, 8, 0.5); }
    .notif-top { display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
    .notif-desc { font-size: 11px; color: #cbd5e1; margin-bottom: 8px; }
    .notif-btn {
      width: 100%; padding: 6px; border-radius: 6px; border: none;
      background: #ef4444; color: #fff; font-size: 11px; font-weight: 700; cursor: pointer;
    }
    .notif-card.clean-notif .notif-btn { background: #eab308; color: #090d16; }

    .ws-badge {
      display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600;
      padding: 4px 10px; border-radius: 20px; background: rgba(30, 41, 59, 0.9);
      border: 1px solid #334155; color: #94a3b8;
    }
    .ws-badge.connected { border-color: #22c55e; color: #4ade80; }
  </style>
</head>
<body>

  <div id="canvas-container">
    <div class="top-bar">
      <div class="brand">
        <div class="brand-dot"></div>
        <div>
          <h1>VisionTable AI (PHP + SQLite)</h1>
          <span>Цифровой двойник зала • Столов в БД: <?= count($initialTables) ?></span>
        </div>
      </div>

      <div class="controls-group">
        <div id="ws-status" class="ws-badge">🔴 Python CV: ожидание</div>
        <button class="btn active" data-speed="1" onclick="setSpeed(1, this)">1x</button>
        <button class="btn" data-speed="15" onclick="setSpeed(15, this)">15x</button>
        <button class="btn" id="auto-sim-btn" onclick="toggleAutoSim()">▶ Авто-тест</button>
        <a href="analytics.php" class="btn" style="background:#0f766e; border-color:#14b8a6;">📊 Аналитика SQL</a>
      </div>
    </div>

    <div class="bottom-hud">
      <div class="hud-card" style="flex: 1;">
        <div class="legend" style="margin-bottom: 8px;">
          <div class="legend-item"><span class="dot free"></span> Свободен</div>
          <div class="legend-item"><span class="dot waiting"></span> Ждет официанта</div>
          <div class="legend-item"><span class="dot served"></span> Обслуживается</div>
          <div class="legend-item"><span class="dot away"></span> Вышли (вещи на столе)</div>
          <div class="legend-item"><span class="dot dirty"></span> Нужна уборка</div>
        </div>
        <div id="prediction-line" style="font-size: 12px; color: #38bdf8; font-weight: 600;">
          Загрузка данных из SQLite...
        </div>
      </div>
    </div>
  </div>

  <div class="sidebar">
    <div class="panel-box">
      <div class="panel-title">
        <span id="sel-table-name">Столик №1</span>
        <span id="sel-table-badge" class="status-pill" style="background:#16a34a;">СВОБОДЕН</span>
      </div>
      <div style="font-size: 12px; color: #94a3b8; display: flex; justify-content: space-between;">
        <span>Таймер ожидания:</span>
        <strong id="sel-table-timer" style="color: #f8fafc;">00:00</strong>
      </div>

      <div class="action-grid">
        <button class="act-btn act-seat" onclick="manualAction('WAITING')">1. Сели гости</button>
        <button class="act-btn act-serve" onclick="manualAction('SERVED')">2. Принят заказ</button>
        <button class="act-btn act-away" onclick="manualAction('AWAY')">3. Вышли (вещи)</button>
        <button class="act-btn act-leave" onclick="manualAction('DIRTY')">4. Ушли (посуда)</button>
        <button class="act-btn act-clean" onclick="manualAction('FREE')">5. Стол убран (Свободен)</button>
      </div>
    </div>

    <div class="phone-frame">
      <div class="phone-notch"></div>
      <div class="phone-header">
        <span>📱 WaiterApp • PHP Sync</span>
        <span id="db-sync-status">SQLite OK</span>
      </div>
      <div class="notif-list" id="notif-list"></div>
    </div>
  </div>

  <script>
    // Начальные данные, загруженные напрямую из PHP + SQLite
    window.tables = <?= json_encode($initialTables, JSON_UNESCAPED_UNICODE) ?>;

    const STATUS_COLORS = {
      FREE: 0x22c55e,
      WAITING: 0xef4444,
      SERVED: 0x3b82f6,
      AWAY: 0xa855f7,
      DIRTY: 0xeab308
    };

    const STATUS_LABELS = {
      FREE: { text: "СВОБОДЕН", bg: "#16a34a", color: "#fff" },
      WAITING: { text: "ЖДЕТ ОФИЦИАНТА", bg: "#dc2626", color: "#fff" },
      SERVED: { text: "ОБСЛУЖИВАЕТСЯ", bg: "#2563eb", color: "#fff" },
      AWAY: { text: "ВЫШЛИ (ВЕЩИ)", bg: "#9333ea", color: "#fff" },
      DIRTY: { text: "НУЖНА УБОРКА", bg: "#eab308", color: "#090d16" }
    };

    let simSpeed = 1;
    let autoSim = false;
    let selectedTableId = 1;
    let liveCVConnected = false;

    // ==========================================
    // THREE.JS 3D-СЦЕНА
    // ==========================================
    const container = document.getElementById("canvas-container");
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x090d16);

    const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 100);
    camera.position.set(0, 14, 15);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);

    const controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.maxPolarAngle = Math.PI / 2.15;

    scene.add(new THREE.AmbientLight(0x94a3b8, 0.7));
    const dirLight = new THREE.DirectionalLight(0xffffff, 0.85);
    dirLight.position.set(8, 16, 10);
    scene.add(dirLight);

    const floor = new THREE.Mesh(
      new THREE.PlaneGeometry(22, 16),
      new THREE.MeshStandardMaterial({ color: 0x111827, roughness: 0.8 })
    );
    floor.rotation.x = -Math.PI / 2;
    scene.add(floor);
    scene.add(new THREE.GridHelper(22, 22, 0x1e293b, 0x1e293b));

    const tableMeshes = {};
    const clickableMeshes = [];

    function createTableLabel(text) {
      const canvas = document.createElement("canvas");
      canvas.width = 128; canvas.height = 64;
      const ctx = canvas.getContext("2d");
      ctx.fillStyle = "rgba(15, 23, 42, 0.85)";
      ctx.roundRect(10, 10, 108, 44, 12);
      ctx.fill();
      ctx.font = "bold 26px sans-serif";
      ctx.fillStyle = "#ffffff";
      ctx.textAlign = "center";
      ctx.fillText(text, 64, 41);
      const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(canvas) }));
      sprite.scale.set(1.4, 0.7, 1);
      return sprite;
    }

    window.tables.forEach(t => {
      const group = new THREE.Group();
      group.position.set(t.x, 0, t.z);

      const topMat = new THREE.MeshStandardMaterial({ color: STATUS_COLORS[t.status] || 0x22c55e, roughness: 0.35 });
      const topMesh = new THREE.Mesh(new THREE.BoxGeometry(1.8, 0.12, 1.4), topMat);
      topMesh.position.y = 0.78;
      topMesh.userData = { tableId: t.id };
      clickableMeshes.push(topMesh);
      group.add(topMesh);

      const leg = new THREE.Mesh(
        new THREE.CylinderGeometry(0.12, 0.25, 0.75, 12),
        new THREE.MeshStandardMaterial({ color: 0x334155 })
      );
      leg.position.y = 0.38;
      group.add(leg);

      const ringMat = new THREE.MeshBasicMaterial({ color: STATUS_COLORS[t.status] || 0x22c55e, side: THREE.DoubleSide });
      const ring = new THREE.Mesh(new THREE.RingGeometry(1.15, 1.32, 32), ringMat);
      ring.rotation.x = -Math.PI / 2;
      ring.position.y = 0.02;
      group.add(ring);

      const label = createTableLabel(`#${t.id}`);
      label.position.y = 1.65;
      group.add(label);

      const guestsGroup = new THREE.Group();
      const dishesGroup = new THREE.Group();
      const itemsGroup = new THREE.Group();

      const chairPositions = [
        { x: 0, z: -1.05 }, { x: 0, z: 1.05 }, { x: -1.2, z: 0 }, { x: 1.2, z: 0 }
      ];

      chairPositions.slice(0, t.seats).forEach(pos => {
        const chair = new THREE.Mesh(new THREE.BoxGeometry(0.5, 0.45, 0.5), new THREE.MeshStandardMaterial({ color: 0x475569 }));
        chair.position.set(pos.x, 0.22, pos.z);
        group.add(chair);

        const guest = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.22, 0.75, 12), new THREE.MeshStandardMaterial({ color: 0x94a3b8 }));
        guest.position.set(pos.x, 0.75, pos.z);
        guestsGroup.add(guest);

        const plate = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.12, 0.04, 16), new THREE.MeshStandardMaterial({ color: 0xf8fafc }));
        plate.position.set(pos.x * 0.45, 0.86, pos.z * 0.45);
        dishesGroup.add(plate);
      });

      // Личная вещь на столе (сумка/телефон для статуса AWAY)
      const bag = new THREE.Mesh(new THREE.BoxGeometry(0.35, 0.22, 0.25), new THREE.MeshStandardMaterial({ color: 0xc084fc }));
      bag.position.set(0, 0.95, 0);
      itemsGroup.add(bag);

      group.add(guestsGroup, dishesGroup, itemsGroup);
      scene.add(group);
      tableMeshes[t.id] = { topMat, ringMat, guestsGroup, dishesGroup, itemsGroup };
    });

    // ==========================================
    // СИНХРОНИЗАЦИЯ С PHP API И SQLITE
    // ==========================================
    async function saveStatusToPHP(tableId, newStatus, waitSec = 0) {
      try {
        await fetch('api/update_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            table_id: tableId,
            status: newStatus,
            guests_count: newStatus === 'WAITING' || newStatus === 'SERVED' ? 2 : 0,
            wait_seconds: Math.round(waitSec)
          })
        });
      } catch (e) {
        console.error("Ошибка сохранения в PHP/SQLite:", e);
      }
    }

    window.setTableStatus = function(id, newStatus, pushToBackend = true) {
      const t = window.tables.find(item => item.id === id);
      if (!t) return;
      if (t.status !== newStatus) {
        t.status = newStatus;
        if (newStatus === "FREE" || newStatus === "SERVED") t.waitTime = 0;
        if (pushToBackend) saveStatusToPHP(id, newStatus, t.waitTime);
      }
      syncVisuals();
    };

    function manualAction(status) {
      window.setTableStatus(selectedTableId, status, true);
    }

    function syncVisuals() {
      let occupied = 0;
      window.tables.forEach(t => {
        const v = tableMeshes[t.id];
        if (!v) return;
        const hex = STATUS_COLORS[t.status] || 0x22c55e;
        v.topMat.color.setHex(hex);
        v.ringMat.color.setHex(hex);
        v.guestsGroup.visible = (t.status === "WAITING" || t.status === "SERVED");
        v.dishesGroup.visible = (t.status === "SERVED" || t.status === "DIRTY" || t.status === "AWAY");
        v.itemsGroup.visible = (t.status === "AWAY");
        if (t.status !== "FREE") occupied++;
      });

      const freeList = window.tables.filter(t => t.status === "FREE").map(t => `№${t.id}`);
      document.getElementById("prediction-line").textContent = freeList.length > 0
        ? `✅ Свободные столы в базе SQLite: ${freeList.join(", ")} (Загрузка зала: ${Math.round(occupied / window.tables.length * 100)}%)`
        : `⏳ Все столики заняты! Ожидайте освобождения.`;

      updateSelectedPanel();
      updateWaiterPhone();
    }

    function formatSeconds(sec) {
      const m = Math.floor(sec / 60).toString().padStart(2, "0");
      const s = Math.floor(sec % 60).toString().padStart(2, "0");
      return `${m}:${s}`;
    }

    function updateSelectedPanel() {
      const t = window.tables.find(item => item.id === selectedTableId);
      if (!t) return;
      const meta = STATUS_LABELS[t.status] || STATUS_LABELS.FREE;
      document.getElementById("sel-table-name").textContent = `Столик №${t.id} (${t.seats} места)`;
      const badge = document.getElementById("sel-table-badge");
      badge.textContent = meta.text;
      badge.style.background = meta.bg;
      badge.style.color = meta.color;
      document.getElementById("sel-table-timer").textContent = t.status === "WAITING" ? formatSeconds(t.waitTime) : "00:00";
    }

    function updateWaiterPhone() {
      const list = document.getElementById("notif-list");
      const alerts = window.tables.filter(t => (t.status === "WAITING" && t.waitTime >= 300) || t.status === "DIRTY");
      if (alerts.length === 0) {
        list.innerHTML = `<div style="text-align:center; color:#475569; font-size:12px; margin-top:40px;">Нет срочных вызовов.<br>Данные синхронизированы с SQLite.</div>`;
        return;
      }
      list.innerHTML = alerts.map(t => t.status === "WAITING" ? `
        <div class="notif-card">
          <div class="notif-top"><span style="color:#fca5a5;">🚨 Столик №${t.id}</span><span>${formatSeconds(t.waitTime)}</span></div>
          <div class="notif-desc">Гости ждут официанта > 5 минут!</div>
          <button class="notif-btn" onclick="window.setTableStatus(${t.id}, 'SERVED', true)">Принять заказ</button>
        </div>` : `
        <div class="notif-card clean-notif">
          <div class="notif-top"><span style="color:#fde047;">🧹 Столик №${t.id}</span><span>Уборка</span></div>
          <div class="notif-desc">Гости ушли, осталась посуда.</div>
          <button class="notif-btn" onclick="window.setTableStatus(${t.id}, 'FREE', true)">Стол убран</button>
        </div>`
      ).join("");
    }

    function setSpeed(speed, btn) {
      simSpeed = speed;
      document.querySelectorAll(".top-bar .btn[data-speed]").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    }

    function toggleAutoSim() {
      autoSim = !autoSim;
      const btn = document.getElementById("auto-sim-btn");
      btn.classList.toggle("sim-active", autoSim);
      btn.textContent = autoSim ? "⏸ Пауза теста" : "▶ Авто-тест";
    }

    // Клик по столу в 3D
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2();
    renderer.domElement.addEventListener("pointerdown", (e) => {
      const rect = renderer.domElement.getBoundingClientRect();
      mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
      mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
      raycaster.setFromCamera(mouse, camera);
      const hits = raycaster.intersectObjects(clickableMeshes);
      if (hits.length > 0) {
        selectedTableId = hits[0].object.userData.tableId;
        updateSelectedPanel();
      }
    });

    // Периодическая синхронизация с PHP API (get_state.php)
    setInterval(async () => {
      if (liveCVConnected || autoSim) return;
      try {
        const res = await fetch('api/get_state.php');
        const data = await res.json();
        if (data.tables) {
          data.tables.forEach(dbT => {
            const local = window.tables.find(t => t.id === dbT.id);
            if (local) {
              local.status = dbT.status;
              if (dbT.status === "WAITING") local.waitTime = dbT.wait_seconds;
            }
          });
          syncVisuals();
        }
      } catch (e) {}
    }, 3000);

    // WebSocket связь с Python CV-камерой
    (function connectWS() {
      const badge = document.getElementById("ws-status");
      const ws = new WebSocket("ws://localhost:8765");
      ws.onopen = () => { liveCVConnected = true; badge.classList.add("connected"); badge.textContent = "🟢 Python CV: онлайн"; };
      ws.onmessage = (e) => {
        const data = JSON.parse(e.data);
        if (data.fps) badge.textContent = `🟢 Python CV (${data.fps} FPS)`;
        if (data.tables) {
          data.tables.forEach(cvT => {
            window.setTableStatus(cvT.id, cvT.status, false);
            const local = window.tables.find(t => t.id === cvT.id);
            if (local && cvT.status === "WAITING") local.waitTime = cvT.wait_sec;
          });
          syncVisuals();
        }
      };
      ws.onclose = () => { liveCVConnected = false; badge.classList.remove("connected"); badge.textContent = "🔴 Python CV: ожидание"; setTimeout(connectWS, 3000); };
    })();

    let lastTime = performance.now();
    function animate(now) {
      requestAnimationFrame(animate);
      const dt = ((now - lastTime) / 1000) * simSpeed;
      lastTime = now;

      if (!liveCVConnected) {
        window.tables.forEach(t => {
          if (t.status === "WAITING") t.waitTime += dt;
          if (autoSim && Math.random() < 0.005 * dt) {
            const flow = { FREE: "WAITING", WAITING: "SERVED", SERVED: "DIRTY", AWAY: "SERVED", DIRTY: "FREE" };
            window.setTableStatus(t.id, flow[t.status] || "FREE", true);
          }
        });
        updateSelectedPanel();
        updateWaiterPhone();
      }

      controls.update();
      renderer.render(scene, camera);
    }

    syncVisuals();
    animate(performance.now());
  </script>
</body>
</html>