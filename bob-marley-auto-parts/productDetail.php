<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// ПОЛУЧАЕМ ID ТОВАРА ИЗ АДРЕСНОЙ СТРОКИ
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = getProduct($productId);

// ЕСЛИ ТОВАР НЕ НАЙДЕН - ПЕРЕХОДИМ В КАТАЛОГ
if (!$product) {
    header('Location: products.php');
    exit;
}

// ОБРАБАТЫВАЕМ ДОБАВЛЕНИЕ В КОРЗИНУ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addToCart'])) {
    $quantity = intval($_POST['quantity']);

    // ДОБАВЛЯЕМ ТОВАР В КОРЗИНУ
    addToCart($productId, $quantity);

    // ПЕРЕНАПРАВЛЯЕМ В КОРЗИНУ
    header('Location: cart.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<!-- ПОДКЛЮЧАЕМ ШАПКУ САЙТА -->
<?php include 'includes/header.php'; ?>

<main class="mainContent">
    <div class="container">

        <!-- ОСНОВНАЯ ИНФОРМАЦИЯ О ТОВАРЕ -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: start;">
            <!-- КАРТИНКА ТОВАРА -->
            <div>
                <div style="width: 100%; height: 400px; background: <?php echo getProductColor($product['categoryId']); ?>;
                        color: white; display: flex; align-items: center; justify-content: center;
                        border-radius: 10px; border: 5px solid #f9a602; font-size: 6rem;">
                    <?php echo getProductImage($product); ?>
                </div>
            </div>

            <!-- Информация о товаре -->
            <div>
                <h1 style="color: #1a4721; margin-bottom: 1rem;"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p style="color: #666; margin-bottom: 1rem;">Категория: <?php echo htmlspecialchars($product['categoryName']); ?></p>

                <div class="productPrice" style="font-size: 2rem; margin: 2rem 0;">
                    <?php echo formatPrice($product['price']); ?>
                </div>

            </div>
        </div>
                <!-- ОПИСАНИЕ ТОВАРА -->
                <div style="background: #f8f9fa; padding: 2rem; border-radius: 10px; margin-bottom: 2rem;">
                    <h3 style="color: #1a4721; margin-bottom: 1rem;">📖 Описание товара</h3>
                    <p style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <!-- ФОРМА ДОБАВЛЕНИЯ В КОРЗИНУ -->
                <form method="POST" action="">
                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 2rem;">
                        <label for="quantity" class="formLabel">Количество:</label>
                        <input type="number"
                               id="quantity"
                               name="quantity"
                               value="1"
                               min="1"
                               max="<?php echo $product['stock']; ?>"
                               style="width: 80px;"
                               class="formControl">
                        <span style="color: #666;">Доступно: <?php echo $product['stock']; ?> шт.</span>
                    </div>

                    <!-- КНОПКА ДОБАВЛЕНИЯ В КОРЗИНУ -->
                    <button type="submit" name="addToCart" class="btn btnSuccess" style="font-size: 1.2rem; padding: 1rem 2rem;">
                        🛒 Добавить в корзину
                    </button>
                </form>

                <!-- ССЫЛКА НАЗАД В КАТАЛОГ -->
                <div style="margin-top: 2rem;">
                    <a href="products.php" class="btn btnPrimary">← Назад к каталогу</a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ПОДКЛЮЧАЕМ ПОДВАЛ САЙТА -->
<?php include 'includes/footer.php'; ?>
</body>
</html>
