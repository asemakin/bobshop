<?php
session_start();

require_once 'includes/init.php';
require_once 'includes/imageFunctions.php'; // ← ДОБАВЬ ЭТУ СТРОКУ


//require_once 'includes/config.php';
//require_once 'includes/functions.php';

$categoryId = isset($_GET['categoryId']) ? intval($_GET['categoryId']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Получаем товары в зависимости от фильтров
if (!empty($search)) {
    $products = searchProducts($search);
} elseif ($categoryId) {
    $products = getProductsByCategory($categoryId);
} else {
    $products = getProducts(); // Все товары без лимита
}

$categories = getCategories();
$popularProducts = $products;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="mainContent">
    <div class="container">
        <h1 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
            🛠️ Каталог автозапчастей
        </h1>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 10px; margin-bottom: 2rem;">
            <form method="GET" action="products.php" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text"
                           name="search"
                           placeholder="Поиск запчастей..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="formControl">
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <select name="categoryId" class="formControl">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btnPrimary">🔍 Найти</button>
                <a href="products.php" class="btn btnDanger">❌ Сбросить</a>
            </form>
        </div>

        <!-- Информация о фильтрах -->
        <?php if ($categoryId): ?>
            <?php
            $currentCategory = array_filter($categories, function($cat) use ($categoryId) {
                return $cat['id'] == $categoryId;
            });
            $currentCategory = reset($currentCategory);
            ?>
            <div style="background: #ffdd59; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                <strong>Категория:</strong> <?php echo htmlspecialchars($currentCategory['name']); ?>
                <?php if (!empty($currentCategory['description'])): ?>
                    <br><small><?php echo htmlspecialchars($currentCategory['description']); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($search)): ?>
            <div style="background: #a3d8ff; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                <strong>Результаты поиска:</strong> "<?php echo htmlspecialchars($search); ?>"
            </div>
        <?php endif; ?>

        <div class="productsGrid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <h3 style="color: #666;">Товары не найдены</h3>
                    <p>Попробуйте изменить параметры поиска</p>
                    <a href="products.php" class="btn btnPrimary">Показать все товары</a>
                </div>
            <?php else: ?>

                <?php foreach ($products as $product): ?>
                    <div class="productCard">

                        <!-- КАРТИНКА ТОВАРА (РЕАЛЬНЫЕ ФОТО) -->
                        <div class="productImage" style="border-radius: 8px; height: 200px; border: 3px solid #f9a602; overflow: hidden;">
                            <?php
                            echo getProductImageHtml(
                                    $product['image'],
                                    $product['name'],
                                    'product-image'
                            );
                            ?>
                            <style>
                                .product-image {
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                    object-position: center;
                                }
                            </style>
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

            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
