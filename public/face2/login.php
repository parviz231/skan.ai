<?php
session_start();

require_once("config2.php");

$error = "";

if (isset($_POST['username'])) {

    if ($conn->connect_error) {
        die("Ошибка подключения: " . $conn->connect_error);
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT user_id, nickname
        FROM students
        WHERE nickname = ?
        AND password = ?
        LIMIT 1
    ");

    if (!$stmt) {
        die("Ошибка SQL: " . $conn->error);
    }

    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        $_SESSION['username1'] = $row['nickname'];
        $_SESSION['id1'] = (int)$row['user_id'];

        $stmt->close();

        header("Location: profile.php");
        exit;

    } else {

        $stmt->close();

        $check_stmt = $conn->prepare("
            SELECT user_id
            FROM students
            WHERE nickname = ?
            LIMIT 1
        ");

        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Неправильный пароль";
        } else {
            $error = "Такого пользователя не существует!";
        }

        $check_stmt->close();
    }
}

require_once("header.php");
?>

<style>
.login-container {
    background:#fff;
    padding:30px;
    border-radius:20px;
    width:300px;
    text-align:center;
    margin-top:40px;
    box-shadow:0 10px 25px rgba(0,0,0,0.15);
    animation:fadeIn 0.8s ease;
}

@keyframes fadeIn {
    from {
        opacity:0;
        transform:translateY(20px);
    }

    to {
        opacity:1;
        transform:translateY(0);
    }
}

.login-container h2 {
    margin-bottom:20px;
    color:#2c3e50;
}

.login-container input {
    width:100%;
    padding:10px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:14px;
    box-sizing:border-box;
}

.login-container input:focus {
    border-color:#3498db;
    outline:none;
    box-shadow:0 0 5px rgba(52,152,219,0.6);
}

.login-container button {
    width:100%;
    padding:10px;
    margin-top:15px;
    background:#2C4361;
    border:none;
    border-radius:6px;
    color:#fff;
    font-size:16px;
    cursor:pointer;
    transition:background 0.3s;
}

.login-container button:hover {
    background:rgba(44,128,180,0.59);
}

.login-container .links {
    margin-top:15px;
    font-size:14px;
}

.login-container .links a {
    color:#2C4361;
    text-decoration:none;
}

.login-container .links a:hover {
    text-decoration:underline;
}

.animate {
    opacity:0;
    transform:translateY(50px);
    transition:all 1s ease;
}

.animate.show {
    opacity:1;
    transform:translateY(0);
}

.error {
    color:red;
    display:block;
    margin-top:5px;
}
</style>

</head>

<body>

<center>

<div class="login-container animate">

    <h2>Вход в систему</h2>

    <form action="login.php" method="post">

        <input
            type="text"
            name="username"
            placeholder="Логин"
            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
            required
        >

        <?php if ($error == "Такого пользователя не существует!"): ?>

            <span class="error">
                <?= htmlspecialchars($error) ?>
            </span>

        <?php endif; ?>

        <input
            type="password"
            name="password"
            placeholder="Пароль"
            required
        >

        <?php if ($error == "Неправильный пароль"): ?>

            <span class="error">
                <?= htmlspecialchars($error) ?>
            </span>

        <?php endif; ?>

        <button type="submit">
            Войти
        </button>

        <div class="links">
            <a href="#">
                Забыли пароль?
            </a>
        </div>

    </form>

</div>

</center>

</body>

</html>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const elements =
        document.querySelectorAll('.animate');

    const observer =
        new IntersectionObserver((entries) => {

            entries.forEach(entry => {

                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                }

            });

        }, {
            threshold:0.2
        });

    elements.forEach(el =>
        observer.observe(el)
    );

});
</script>

<?php require_once("js.php"); ?>