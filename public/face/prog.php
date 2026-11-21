<?php
session_start();
require_once("header.php");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Клуб программирования — РИТМ</title>
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
            background: linear-gradient(135deg, #0d1117, #161b22, #1f242f);
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 30px 30px;
        }
        .club-hero::before {
            content: '</>';
            position: absolute;
            right: -30px;
            top: -30px;
            font-size: 200px;
            opacity: 0.04;
            color: #fff;
            font-weight: 900;
        }
        .club-hero .container { position: relative; z-index: 2; }
        .club-hero h1 {
            font-size: 48px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .club-hero h1 .highlight { color: #58a6ff; }
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
            background: rgba(88,166,255,0.15);
            border: 1px solid rgba(88,166,255,0.2);
            border-radius: 100px;
            color: #58a6ff;
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
        .section-title .highlight { color: #58a6ff; }
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
            background: rgba(88,166,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #58a6ff;
            margin-bottom: 16px;
        }
        .info-card h4 { font-size: 18px; font-weight: 600; margin-bottom: 6px; }
        .info-card p { color: #7f8c8d; font-size: 14px; line-height: 1.6; }

        .code-block {
            background: #0d1117;
            border-radius: 12px;
            padding: 24px;
            color: #e6edf3;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
            overflow-x: auto;
            border: 1px solid #30363d;
        }
        .code-block .comment { color: #8b949e; }
        .code-block .keyword { color: #ff7b72; }
        .code-block .string { color: #a5d6ff; }
        .code-block .function { color: #d2a8ff; }
        .code-block .variable { color: #ffa657; }

        .btn-join {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 36px;
            background: linear-gradient(135deg, #58a6ff, #1f6feb);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(88,166,255,0.35);
        }
        .btn-join:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(88,166,255,0.45);
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
            background: linear-gradient(135deg, #58a6ff, #1f6feb);
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
            .code-block { font-size: 12px; padding: 16px; }
        }
    </style>
</head>
<body>

<div class="club-hero">
    <div class="container">
        <div class="badge-club"><i class="fas fa-code"></i> Клуб программирования</div>
        <h1>💻 Программирование <span class="highlight">&amp; IT</span></h1>
        <p>Создавай свои проекты, изучай языки программирования и становись IT-специалистом</p>
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
                    <div class="icon"><i class="fas fa-laptop-code"></i></div>
                    <h4>4 языка</h4>
                    <p>Изучаем Python, JavaScript, C++ и HTML/CSS</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-rocket"></i></div>
                    <h4>10+ проектов</h4>
                    <p>Создано учениками за последний год</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                    <h4>7 побед</h4>
                    <p>На олимпиадах по информатике</p>
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
                    <div class="avatar">А</div>
                    <div class="info">
                        <h4>Ачилов Х.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Старший преподаватель</p>
                        <p style="font-size:13px;color:#7f8c8d;">Опыт программирования — 10 лет</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="teacher-card">
                    <div class="avatar">М</div>
                    <div class="info">
                        <h4>Маматов С.</h4>
                        <p><i class="fas fa-star" style="color:#fbbf24;"></i> Full-stack разработчик</p>
                        <p style="font-size:13px;color:#7f8c8d;">Специалист по веб-технологиям</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <h2 class="section-title">📝 Пример <span class="highlight">кода</span></h2>
        <p class="section-sub">Вот так выглядит работа наших учеников</p>
        <div class="code-block">
            <span class="comment">// Простая программа на Python</span><br>
            <span class="keyword">def</span> <span class="function">hello_world</span>():<br>
            &nbsp;&nbsp;&nbsp;&nbsp;<span class="keyword">print</span>(<span class="string">"Привет, мир!"</span>)<br>
            <br>
            <span class="keyword">for</span> i <span class="keyword">in</span> <span class="function">range</span>(5):<br>
            &nbsp;&nbsp;&nbsp;&nbsp;hello_world()
        </div>
    </div>
</div>

<div class="section" style="background:#fff;padding:60px 0;">
    <div class="container">
        <h2 class="section-title">📸 Фотогалерея</h2>
        <p class="section-sub">Моменты занятий и хакатонов</p>
        <div class="gallery-grid">
            <img src="https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=300&fit=crop" alt="Программирование 1">
            <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=400&h=300&fit=crop" alt="Программирование 2">
            <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=400&h=300&fit=crop" alt="Программирование 3">
        </div>
    </div>
</div>

<div class="section" id="join">
    <div class="container text-center">
        <h2 class="section-title">Готов <span class="highlight">создавать</span>?</h2>
        <p class="section-sub" style="max-width:500px;margin:0 auto 32px;">Присоединяйся к клубу программирования и создавай будущее!</p>
        <a href="#" class="btn-join">
            <i class="fas fa-pen"></i> Записаться сейчас
        </a>
    </div>
</div>

</body>
</html>