<?php
/**
 * Конфигурация базы данных и основные настройки
 * Bob Marley Auto Parts - Конфигурационный файл
 */

// Включение отображения ошибок для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Параметры подключения к базе данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'bobMarleyAutoParts');
define('DB_USER', 'root');
define('DB_PASS', '');

// Базовый URL сайта
define('BASE_URL', 'http://localhost/bob-marley-auto-parts');

// Инициализация сессии
session_start();

// Создание подключения к базе данных
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Инициализация корзины в сессии, если её нет
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

