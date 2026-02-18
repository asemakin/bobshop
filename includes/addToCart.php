<?php
session_start();
require_once 'cartManager.php';
require_once 'init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$productId = intval($_POST['productId'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $product = getProductById($productId);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Товар не найден']);
        exit;
    }

    $currentQuantity = CartManager::getQuantityInCart($productId);
    $newQuantity = $currentQuantity + $quantity;

    if ($newQuantity > $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'Недостаточно товара на складе']);
        exit;
    }

    CartManager::addToCart($productId, $quantity);

    echo json_encode([
        'success' => true,
        'message' => 'Товар добавлен в корзину',
        'cartTotal' => CartManager::getCartTotal()
    ]);

} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
