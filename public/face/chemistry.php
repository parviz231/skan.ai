<?php
session_start();
require_once("header.php");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Химический клуб — РИТМ</title>
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
            background: linear-gradient(135deg, #0c2340, #1a4a6e, #2d7d46);
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 30px 30px;
        }
        .club-hero::before {
            content: '🧪';
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
        .club-hero h1 .highlight { color: #34d399; }
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
            background: rgba(52,211,153,0.15);
            border: 1px solid rgba(52,211,153,0.2);
            border-radius: 100px;
            color: #34d399;
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
        .section-title .highlight { color: #34d399; }
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
            background: rgba(52,211,153,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #34d399;
            margin-bottom: 16px;
        }
        .info-card h4 { font-size: 18px; font-weight: 600; margin-bottom: 6px; }
        .info-card p { color: #7f8c8d; font-size: 14px; line-height: 1.6; }

        .btn-join {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 36px;
            background: linear-gradient(135deg, #34d399, #059669);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(52,211,153,0.35);
        }
        .btn-join:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(52,211,153,0.45);
            color: #fff;
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
            background: linear-gradient(135deg, #34d399, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: #fff;
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
        <div class="badge-club"><i class="fas fa-flask"></i> Химический клуб</div>
        <h1>🧪 Химия — <span class="highlight">это интересно</span></h1>
        <p>Изучай химию через эксперименты, опыты и научные исследования</p>
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
                    <div class="icon"><i class="fas fa-microscope"></i></div>
                    <h4>50+ опытов</h4>
                    <p>Проведено за последний учебный год</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                    <h4>8 побед</h4>
                    <p>На городских и республиканских олимпиадах</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-flask"></i></div>
                    <h4>Современная лаборатория</h4>
                    <p>Всё необходимое оборудование для экспериментов</p>
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
                    <div class="avatar">Ш</div>
                    <div class="info">
                        <h4>Шафиев Н.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Кандидат химических наук</p>
                        <p style="font-size:13px;color:#7f8c8d;">Опыт преподавания — 12 лет</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="teacher-card">
                    <div class="avatar">Р</div>
                    <div class="info">
                        <h4>Рахимова Д.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Магистр химии</p>
                        <p style="font-size:13px;color:#7f8c8d;">Специалист по органической химии</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <h2 class="section-title">📸 Фотогалерея</h2>
        <p class="section-sub">Моменты из лаборатории</p>
        <div class="gallery-grid">
            <img src="https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=400&h=300&fit=crop" alt="Химия 1">
            <img src="https://images.unsplash.com/photo-1576086213369-97a306d36557?w=400&h=300&fit=crop" alt="Химия 2">
            <img src="https://images.unsplash.com/photo-1581093588401-fbb62a02f120?w=400&h=300&fit=crop" alt="Химия 3">
        </div>
    </div>
</div>

<div class="section" id="join">
    <div class="container text-center">
        <h2 class="section-title">Готов <span class="highlight">экспериментировать</span>?</h2>
        <p class="section-sub" style="max-width:500px;margin:0 auto 32px;">Присоединяйся к химическому клубу и открывай мир науки!</p>
        <a href="#" class="btn-join">
            <i class="fas fa-pen"></i> Записаться сейчас
        </a>
    </div>
</div>

</body>
</html>