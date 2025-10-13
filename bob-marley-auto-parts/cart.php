<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/sessionManager.php';
require_once 'includes/cartIntegration.php';

// Обработка действий с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = $_POST['productId'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    switch ($action) {
        case 'add':
            addToCart($productId, $quantity);
            // Автоматически сохраняем корзину для авторизованных пользователей
            if (SessionManager::isUserLoggedIn()) {
                saveUserCart(SessionManager::getCurrentUserId(), $_SESSION['cart']);
            }
            break;
        case 'update':
            // Обновляем количество товара в корзине
            if ($quantity <= 0) {
                removeFromCart($productId);
            } else {
                updateCartItem($productId, $quantity);
            }
            // Автоматически сохраняем корзину для авторизованных пользователей
            if (SessionManager::isUserLoggedIn()) {
                saveUserCart(SessionManager::getCurrentUserId(), $_SESSION['cart']);
            }
            break;
        case 'remove':
            // Удаляем товар из корзины
            removeFromCart($productId);
            // Автоматически сохраняем корзину для авторизованных пользователей
            if (SessionManager::isUserLoggedIn()) {
                saveUserCart(SessionManager::getCurrentUserId(), $_SESSION['cart']);
            }
            break;
        case 'clear':
            // Полностью очищаем корзину
            clearCart();
            // Очищаем корзину в базе для авторизованных пользователей
            if (SessionManager::isUserLoggedIn()) {
                clearUserCart(SessionManager::getCurrentUserId());
            }
            break;
    }

    // Перенаправляем обратно в корзину чтобы избежать повторной отправки формы
    header('Location: cart.php');
    exit;
}

// Получаем актуальное состояние корзины
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
<!-- Подключаем шапку сайта -->
<?php include 'includes/header.php'; ?>

