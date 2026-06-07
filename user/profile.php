<?php
// ============================================
// ФАЙЛ: user/profile.php
// ОТВЕЧАЕТ ЗА: личный кабинет пользователя
// ============================================

// ----- 1. ПОДКЛЮЧЕНИЕ НЕОБХОДИМЫХ ФАЙЛОВ -----
// Определяем корень проекта (поднимаемся из папки /user/ в корень)
// __DIR__ = C:/OSPanel/domains/bobshop/user/
// dirname(__DIR__) = C:/OSPanel/domains/bobshop/
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Подключаем файл с функциями для ссылок (ВАЖНО: подключать ПЕРВЫМ)
require_once ROOT_PATH . 'includes/paths.php';

// Подключаем остальные необходимые файлы
require_once ROOT_PATH . 'includes/config.php';      // Настройки БД
require_once ROOT_PATH . 'includes/functions.php';   // Общие функции
require_once ROOT_PATH . 'includes/userAuth.php';    // Работа с пользователями
require_once ROOT_PATH . 'includes/sessionManager.php'; // Управление сессиями

// ----- 2. ПРОВЕРКА АВТОРИЗАЦИИ -----
// Если пользователь не вошел в систему - отправляем на страницу входа
if (!SessionManager::isUserLoggedIn()) {
    // url('/user/login.php') превратится в http://bobshop.local/user/login.php
    header('Location: ' . url('/user/login.php'));
    exit; // Останавливаем выполнение скрипта
}

// ----- 3. ПОЛУЧЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ -----
$userAuth = new UserAuth($pdo);
$currentUser = $userAuth->getUserById(SessionManager::getCurrentUserId());

// Если пользователь не найден в базе данных - выходим из системы
if (!$currentUser) {
    SessionManager::logout();
    header('Location: ' . url('/user/login.php?error=session_expired'));
    exit;
}

// ----- 4. ОБРАБОТКА ФОРМ (ОБНОВЛЕНИЕ ПРОФИЛЯ, СМЕНА ПАРОЛЯ) -----
// ... (ваш существующий код обработки форм) ...

// ----- 5. ПОЛУЧЕНИЕ ИСТОРИИ ЗАКАЗОВ -----
function getUserOrders($userId) {
    global $pdo;
    // ... (ваша существующая функция) ...
}

$userOrders = getUserOrders($currentUser['id']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Bob Marley Auto Parts</title>

    <!-- ПОДКЛЮЧЕНИЕ СТИЛЕЙ -->
    <!-- asset('css/style.css') превратится в http://bobshop.local/css/style.css -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
<!-- ========== ШАПКА САЙТА ========== -->
<!-- Подключается из файла includes/header.php -->
<?php include ROOT_PATH . 'includes/header.php'; ?>

<!-- ========== ОСНОВНОЙ КОНТЕНТ ========== -->
<div class="container">
    <!-- Приветствие пользователя -->
    <div class="userWelcome">
        <h2>👋 Привет, <?php echo htmlspecialchars($currentUser['fullName'] ?? 'Пользователь'); ?>!</h2>
        <p>Добро пожаловать в ваш личный кабинет</p>
    </div>

    <!-- Блок с сообщениями об успехе/ошибке -->
    <?php if (!empty($successMessage)): ?>
        <div class="alert alertSuccess"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alertError"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <!-- ========== ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ ========== -->
    <div class="profileSection">
        <h3>📊 Информация о профиле</h3>

        <!-- Форма обновления профиля -->
        <form method="POST" action="">
            <input type="hidden" name="updateProfile" value="1">

            <!-- Поля формы (ваш существующий код) -->
            <!-- ... -->

            <button type="submit" class="btn btnPrimary">💾 Сохранить изменения</button>
        </form>
    </div>

    <!-- ========== СМЕНА ПАРОЛЯ ========== -->
    <div class="profileSection">
        <h3>🔒 Смена пароля</h3>

        <!-- Форма смены пароля -->
        <form method="POST" action="">
            <input type="hidden" name="changePassword" value="1">

            <!-- Поля формы (ваш существующий код) -->
            <!-- ... -->

            <button type="submit" class="btn btnPrimary">🔄 Сменить пароль</button>
        </form>
    </div>

    <!-- ========== ИСТОРИЯ ЗАКАЗОВ ========== -->
    <div class="profileSection">
        <h3>📦 История заказов</h3>

        <?php if (empty($userOrders)): ?>
            <p>У вас еще нет заказов</p>
            <!-- mainUrl('products.php') ведет на /main/products.php -->
            <a href="<?php echo mainUrl('products.php'); ?>" class="btn btnPrimary">
                🛒 Сделать первый заказ
            </a>
        <?php else: ?>
            <!-- Список заказов (ваш существующий код) -->
            <!-- ... -->
        <?php endif; ?>
    </div>
</div>

<!-- ========== ПОДВАЛ САЙТА ========== -->
<!-- Подключается из файла includes/footer.php -->
<?php include ROOT_PATH . 'includes/footer.php'; ?>
</body>
</html>