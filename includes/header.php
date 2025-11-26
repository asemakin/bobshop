<?php
/**
 * Шапка сайта Bob Marley Auto Parts
 * Включает логотип и навигационное меню
 */

// Подключаем менеджер сессий для проверки авторизации
//require_once 'sessionManager.php';

// Подключаем функции если они еще не подключены
//require_once 'functions.php';
require_once 'init.php';
// Подключаем пути
require_once 'paths.php';

// Получаем данные корзины используя вашу функцию getCart()
$cart = getCart();
$cartItemsCount = count($cart['items']);
?>

<header class="header">
    <div class="container">
        <div class="logo">
            <h1>🎵 Bob Marley Auto Parts 🎵</h1>
            <p>One Love, One Heart, Quality Auto Parts!</p>
        </div>

        <nav class="navbar">
            <ul class="navMenu">
                <li><a href="<?php echo url('bobshop/index.php'); ?>">🏠 Главная</a></li>
                <li><a href="<?php echo url('bobshop/products.php'); ?>">🛒 Каталог</a></li>
                <li>
                    <a href="<?php echo url('bobshop/cart.php'); ?>">
                        🛒 Корзина
                        <?php if ($cartItemsCount > 0): ?>
                            <span class="cartBadge">
                                (<?php echo $cartItemsCount; ?>)
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="<?php echo url('bobshop/checkout.php'); ?>">📦 Оформление</a></li>

                <?php if (SessionManager::isUserLoggedIn()): ?>
                    <!-- Показываем для авторизованных пользователей -->
                    <li><a href="<?php echo url('bobshop/user/profile.php'); ?>">👤 <?php echo htmlspecialchars(SessionManager::getUserName()); ?></a></li>
                    <li><a href="<?php echo url('bobshop/user/logout.php'); ?>">🚪 Выйти</a></li>
                <?php else: ?>
                    <!-- Показываем для неавторизованных пользователей -->
                    <li><a href="<?php echo url('bobshop/user/login.php'); ?>">🔑 Войти</a></li>
                    <li><a href="<?php echo url('bobshop/user/register.php'); ?>">📝 Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>