<?php
session_start();

require_once '../includes/init.php';
require_once '../includes/imageFunctions.php';
require_once '../includes/cartManager.php';

$categoryId = isset($_GET['categoryId']) ? intval($_GET['categoryId']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Получаем товары в зависимости от фильтров
if (!empty($search)) {
    $products = searchProducts($search);
} elseif ($categoryId) {
    $products = getProductsByCategory($categoryId);
} else {
    $products = getProducts();
}

$categories = getCategories();
$popularProducts = $products;

// Получаем ID товаров в корзине для подсветки
$cartProductIds = CartManager::getCartProductIds();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .cartHighlight {
            border: 3px solid #f9a602 !important;
            box-shadow: 0 0 15px rgba(249, 166, 2, 0.3);
            position: relative;
        }

        .inCartBadge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #f9a602;
            color: #000;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            z-index: 10;
        }

        .quantityControls {
            display: flex;
            align-items: center;
            gap: 5px;
            justify-content: center;
            margin-top: 10px;
        }

        .quantityBtn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            font-weight: bold;
            cursor: pointer;
        }

        .quantityDisplay {
            padding: 5px 10px;
            background: #f8f9fa;
            border-radius: 5px;
            min-width: 40px;
            text-align: center;
        }

        .productImageContainer {
            border-radius: 8px;
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .productImage {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .filterInfo {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .categoryHighlight {
            background: #ffdd59;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .searchHighlight {
            background: #a3d8ff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .noProducts {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
        }

        .productActions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-direction: column;
            align-items: center;
        }

        .stockInfo {
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="mainContent">
    <div class="container">
        <h1 class="pageTitle">
            🛠️ Каталог автозапчастей
        </h1>

        <div class="filterInfo">
            <form method="GET" action="products.php" class="filterForm">
                <div class="filterGroup">
                    <input type="text"
                           name="search"
                           placeholder="Поиск запчастей..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="formControl">
                </div>

                <div class="filterGroup">
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

        <?php if ($categoryId): ?>
            <?php
            $currentCategory = array_filter($categories, function($cat) use ($categoryId) {
                return $cat['id'] == $categoryId;
            });
            $currentCategory = reset($currentCategory);
            ?>
            <div class="categoryHighlight">
                <strong>Категория:</strong> <?php echo htmlspecialchars($currentCategory['name']); ?>
                <?php if (!empty($currentCategory['description'])): ?>
                    <br><small><?php echo htmlspecialchars($currentCategory['description']); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($search)): ?>
            <div class="searchHighlight">
                <strong>Результаты поиска:</strong> "<?php echo htmlspecialchars($search); ?>"
            </div>
        <?php endif; ?>

        <div class="productsGrid">
            <?php if (empty($products)): ?>
                <div class="noProducts">
                    <h3>Товары не найдены</h3>
                    <p>Попробуйте изменить параметры поиска</p>
                    <a href="products.php" class="btn btnPrimary">Показать все товары</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $isInCart = CartManager::isInCart($product['id']);
                    $cartQuantity = CartManager::getQuantityInCart($product['id']);
                    ?>

                    <div class="productCard <?php echo $isInCart ? 'cartHighlight' : ''; ?>">

                        <?php if ($isInCart): ?>
                            <div class="inCartBadge">
                                🛒 В корзине (<?php echo $cartQuantity; ?>)
                            </div>
                        <?php endif; ?>

                        <div class="productImageContainer">
                            <?php
                            echo getProductImageHtml(
                                    $product['image'],
                                    $product['name'],
                                    'productImage'
                            );
                            ?>
                        </div>

                        <h3 class="productTitle"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="productCategory"><?php echo htmlspecialchars($product['categoryName']); ?></p>
                        <div class="productPrice"><?php echo formatPrice($product['price']); ?></div>

                        <div class="productActions">
                            <a href="../productDetail.php?id=<?php echo $product['id']; ?>" class="btn btnPrimary fullWidth">
                                Подробнее
                            </a>

                            <?php if ($isInCart): ?>
                                <div class="quantityControls">
                                    <button type="button"
                                            class="quantityBtn btnDanger"
                                            onclick="updateCart(<?php echo $product['id']; ?>, -1)">
                                        −
                                    </button>
                                    <span class="quantityDisplay">
                                        <?php echo $cartQuantity; ?>
                                    </span>
                                    <button type="button"
                                            class="quantityBtn btnSuccess"
                                            onclick="updateCart(<?php echo $product['id']; ?>, 1)"
                                            <?php echo ($product['stock'] <= $cartQuantity) ? 'disabled' : ''; ?>>
                                        +
                                    </button>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="../includes/addToCartHandler.php" class="fullWidth">
                                    <input type="hidden" name="productId" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit"
                                            class="btn btnSuccess fullWidth"
                                            <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                        <?php echo ($product['stock'] > 0) ? '🛒 Добавить в корзину' : 'Нет в наличии'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="stockInfo">
                            <small class="textMuted">
                                В наличии: <?php echo $product['stock']; ?> шт.
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    function updateCart(productId, change) {
        fetch('includes/updateCart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'productId=' + productId + '&change=' + change
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении корзины');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addToCartForms = document.querySelectorAll('form[action="includes/addToCartHandler.php"]');

        addToCartForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('includes/addToCart.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Ошибка: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Произошла ошибка при добавлении в корзину');
                    });
            });
        });
    });
</script>
</body>
</html>


