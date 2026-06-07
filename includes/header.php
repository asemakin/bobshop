<?php
// ============================================
// ФАЙЛ: includes/header.php
// ОТВЕЧАЕТ ЗА: шапку сайта (меню, логотип, навигацию)
// ============================================

// Определяем корневую папку проекта (поднимаемся на уровень выше из папки includes/)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// Подключаем функции для ссылок, если они еще не подключены
if (!function_exists('url')) {
    require_once ROOT_PATH . 'includes/paths.php';
}

// Получаем данные корзины (количество товаров)
$cart = getCart();
$cartItemsCount = count($cart['items']);

// Определяем, на какой мы сейчас странице (для подсветки активного пункта меню)
$currentPage = basename($_SERVER['PHP_SELF']); // Например: index.php, cart.php, profile.php
$currentFolder = basename(dirname($_SERVER['PHP_SELF'])); // Например: main, user
?>

<header class="header">
    <div class="container">
        <!-- ========== ЛОГОТИП ========== -->
        <!-- При клике на логотип переходим на главную страницу -->
        <div class="logo">
            <a href="<?php echo url('/'); ?>"> <!-- url('/') ведет на /main/index.php -->
                <h1>🎵 Bob Marley Auto Parts 🎵</h1>
                <p>One Love, One Heart, Quality Auto Parts!</p>
            </a>
        </div>

        <!-- ========== НАВИГАЦИОННОЕ МЕНЮ ========== -->
        <nav class="navbar">
            <ul class="navMenu">
                <!-- ГЛАВНАЯ - всегда ведет на /main/index.php -->
                <li class="<?php echo ($currentPage == 'index.php' && $currentFolder == 'main') ? 'active' : ''; ?>">
                    <a href="<?php echo url('/'); ?>">🏠 Главная</a>
                </li>

                <!-- КАТАЛОГ - ведет на /main/products.php -->
                <li class="<?php echo ($currentPage == 'products.php') ? 'active' : ''; ?>">
                    <a href="<?php echo mainUrl('products.php'); ?>">🛒 Каталог</a>
                </li>

                <!-- КОРЗИНА - ведет на /main/cart.php -->
                <li class="<?php echo ($currentPage == 'cart.php') ? 'active' : ''; ?>">
                    <a href="<?php echo mainUrl('cart.php'); ?>">
                        🛒 Корзина
                        <!-- Если в корзине есть товары, показываем счетчик -->
                        <?php if ($cartItemsCount > 0): ?>
                            <span class="cartBadge">(<?php echo $cartItemsCount; ?>)</span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- ОФОРМЛЕНИЕ - ведет на /main/checkout.php -->
                <li class="<?php echo ($currentPage == 'checkout.php') ? 'active' : ''; ?>">
                    <a href="<?php echo mainUrl('checkout.php'); ?>">📦 Оформление</a>
                </li>

                <!-- ========== БЛОК ПОЛЬЗОВАТЕЛЯ ========== -->
                <?php if (SessionManager::isUserLoggedIn()): ?>
                    <!-- ПОКАЗЫВАЕМ ДЛЯ АВТОРИЗОВАННЫХ ПОЛЬЗОВАТЕЛЕЙ -->
                    <!-- Профиль пользователя -->
                    <li><a href="<?php echo userUrl('profile.php'); ?>">👤 <?php echo htmlspecialchars(SessionManager::getUserName()); ?></a></li>
                    <!-- Выход из системы -->
                    <li><a href="<?php echo userUrl('logout.php'); ?>">🚪 Выйти</a></li>
                <?php else: ?>
                    <!-- ПОКАЗЫВАЕМ ДЛЯ НЕАВТОРИЗОВАННЫХ ПОЛЬЗОВАТЕЛЕЙ -->
                    <!-- Вход в систему -->
                    <li><a href="<?php echo userUrl('login.php'); ?>">🔑 Войти</a></li>
                    <!-- Регистрация -->
                    <li><a href="<?php echo userUrl('register.php'); ?>">📝 Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

