<?php
// Настройки подключения к базе данных
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');

// Проверка соединения
if ($db->connect_error) {
    die("Ошибка подключения к базе данных: " . $db->connect_error);
}

// Установка кодировки
$db->set_charset("utf8mb4");