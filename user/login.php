<?php

require_once '../includes/init.php';
// Подключаем необходимые файлы
require_once '../includes/config.php';
//require_once '../includes/functions.php';
require_once '../includes/userAuth.php';
//require_once '../includes/sessionManager.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (SessionManager::isUserLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

// Создаем объект для работы с пользователями
$userAuth = new UserAuth($pdo);
$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные из формы
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Проверяем что все поля заполнены
    if (empty($email) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        // Пытаемся авторизовать пользователя
        $result = $userAuth->loginUser($email, $password);

        if ($result['success']) {
            // Успешная авторизация, начинаем сессию
            SessionManager::startUserSession($result['user']);
            // Перенаправляем на главную страницу
            header('Location: ../index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<!-- Подключаем шапку сайта -->
<?php //include '../includes/header.php'; ?>

<div class="container">
    <div class="authForm">
        <h2>Вход в аккаунт</h2>

        <!-- Вывод сообщений об ошибках -->
        <?php if ($error): ?>
            <div class="alert alertError"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Форма входа -->
        <form method="POST" action="">
            <div class="formGroup">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="formGroup">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btnPrimary">Войти</button>
        </form>

        <p class="authLink">
            Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
        </p>
    </div>
</div>

<!-- Подключаем подвал сайта -->
<?php include '../includes/footer.php'; ?>
</body>
</html>