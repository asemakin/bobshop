<?php
/**
 * Вспомогательные функции для магазина
 * Bob Marley Auto Parts - Функции
 */

/**
 * Форматирование цены
 */
function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' ₽';
}

/**
 * Получение товаров из базы данных
 */
function getProducts($categoryId = null, $limit = null) {
    global $pdo;

    $sql = "SELECT p.*, c.name as categoryName 
            FROM products p 
            LEFT JOIN categories c ON p.categoryId = c.id 
            WHERE 1=1";

    $params = [];

    if ($categoryId) {
        $sql .= " AND p.categoryId = ?";
        $params[] = $categoryId;
    }

    $sql .= " ORDER BY p.createdAt DESC";

    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Получение одного товара по ID
 */
function getProduct($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT p.*, c.name as categoryName 
                          FROM products p 
                          LEFT JOIN categories c ON p.categoryId = c.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Получение категорий
 */
function getCategories() {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Добавление товара в корзину
 */
function addToCart($productId, $quantity = 1) {
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Обновление количества товара в корзине
 */
function updateCartItem($productId, $quantity) {
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Удаление товара из корзины
 */
function removeFromCart($productId) {
    unset($_SESSION['cart'][$productId]);
}

/**
 * Получение содержимого корзины
 */
function getCart() {
    $cart = [];
    $total = 0;

    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $product = getProduct($productId);
        if ($product) {
            $product['quantity'] = $quantity;
            $product['subtotal'] = $product['price'] * $quantity;
            $cart[] = $product;
            $total += $product['subtotal'];
        }
    }

    return [
        'items' => $cart,
        'total' => $total
    ];
}

/**
 * Очистка корзины
 */
function clearCart() {
    $_SESSION['cart'] = [];
}

/**
 * Создание нового заказа
 */
function createOrder($customerData, $cart) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Вставляем заказ
        $stmt = $pdo->prepare("INSERT INTO orders (customerName, email, phone, address, totalAmount) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $customerData['customerName'],
            $customerData['email'],
            $customerData['phone'],
            $customerData['address'],
            $cart['total']
        ]);

        $orderId = $pdo->lastInsertId();

        // Вставляем элементы заказа
        $stmt = $pdo->prepare("INSERT INTO orderItems (orderId, productId, quantity, price) 
                              VALUES (?, ?, ?, ?)");

        foreach ($cart['items'] as $item) {
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['quantity'],
                $item['price']
            ]);
        }

        $pdo->commit();
        return $orderId;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

