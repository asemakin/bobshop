<?php
// Подключаем необходимые файлы
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/userAuth.php';
require_once '../includes/sessionManager.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (SessionManager::isUserLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

// Создаем объект для работы с пользователями
$userAuth = new UserAuth($pdo);
$error = '';
$success = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные из формы
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $fullName = trim($_POST['fullName']);
    $phone = trim($_POST['phone']);

    // Валидация данных
    if (empty($email) || empty($password) || empty($fullName)) {
        $error = 'Все обязательные поля должны быть заполнены';
    } elseif ($password !== $confirmPassword) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        // Пытаемся зарегистрировать пользователя
        $result = $userAuth->registerUser($email, $password, $fullName, $phone);

        if ($result['success']) {
            $success = $result['message'];
            // Автоматически логиним пользователя после успешной регистрации
            $loginResult = $userAuth->loginUser($email, $password);
            if ($loginResult['success']) {
                SessionManager::startUserSession($loginResult['user']);
                header('Location: ../index.php');
                exit;
            }
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
    <title>Регистрация - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<!-- Подключаем шапку сайта -->
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="authForm">
        <h2>Регистрация</h2>

        <!-- Вывод сообщений об ошибках -->
        <?php if ($error): ?>
            <div class="alert alertError"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Вывод сообщений об успехе -->
        <?php if ($success): ?>
            <div class="alert alertSuccess"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Форма регистрации -->
        <form method="POST" action="">
            <div class="formGroup">
                <label for="fullName">Полное имя *</label>
                <input type="text" id="fullName" name="fullName"
                       value="<?php echo htmlspecialchars($_POST['fullName'] ?? ''); ?>" required>
            </div>

            <div class="formGroup">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="formGroup">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone"
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="formGroup">
                <label for="password">Пароль *</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="formGroup">
                <label for="confirmPassword">Подтвердите пароль *</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>

            <button type="submit" class="btn btnPrimary">Зарегистрироваться</button>
        </form>

        <p class="authLink">
            Уже есть аккаунт? <a href="login.php">Войдите</a>
        </p>
    </div>
</div>

<!-- Подключаем подвал сайта -->
<?php include '../includes/footer.php'; ?>
</body>
</html>
