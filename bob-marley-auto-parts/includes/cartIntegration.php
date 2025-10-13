<?php
/**
 * Интеграция корзины с системой пользователей
 * Bob Marley Auto Parts
 */

/**
 * Сохранение корзины пользователя в базу данных
 */
function saveUserCart($userId, $cartItems) {
    global $pdo;

    try {
        // Преобразуем корзину в JSON для хранения
        $cartData = json_encode($cartItems);

        // Проверяем существует ли уже запись корзины
        $stmt = $pdo->prepare("SELECT id FROM userCarts WHERE userId = ?");
        $stmt->execute([$userId]);
        $existingCart = $stmt->fetch();

        if ($existingCart) {
            // Обновляем существующую корзину
            $stmt = $pdo->prepare("UPDATE userCarts SET cartData = ?, updatedAt = NOW() WHERE userId = ?");
            $stmt->execute([$cartData, $userId]);
        } else {
            // Создаем новую запись корзины
            $stmt = $pdo->prepare("INSERT INTO userCarts (userId, cartData, createdAt, updatedAt) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$userId, $cartData]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Ошибка сохранения корзины: " . $e->getMessage());
        return false;
    }
}

/**
 * Загрузка корзины пользователя из базы данных
 */
function loadUserCart($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT cartData FROM userCarts WHERE userId = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();

        if ($cart && $cart['cartData']) {
            return json_decode($cart['cartData'], true);
        }

        return [];
    } catch (PDOException $e) {
        error_log("Ошибка загрузки корзины: " . $e->getMessage());
        return [];
    }
}

/**
 * Автоматическая синхронизация корзины при входе пользователя
 */
function syncCartOnLogin($userId) {
    // Загружаем сохраненную корзину из базы
    $savedCart = loadUserCart($userId);

    // Если есть сохраненная корзина, объединяем с текущей
    if (!empty($savedCart) && isset($_SESSION['cart'])) {
        foreach ($savedCart as $productId => $quantity) {
            if (isset($_SESSION['cart'][$productId])) {
                // Выбираем большее количество
                $_SESSION['cart'][$productId] = max($_SESSION['cart'][$productId], $quantity);
            } else {
                // Добавляем новый товар
                $_SESSION['cart'][$productId] = $quantity;
            }
        }
    }

    // Сохраняем объединенную корзину
    saveUserCart($userId, $_SESSION['cart'] ?? []);
}

/**
 * Автоматическое сохранение корзины при выходе пользователя
 */
function saveCartOnLogout($userId) {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        saveUserCart($userId, $_SESSION['cart']);
    }
}


/**
 * Очистка корзины пользователя
 */
function clearUserCart($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM userCarts WHERE userId = ?");
        $stmt->execute([$userId]);
        return true;
    } catch (PDOException $e) {
        error_log("Ошибка очистки корзины: " . $e->getMessage());
        return false;
    }
}

