<?php
// Настройки подключения к базе данных
$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost';
$user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'bob_auto_parts';

$db = new mysqli($host, $user, $pass, $name);

// Проверка соединения
if ($db->connect_error) {
    die("Ошибка подключения к базе данных: " . $db->connect_error);
}

// Установка кодировки
$db->set_charset("utf8mb4");