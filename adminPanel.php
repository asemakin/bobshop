<?php
// Включаем сессии
session_start();

// Данные для входа (позже можно хранить в БД)
$adminLogin = 'admin';
$adminPassword = '1234'; // простой пароль для начала

// Подключение к БД
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

// === ОБРАБОТКА ВХОДА ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];

    if ($login === $adminLogin && $password === $adminPassword) {
        $_SESSION['isAdmin'] = true;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

// === ОБРАБОТКА ВЫХОДА ===
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: adminPanel.php");
    exit;
}

// === ОБРАБОТКА ОБНОВЛЕНИЯ НАСТРОЕК ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateSettings']) && isset($_SESSION['isAdmin'])) {
    $newThreshold = (float)$_POST['discountThreshold'];
    $newRate = (float)$_POST['discountRate'];

    // Обновляем значения в таблице settings
    $db->query("UPDATE settings SET settingValue = '{$newThreshold}' WHERE settingKey = 'discountThreshold'");
    $db->query("UPDATE settings SET settingValue = '{$newRate}' WHERE settingKey = 'discountRate'");
    $success = "Настройки успешно обновлены!";
}

// === ПОЛУЧАЕМ ТЕКУЩИЕ НАСТРОЙКИ ===
$settings = [];
$res = $db->query("SELECT settingKey, settingValue FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['settingKey']] = $row['settingValue'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f0f0f0;
            padding: 40px;
        }
        .panel {
            background: white;
            padding: 30px;
            max-width: 400px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        .message {
            color: green;
            text-align: center;
        }
        .error {
            color: red;
            text-align: center;
        }
        .logout {
            text-align: center;
            margin-top: 20px;
        }
        .logout a {
            color: red;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="panel">
    <?php if (!isset($_SESSION['isAdmin'])): ?>
        <h2>Вход в админку</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="login" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
    <?php else: ?>
        <h2>Настройки скидки</h2>
        <?php if (isset($success)): ?>
            <div class="message"><?= $success ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Порог суммы (₽), от которой действует скидка:</label>
            <input type="number" name="discountThreshold" step="0.01" value="<?= htmlspecialchars($settings['discountThreshold']) ?>" required>

            <label>Размер скидки (например, 0.10 = 10%):</label>
            <input type="number" name="discountRate" step="0.01" min="0" max="1" value="<?= htmlspecialchars($settings['discountRate']) ?>" required>

            <button type="submit" name="updateSettings">Сохранить настройки</button>
        </form>

        <div class="logout">
            <a href="?logout=1">Выйти из админки</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

