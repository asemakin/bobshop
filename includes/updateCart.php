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
$change = intval($_POST['change'] ?? 0);

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
    $newQuantity = $currentQuantity + $change;

    if ($newQuantity < 0) {
        $newQuantity = 0;
    }

    if ($newQuantity > $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'Недостаточно товара на складе']);
        exit;
    }

    if ($newQuantity === 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $newQuantity;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Корзина обновлена',
        'newQuantity' => $newQuantity,
        'cartTotal' => CartManager::getCartTotal()
    ]);

} catch (Exception $e) {
    error_log("Update cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
