<?php
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Получаем ID товара для редактирования
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Если нет ID - возвращаемся к списку товаров
if ($productId === 0) {
    header('Location: products.php');
    exit;
}

// Получаем данные товара
$product = getProduct($productId);
if (!$product) {
    header('Location: products.php');
    exit;
}

$categories = getCategories();
$success = '';
$error = '';

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $categoryId = intval($_POST['categoryId'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    if (!empty($name) && $price > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, categoryId = ?, stock = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $price, $categoryId, $stock, $productId]);
            $success = "✅ Товар успешно обновлен!";

            // Обновляем данные товара
            $product = getProduct($productId);

        } catch (PDOException $e) {
            $error = "❌ Ошибка при обновлении товара: " . $e->getMessage();
        }
    } else {
        $error = "❌ Заполните все обязательные поля";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование товара - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="mainContent">
    <div class="container">
        <h1 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
            ✏️ Редактирование товара
        </h1>

        <?php if ($success): ?>
            <div style="background: #27ae60; color: white; padding: 1rem; border-radius: 5px; margin-bottom: 2rem; text-align: center;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #e74c3c; color: white; padding: 1rem; border-radius: 5px; margin-bottom: 2rem; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
            <!-- Форма редактирования -->
            <div>
                <h2 style="color: #1a4721; margin-bottom: 1rem;">📝 Данные товара</h2>

                <form method="POST">
                    <div style="display: grid; gap: 1.5rem;">
                        <div>
                            <label class="formLabel">Название товара *</label>
                            <input type="text" name="name" class="formControl"
                                   value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>

                        <div>
                            <label class="formLabel">Описание</label>
                            <textarea name="description" class="formControl" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="formLabel">Цена *</label>
                                <input type="number" name="price" step="0.01" class="formControl"
                                       value="<?php echo $product['price']; ?>" required>
                            </div>
                            <div>
                                <label class="formLabel">Количество на складе</label>
                                <input type="number" name="stock" class="formControl"
                                       value="<?php echo $product['stock']; ?>">
                            </div>
                        </div>

                        <div>
                            <label class="formLabel">Категория</label>
                            <select name="categoryId" class="formControl">
                                <option value="0">Без категории</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo $product['categoryId'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btnSuccess">💾 Сохранить изменения</button>
                            <a href="products.php" class="btn btnPrimary">← Назад к списку</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Предпросмотр товара -->
            <div>
                <h2 style="color: #1a4721; margin-bottom: 1rem;">👀 Предпросмотр</h2>

                <div class="productCard">
                    <div class="productImage" style="background: <?php echo getProductColor($product['categoryId']); ?>;
                        color: white; display: flex; align-items: center; justify-content: center;
                        font-size: 3rem; border-radius: 8px; height: 200px; border: 3px solid #f9a602;">
                        <?php echo getProductImage($product); ?>
                    </div>

                    <h3 class="productTitle"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="productCategory"><?php echo htmlspecialchars($product['categoryName']); ?></p>
                    <div class="productPrice"><?php echo formatPrice($product['price']); ?></div>
                    <p style="color: #888; font-size: 0.9rem;">В наличии: <?php echo $product['stock']; ?> шт.</p>
                </div>

                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-top: 1rem;">
                    <p><strong>ID товара:</strong> <?php echo $product['id']; ?></p>
                    <p><strong>Создан:</strong> <?php echo date('d.m.Y H:i', strtotime($product['createdAt'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>

