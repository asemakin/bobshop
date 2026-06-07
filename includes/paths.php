<?php
// ============================================
// ФАЙЛ: includes/paths.php
// ОТВЕЧАЕТ ЗА: правильное формирование всех ссылок на сайте
// ============================================

// ----- ОПРЕДЕЛЕНИЕ АДРЕСА САЙТА -----
// Автоматически определяем протокол (http или https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
// Получаем домен (например: bobshop.local, localhost, mysite.com)
$host = $_SERVER['HTTP_HOST'];

// Полный адрес сайта (например: http://bobshop.local)
define('SITE_URL', $protocol . '://' . $host);

// ----- ГЛАВНАЯ ФУНКЦИЯ ДЛЯ ССЫЛОК -----
// Используйте эту функцию для ВСЕХ ссылок на сайте
// Примеры использования:
//   url('/')                 -> http://bobshop.local/main/index.php
//   url('/user/profile.php') -> http://bobshop.local/user/profile.php
//   url('/main/cart.php')    -> http://bobshop.local/main/cart.php
function url($path = '') {
    // Убираем слеш в начале, если он есть (чтобы не было двойных слешей)
    $path = ltrim($path, '/');

    // ЕСЛИ: ссылка пустая или просто "/" - ведем на ГЛАВНУЮ страницу
    if (empty($path) || $path == '/') {
        return SITE_URL . '/main/index.php';  // Главная страница сайта
    }

    // ИНАЧЕ: добавляем адрес сайта к пути
    return SITE_URL . '/' . $path;
}

// ----- ФУНКЦИЯ ДЛЯ СТРАНИЦ В ПАПКЕ MAIN -----
// Используйте для ссылок на страницы магазина: cart.php, products.php, checkout.php
// Примеры:
//   mainUrl('cart.php')     -> http://bobshop.local/main/cart.php
//   mainUrl('products.php') -> http://bobshop.local/main/products.php
//   mainUrl('index.php')    -> http://bobshop.local/main/index.php (но лучше использовать url('/'))
function mainUrl($page = '') {
    // Если страница не указана или запрошен index.php - возвращаем ГЛАВНУЮ
    if (empty($page) || $page == 'index.php') {
        return SITE_URL . '/main/index.php';
    }

    // Иначе формируем путь к файлу в папке main
    return SITE_URL . '/main/' . ltrim($page, '/');
}

// ----- ФУНКЦИЯ ДЛЯ СТРАНИЦ ПОЛЬЗОВАТЕЛЯ -----
// Используйте для ссылок на: profile.php, login.php, register.php, logout.php
// Примеры:
//   userUrl('profile.php')  -> http://bobshop.local/user/profile.php
//   userUrl('login.php')    -> http://bobshop.local/user/login.php
function userUrl($page = '') {
    return SITE_URL . '/user/' . ltrim($page, '/');
}

// ----- ФУНКЦИЯ ДЛЯ СТАТИЧЕСКИХ ФАЙЛОВ -----
// Используйте для: CSS, JavaScript, изображений, шрифтов
// Примеры:
//   asset('css/style.css')    -> http://bobshop.local/css/style.css
//   asset('js/script.js')     -> http://bobshop.local/js/script.js
//   asset('images/logo.png')  -> http://bobshop.local/images/logo.png
function asset($path) {
    return SITE_URL . '/' . ltrim($path, '/');
}
?>