<main class="mainContent">
    <div class="container">
        <h1 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
            🛒 Ваша корзина
        </h1>

        <!-- Уведомление о сохранении корзины для авторизованных пользователей -->
        <?php if (SessionManager::isUserLoggedIn()): ?>
            <div class="alert alertSuccess" style="margin-bottom: 2rem; text-align: center;">
                ✅ Ваша корзина автоматически сохраняется
                <br><small>Вы можете выйти и вернуться позже - товары останутся в корзине</small>
            </div>
        <?php endif; ?>

        <!-- Если корзина пуста - показываем сообщение -->
        <?php if (empty($cart['items'])): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3 style="color: #666;">Корзина пуста</h3>
                <p>Добавьте товары из каталога</p>

                <?php if (!SessionManager::isUserLoggedIn()): ?>
                    <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; margin: 2rem auto; max-width: 400px;">
                        <p style="margin-bottom: 1rem;">🌟 <strong>Зарегистрируйтесь чтобы:</strong></p>
                        <ul style="text-align: left; margin-bottom: 1rem;">
                            <li>Сохранять корзину между сессиями</li>
                            <li>Отслеживать историю заказов</li>
                            <li>Получать персональные скидки</li>
                        </ul>
                        <a href="user/register.php" class="btn btnPrimary" style="margin: 0.5rem;">
                            📝 Бесплатная регистрация
                        </a>
                        <a href="user/login.php" class="btn" style="background: transparent; border: 2px solid #1a4721; color: #1a4721; margin: 0.5rem;">
                            🔑 Войти в аккаунт
                        </a>
                    </div>
                <?php endif; ?>

                <a href="products.php" class="btn btnPrimary">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <!-- Если в корзине есть товары - выводим их -->
            <div style="margin-bottom: 2rem;">
                <?php foreach ($cart['items'] as $item): ?>
                    <div class="cartItem">
                        <!-- Картинка товара в корзине -->
                        <div class="cartItemImage" style="background: <?php echo getProductColor($item['categoryId']); ?>;
                                color: white; display: flex; align-items: center; justify-content: center;
                                font-size: 2rem; border-radius: 5px; min-width: 80px; min-height: 80px; border: 2px solid #f9a602;">
                            <?php echo getProductImage($item); ?>
                        </div>

                        <!-- Информация о товаре -->
                        <div style="flex: 1;">
                            <h3 class="productTitle"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="productCategory"><?php echo htmlspecialchars($item['categoryName']); ?></p>
                            <div class="productPrice">Цена: <?php echo formatPrice($item['price']); ?></div>

                            <!-- Уведомление о сохранении для авторизованных пользователей -->
                            <?php if (SessionManager::isUserLoggedIn()): ?>
                                <small style="color: #27ae60;">✅ Сохранено в вашем аккаунте</small>
                            <?php endif; ?>
                        </div>

                        <!-- Управление количеством -->
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <!-- Форма для изменения количества -->
                            <form method="POST" action="cart.php" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="productId" value="<?php echo $item['id']; ?>">
                                <input type="number"
                                       name="quantity"
                                       value="<?php echo $item['quantity']; ?>"
                                       min="1"
                                       style="width: 60px; padding: 0.5rem;"
                                       class="formControl">
                                <button type="submit" class="btn btnPrimary" title="Обновить количество">🔄</button>
                            </form>

                            <!-- Форма для удаления товара -->
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="productId" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btnDanger" title="Удалить товар">🗑️</button>
                            </form>
                        </div>

                        <!-- Общая стоимость за этот товар -->
                        <div style="text-align: right;">
                            <strong style="font-size: 1.2rem;">
                                <?php echo formatPrice($item['subtotal']); ?>
                            </strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Блок с итого и кнопками действий -->
            <div class="cartTotal">
                <h2 style="color: #1a4721;">Итого: <?php echo formatPrice($cart['total']); ?></h2>

                <!-- Информация о бесплатной доставке -->
                <?php if ($cart['total'] < 5000): ?>
                    <div style="background: #fff3cd; padding: 1rem; border-radius: 5px; margin: 1rem 0; text-align: center;">
                        <p>🎵 Добавьте товаров ещё на <?php echo formatPrice(5000 - $cart['total']); ?> для <strong>бесплатной доставки!</strong></p>
                    </div>
                <?php else: ?>
                    <div style="background: #e8f5e8; padding: 1rem; border-radius: 5px; margin: 1rem 0; text-align: center;">
                        <p>🎉 <strong>Поздравляем!</strong> Ваша доставка бесплатна!</p>
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
                    <!-- Кнопка очистки корзины -->
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btnDanger" onclick="return confirm('Вы уверены что хотите очистить корзину?')">
                            Очистить корзину
                        </button>
                    </form>

                    <!-- Ссылка для продолжения покупок -->
                    <a href="products.php" class="btn btnPrimary">Продолжить покупки</a>

                    <!-- Ссылка для перехода к оформлению -->
                    <a href="checkout.php" class="btn btnSuccess" style="font-size: 1.1rem; padding: 1rem 2rem;">
                        📦 Перейти к оформлению
                    </a>
                </div>

                <!-- Призыв к регистрации для неавторизованных пользователей -->
                <?php if (!SessionManager::isUserLoggedIn()): ?>
                    <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; margin-top: 2rem; text-align: center;">
                        <p style="margin-bottom: 1rem;"><strong>Не теряйте свою корзину!</strong></p>
                        <p>Зарегистрируйтесь чтобы сохранить товары и получить доступ к истории заказов</p>
                        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem; flex-wrap: wrap;">
                            <a href="user/register.php" class="btn btnPrimary">📝 Быстрая регистрация</a>
                            <a href="user/login.php" class="btn" style="background: transparent; border: 2px solid #1a4721; color: #1a4721;">
                                🔑 Уже есть аккаунт
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Подключаем подвал сайта -->
<?php include 'includes/footer.php'; ?>
</body>
</html>