<?php
/**
 * Менеджер сессий для Bob Marley Auto Parts
 * Управление пользовательскими сессиями и авторизацией
 */

// Проверяем, не запущена ли уже сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем интеграцию корзины
require_once 'cartIntegration.php';

class SessionManager {

    /**
     * Начало сессии пользователя после успешного входа
     * @param array $userData - данные пользователя
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
     * @return bool
     */
    public static function isUserLoggedIn() {
        return isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;
    }

    /**
     * Получение ID текущего пользователя
     * @return int|null
     */
    public static function getCurrentUserId() {
        return $_SESSION['userId'] ?? null;
    }

    /**
     * Получение имени пользователя
     * @return string
     */
    public static function getUserName() {
        return $_SESSION['userName'] ?? 'Гость';
    }

    /**
     * Получение email пользователя
     * @return string
     */
    public static function getUserEmail() {
        return $_SESSION['userEmail'] ?? '';
    }

    /**
     * Выход пользователя из системы
     */
    public static function logout() {
        // Сохраняем корзину перед выходом
        if (isset($_SESSION['userId'])) {
            saveCartOnLogout($_SESSION['userId']);
        }

        // Очищаем все данные сессии
        $_SESSION = array();

        // Если нужно уничтожить cookie сессии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Уничтожаем сессию
        session_destroy();

        // Перенаправляем на главную страницу
        header('Location: ../index.php');
        exit;
    }
}
