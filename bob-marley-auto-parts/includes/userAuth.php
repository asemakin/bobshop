<?php
// Подключаем конфигурацию базы данных
require_once 'config.php';

/**
 * Класс для регистрации и авторизации пользователей
 */
class UserAuth {
    private $pdo;

    /**
     * Конструктор класса
     * @param PDO $pdo - объект подключения к БД
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Регистрация нового пользователя
     * @param string $email - email пользователя
     * @param string $password - пароль
     * @param string $fullName - полное имя
     * @param string $phone - телефон (опционально)
     * @return array - результат операции
     */
    public function registerUser($email, $password, $fullName, $phone = '') {
        // Проверяем существует ли пользователь с таким email
        if ($this->userExists($email)) {
            return [
                'success' => false,
                'message' => 'Пользователь с таким email уже существует'
            ];
        }

        // Хешируем пароль для безопасного хранения
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Подготавливаем SQL запрос
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (email, password, fullName, phone, createdAt) 
                 VALUES (?, ?, ?, ?, NOW())"
            );

            // Выполняем запрос с параметрами
            $stmt->execute([$email, $hashedPassword, $fullName, $phone]);

            return [
                'success' => true,
                'message' => 'Регистрация успешна'
            ];

        } catch (PDOException $e) {
            // Обработка ошибок базы данных
            return [
                'success' => false,
                'message' => 'Ошибка базы данных: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Авторизация пользователя
     * @param string $email - email пользователя
     * @param string $password - пароль
     * @return array - результат операции
     */
    public function loginUser($email, $password) {
        // Получаем данные пользователя по email
        $user = $this->getUserByEmail($email);

        // Проверяем существует ли пользователь и верный ли пароль
        if (!$user || !password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Неверный email или пароль'
            ];
        }

        return [
            'success' => true,
            'user' => $user
        ];
    }

    /**
     * Проверка существования пользователя по email
     * @param string $email - email для проверки
     * @return bool
     */
    private function userExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    /**
     * Получение пользователя по email
     * @param string $email - email пользователя
     * @return array|false - данные пользователя или false если не найден
     */
    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получение пользователя по ID (без пароля)
     * @param int $id - ID пользователя
     * @return array|false - данные пользователя или false если не найден
     */
    public function getUserById($id) {
        $stmt = $this->pdo->prepare(
            "SELECT id, email, fullName, phone, address, createdAt 
             FROM users WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Обновление данных пользователя
     * @param int $userId - ID пользователя
     * @param array $data - новые данные
     * @return bool - успех операции
     */
    public function updateUserProfile($userId, $data) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE users SET fullName = ?, phone = ?, address = ? WHERE id = ?"
            );
            $stmt->execute([
                $data['fullName'],
                $data['phone'],
                $data['address'],
                $userId
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}

