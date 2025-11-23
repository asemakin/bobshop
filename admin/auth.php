<?php
/**
 * Проверка авторизации для админ-панели
 * Подключается в начале каждого файла админки
 */

require_once '../includes/init.php';

// Если пользователь не авторизован - перенаправляем на страницу входа
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

