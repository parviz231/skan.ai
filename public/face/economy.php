<?php
session_start();
require_once("header.php");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экономический клуб — РИТМ</title>
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
            background: linear-gradient(135deg, #1a1a2e, #2d2d44, #4a1942);
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 30px 30px;
        }
        .club-hero::before {
            content: '📈';
            position: absolute;
            right: -50px;
            top: -50px;
            font-size: 250px;
            opacity: 0.05;
        }
        .club-hero .container { position: relative; z-index: 2; }
        .club-hero h1 {
            font-size: 48px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .club-hero h1 .highlight { color: #fcd34d; }
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
            background: rgba(252,211,77,0.15);
            border: 1px solid rgba(252,211,77,0.2);
            border-radius: 100px;
            color: #fcd34d;
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
        .section-title .highlight { color: #fcd34d; }
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
            background: rgba(252,211,77,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fcd34d;
            margin-bottom: 16px;
        }
        .info-card h4 { font-size: 18px; font-weight: 600; margin-bottom: 6px; }
        .info-card p { color: #7f8c8d; font-size: 14px; line-height: 1.6; }

        .btn-join {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 36px;
            background: linear-gradient(135deg, #fcd34d, #f59e0b);
            color: #1a2332;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(252,211,77,0.35);
        }
        .btn-join:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(252,211,77,0.45);
            color: #1a2332;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .gallery-grid img {
            width: 100%;
            height: 200px;
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
            background: linear-gradient(135deg, #fcd34d, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: #1a2332;
        }
        .teacher-card .info h4 { font-size: 20px; font-weight: 700; }
        .teacher-card .info p { color: #7f8c8d; font-size: 14px; }

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
        <div class="badge-club"><i class="fas fa-chart-line"></i> Экономический клуб</div>
        <h1>📊 Экономика <span class="highlight">&amp; Финансы</span></h1>
        <p>Изучай финансовую грамотность, экономику и бизнес-планирование</p>
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
                    <div class="icon"><i class="fas fa-chart-pie"></i></div>
                    <h4>Финансовая грамотность</h4>
                    <p>Основы управления личными финансами</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-lightbulb"></i></div>
                    <h4>Бизнес-идеи</h4>
                    <p>Разработано 15+ стартап-проектов</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                    <h4>5 побед</h4>
                    <p>На экономических олимпиадах</p>
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
                    <div class="avatar">Ю</div>
                    <div class="info">
                        <h4>Юнусова Ш.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Кандидат экономических наук</p>
                        <p style="font-size:13px;color:#7f8c8d;">Опыт преподавания — 10 лет</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="teacher-card">
                    <div class="avatar">К</div>
                    <div class="info">
                        <h4>Каримов А.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Финансовый аналитик</p>
                        <p style="font-size:13px;color:#7f8c8d;">Бизнес-консультант</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <h2 class="section-title">📸 Фотогалерея</h2>
        <p class="section-sub">Моменты занятий и бизнес-игр</p>
        <div class="gallery-grid">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=300&fit=crop" alt="Экономика 1">
            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&h=300&fit=crop" alt="Экономика 2">
            <img src="https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=400&h=300&fit=crop" alt="Экономика 3">
        </div>
    </div>
</div>

<div class="section" id="join">
    <div class="container text-center">
        <h2 class="section-title">Готов <span class="highlight">инвестировать</span> в будущее?</h2>
        <p class="section-sub" style="max-width:500px;margin:0 auto 32px;">Присоединяйся к экономическому клубу и становись финансово грамотным!</p>
        <a href="#" class="btn-join">
            <i class="fas fa-pen"></i> Записаться сейчас
        </a>
    </div>
</div>

</body>
</html>