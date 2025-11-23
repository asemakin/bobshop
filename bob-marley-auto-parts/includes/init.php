<?php
// includes/init.php - Централизованная инициализация

// Старт сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем конфигурацию БД
require_once __DIR__ . '/config.php';

// Подключаем все необходимые файлы
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cartIntegration.php';
require_once __DIR__ . '/sessionManager.php';

