<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Обработка действий с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = $_POST['productId'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    switch ($action) {
        case 'add':
            addToCart($productId, $quantity);
            break;
        case 'update':
            updateCartItem($productId, $quantity);
            break;
        case 'remove':
            removeFromCart($productId);
            break;
        case 'clear':
            clearCart();
            break;
    }

    header('Location: cart.php');
    exit;
}

$cart = getCart();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="mainContent">
    <div class="container">
        <h1 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
            🛒 Ваша корзина
        </h1>

        <?php if (empty($cart['items'])): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3 style="color: #666;">Корзина пуста</h3>
                <p>Добавьте товары из каталога</p>
                <a href="products.php" class="btn btnPrimary">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <!-- Элементы корзины -->
            <div class="cartItems">
                <?php foreach ($cart['items'] as $item): ?>
                    <div class="cartItem">
                        <img src="<?php echo $item['image'] ?: 'images/no-image.jpg'; ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="cartItemImage">

                        <div style="flex: 1;">
                            <h3 class="productTitle"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="productCategory"><?php echo htmlspecialchars($item['categoryName']); ?></p>
                            <div class="productPrice"><?php echo formatPrice($item['price']); ?></div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <form method="POST" action="cart.php" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="productId" value="<?php echo $item['id']; ?>">
                                <input type="number"
                                       name="quantity"
                                       value="<?php echo $item['quantity']; ?>"
                                       min="1"
                                       style="width: 60px; padding: 0.5rem;"
                                       class="formControl">
                                <button type="submit" class="btn btnPrimary">🔄</button>
                            </form>

                            <form method="POST" action="cart.php">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="productId" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btnDanger">🗑️</button>
                            </form>
                        </div>

                        <div style="text-align: right;">
                            <strong style="font-size: 1.2rem;">
                                <?php echo formatPrice($item['subtotal']); ?>
                            </strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Итого и действия -->
            <div class="cartTotal">
                <h2 style="color: #1a4721;">Итого: <?php echo formatPrice($cart['total']); ?></h2>

                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btnDanger">Очистить корзину</button>
                    </form>

                    <a href="products.php" class="btn btnPrimary">Продолжить покупки</a>

                    <a href="checkout.php" class="btn btnSuccess">
                        📦 Перейти к оформлению
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>

