<?php
// includes/paths.php

// Автоматическое определение базового пути
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);

// Убираем лишние слеши
$basePath = rtrim($scriptPath, '/') . '/';

// Определяем абсолютные пути
define('BASE_PATH', $basePath);
define('SITE_URL', $protocol . '://' . $host . $basePath);

// Функция для генерации правильных URL
function url($path = '') {
    return BASE_PATH . ltrim($path, '/');
}

