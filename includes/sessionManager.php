<?php
/**
 * Менеджер сессий для Bob Marley Auto Parts
 * Управление пользовательскими сессиями и авторизацией
 */
require_once 'cartIntegration.php';
class SessionManager {

    /**
     * Начало сессии пользователя после успешного входа
     */
    public static function startUserSession($userData) {
        $_SESSION['userId'] = $userData['id'];
        $_SESSION['userEmail'] = $userData['email'];
        $_SESSION['userName'] = $userData['fullName'];
        $_SESSION['loggedIn'] = true;

        // Синхронизируем корзину при входе
        syncCartOnLogin($userData['id']);
    }

    /**
     * Проверка, авторизован ли пользователь
     */
    public static function isUserLoggedIn() {
        return isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;
    }

    /**
     * Получение ID текущего пользователя
     */
    public static function getCurrentUserId() {
        return $_SESSION['userId'] ?? null;
    }

    /**
     * Получение имени пользователя
     */
    public static function getUserName() {
        return $_SESSION['userName'] ?? 'Гость';
    }

    /**
     * Получение email пользователя
     */
    public static function getUserEmail() {
        return $_SESSION['userEmail'] ?? '';
    }

    public static function logout() {
        // Сохраняем корзину перед выходом (если нужно)
        if (isset($_SESSION['userId'])) {
            require_once __DIR__ . '/cartIntegration.php';
            saveCartOnLogout($_SESSION['userId']);
        }

        // Очищаем сессию
        $_SESSION = [];
        session_destroy();

        // Абсолютный путь для перенаправления
        echo '<script>window.location.href = "../main/index.php";</script>';
        exit;
    }
}