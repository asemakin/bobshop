
<?php

// ЗАПУСКАЕМ СЕССИЮ В НАЧАЛЕ ФАЙЛА
session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Используем новую функцию для популярных товаров
$popularProducts = getPopularProducts(3);
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bob Marley Auto Parts - Качественные автозапчасти</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<section class="hero">
    <div class="container">
        <div class="heroContent">
            <h2>Don't worry, be happy - мы починим вашу машину!</h2>
            <p>Качественные автозапчасти с душой и позитивом</p>
            <a href="products.php" class="btn btnPrimary">Смотреть каталог</a>
        </div>
    </div>
</section>

<main class="mainContent">
    <div class="container">
        <section class="popularProducts">
            <h2 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
                🔥 Популярные запчасти
            </h2>

            <?php if (empty($popularProducts)): ?>
                <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="color: #666;">Товаров пока нет</h3>
                    <p>Добавьте товары через админ-панель или проверьте подключение к БД</p>
                    <a href="admin/" class="btn btnPrimary">Перейти в админку</a>
                </div>
            <?php else: ?>
                <div class="productsGrid">

                    <?php foreach ($popularProducts as $product): ?>
                        <div class="productCard">
                            <!-- КАРТИНКА ТОВАРА (АВТОМАТИЧЕСКАЯ EMOJI) -->
                            <div class="productImage" style="background: <?php echo getProductColor($product['categoryId']); ?>;
                                    color: white; display: flex; align-items: center; justify-content: center;
                                    font-size: 3rem; border-radius: 8px; height: 200px; border: 3px solid #f9a602;">
                                <?php echo getProductImage($product); ?>
                            </div>

                            <h3 class="productTitle"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="productCategory"><?php echo htmlspecialchars($product['categoryName']); ?></p>
                            <div class="productPrice"><?php echo formatPrice($product['price']); ?></div>

                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <a href="productDetail.php?id=<?php echo $product['id']; ?>" class="btn btnPrimary">
                                    Подробнее
                                </a>
                                <form method="POST" action="cart.php" style="display: inline;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="productId" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btnSuccess">🛒 В корзину</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>
        </section>

        <section class="categories" style="margin-top: 3rem;">
            <h2 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
                🚗 Категории запчастей
            </h2>

            <?php if (empty($categories)): ?>
                <div style="text-align: center; padding: 1rem;">
                    <p style="color: #666;">Категории пока не добавлены</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center;">
                    <?php foreach ($categories as $category): ?>
                        <a href="products.php?categoryId=<?php echo $category['id']; ?>"
                           class="btn btnSuccess">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>