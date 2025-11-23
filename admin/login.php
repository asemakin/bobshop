<?php
/**
 * Страница входа в админ-панель
 * Bob Marley Auto Parts
 */
session_start();

// Если пользователь уже авторизован - перенаправляем в админку
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ПРОВЕРЯЕМ ЛОГИН И ПАРОЛЬ
    // В реальном проекте пароль должен быть захэширован!
    $valid_username = 'admin';
    $valid_password = '1234'; // Пароль можно поменять

    if ($username === $valid_username && $password === $valid_password) {
        // Сохраняем в сессию что пользователь авторизован
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;

        // Перенаправляем в админ-панель
        header('Location: index.php');
        exit;
    } else {
        $error = '❌ Неверный логин или пароль';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }
        .login-logo {
            color: #f9a602;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #1a2f1a 0%, #2d5a2d 100%);">
<div class="login-container">
    <div class="login-logo">🎵</div>
    <h1 style="color: #1a4721; margin-bottom: 0.5rem;">Bob Marley Auto Parts</h1>
    <p style="color: #666; margin-bottom: 2rem;">Админ-панель</p>

    <?php if ($error): ?>
        <div style="background: #e74c3c; color: white; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div style="display: grid; gap: 1rem;">
            <div>
                <input type="text"
                       name="username"
                       class="formControl"
                       placeholder="Логин"
                       required
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div>
                <input type="password"
                       name="password"
                       class="formControl"
                       placeholder="Пароль"
                       required>
            </div>
            <button type="submit" class="btn btnSuccess" style="width: 100%;">
                🔐 Войти в админку
            </button>
        </div>
    </form>

    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">
        <small style="color: #666;">
            <strong>Тестовые данные:</strong><br>
            Логин: <code>admin</code><br>
            Пароль: <code>marley123</code>
        </small>
    </div>
</div>
</body>
</html>

