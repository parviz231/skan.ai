

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шахматный клуб — РИТМ</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f0f4f8;
            color: #1a2332;
        }

        .club-hero {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 30px 30px;
        }
        .club-hero::before {
            content: '♛';
            position: absolute;
            right: -50px;
            top: -50px;
            font-size: 300px;
            opacity: 0.05;
            color: #fff;
        }
        .club-hero::after {
            content: '♚';
            position: absolute;
            left: -30px;
            bottom: -30px;
            font-size: 250px;
            opacity: 0.05;
            color: #fff;
        }
        .club-hero .container { position: relative; z-index: 2; }
        .club-hero h1 {
            font-size: 48px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .club-hero h1 .highlight { color: #fbbf24; }
        .club-hero p {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            max-width: 500px;
            margin-top: 12px;
            line-height: 1.7;
        }
        .club-hero .badge-club {
            display: inline-block;
            padding: 6px 20px;
            background: rgba(251,191,36,0.15);
            border: 1px solid rgba(251,191,36,0.2);
            border-radius: 100px;
            color: #fbbf24;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .section { padding: 60px 0; }
        .section-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1a2332;
        }
        .section-title .highlight { color: #fbbf24; }
        .section-sub {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 32px;
        }

        .info-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px 32px;
            border: 1px solid #e8ecf1;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: 0.3s;
            height: 100%;
        }
        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.08);
        }
        .info-card .icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: rgba(251,191,36,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fbbf24;
            margin-bottom: 16px;
        }
        .info-card h4 { font-size: 18px; font-weight: 600; margin-bottom: 6px; }
        .info-card p { color: #7f8c8d; font-size: 14px; line-height: 1.6; }

        .schedule-table {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .schedule-table table { width: 100%; border-collapse: collapse; }
        .schedule-table th {
            background: #f8f9fc;
            padding: 14px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #4a5a6e;
            border-bottom: 2px solid #e8ecf1;
        }
        .schedule-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f2f5;
            font-size: 14px;
        }
        .schedule-table tr:hover td { background: #fafbfc; }
        .schedule-table .status-active {
            display: inline-block;
            padding: 2px 14px;
            background: #d4edda;
            color: #155724;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
        }
        .schedule-table .status-upcoming {
            display: inline-block;
            padding: 2px 14px;
            background: #fff3cd;
            color: #856404;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-join {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 36px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1a2332;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(251,191,36,0.35);
        }
        .btn-join:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(251,191,36,0.45);
            color: #1a2332;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .gallery-grid img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 12px;
            transition: 0.3s;
            border: 1px solid #e8ecf1;
        }
        .gallery-grid img:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }

        .teacher-card {
            display: flex;
            align-items: center;
            gap: 24px;
            background: #fff;
            padding: 24px 32px;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .teacher-card .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: #fff;
        }
        .teacher-card .info h4 { font-size: 20px; font-weight: 700; }
        .teacher-card .info p { color: #7f8c8d; font-size: 14px; }

        /* ===== FOOTER ===== */
        .footer {
            background: #1a2332;
            color: rgba(255,255,255,0.7);
            padding: 30px 0;
            text-align: center;
            margin-top: 40px;
            border-radius: 10px 10px 0 0;
        }
        .footer a {
            color: #fbbf24;
            text-decoration: none;
        }
        .footer a:hover { color: #fff; }

        @media (max-width: 768px) {
            .club-hero h1 { font-size: 32px; }
            .gallery-grid { grid-template-columns: 1fr; }
            .teacher-card { flex-direction: column; text-align: center; }
            .section-title { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="club-hero">
    <div class="container">
        <div class="badge-club"><i class="fas fa-chess-king"></i> Шахматный клуб</div>
        <h1>♛ Шахматный <span class="highlight">клуб</span></h1>
        <p>Развивай логику, стратегическое мышление и интеллект вместе с нами</p>
        <a href="#join" class="btn-join" style="margin-top:20px;">
            <i class="fas fa-users"></i> Присоединиться
        </a>
    </div>
</div>

<div class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-user-graduate"></i></div>
                    <h4>30+ учеников</h4>
                    <p>Активных участников клуба, которые развивают свои навыки</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                    <h4>12 побед</h4>
                    <p>Призовых мест в городских и республиканских турнирах</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-medal"></i></div>
                    <h4>5 мастеров</h4>
                    <p>Учеников получили спортивные разряды по шахматам</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section" style="background:#fff;padding:60px 0;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="teacher-card">
                    <div class="avatar">Н</div>
                    <div class="info">
                        <h4>Норматов М.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Тренер высшей категории</p>
                        <p style="font-size:13px;color:#7f8c8d;">Опыт преподавания — 15 лет</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="teacher-card">
                    <div class="avatar">А</div>
                    <div class="info">
                        <h4>Алимов Р.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Мастер спорта</p>
                        <p style="font-size:13px;color:#7f8c8d;">Чемпион города по шахматам 2024</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <h2 class="section-title">📅 Расписание <span class="highlight">занятий</span></h2>
        <p class="section-sub">Присоединяйся к нам в удобное время</p>
        <div class="schedule-table">
            <table>
                <thead>
                    <tr>
                        <th>День</th>
                        <th>Время</th>
                        <th>Группа</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Среда</strong></td>
                        <td>13:30 — 15:30</td>
                        <td>Начинающие</td>
                        <td><span class="status-active">Идёт набор</span></td>
                    </tr>
                    <tr>
                        <td><strong>Пятница</strong></td>
                        <td>13:30 — 15:30</td>
                        <td>Продвинутые</td>
                        <td><span class="status-active">Идёт набор</span></td>
                    </tr>
                    <tr>
                        <td><strong>Суббота</strong></td>
                        <td>10:00 — 12:00</td>
                        <td>Турнирная группа</td>
                        <td><span class="status-upcoming">Скоро</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="section" style="background:#fff;padding:60px 0;">
    <div class="container">
        <h2 class="section-title">📸 Фотогалерея</h2>
        <p class="section-sub">Моменты наших занятий и турниров</p>
        <div class="gallery-grid">
            <img src="https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=600&h=400&fit=crop" alt="Шахматы 1">
            <img src="https://images.unsplash.com/photo-1586165368502-1bad197a6461?w=600&h=400&fit=crop" alt="Шахматы 2">
            <img src="https://images.unsplash.com/photo-1543946207-1f5c5e7ea53c?w=600&h=400&fit=crop" alt="Шахматы 3">
        </div>
    </div>
</div>

<div class="section" id="join">
    <div class="container text-center">
        <h2 class="section-title">Готов <span class="highlight">начать</span>?</h2>
        <p class="section-sub" style="max-width:500px;margin:0 auto 32px;">Присоединяйся к шахматному клубу и развивай свои навыки!</p>
        <a href="#" class="btn-join">
            <i class="fas fa-pen"></i> Записаться сейчас
        </a>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<div class="footer">
    <div class="container">
        <p>© 2026 <strong style="color:#fbbf24;">РИТМ</strong> — Шахматный клуб. Все права защищены.</p>
        <p style="font-size:13px;opacity:0.6;">
            <a href="index.php">Главная</a> • 
            <a href="schedule.php">Расписание</a> • 
            <a href="#">Контакты</a>
        </p>
    </div>
</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>