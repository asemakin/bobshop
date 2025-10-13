<?php
require_once 'auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Проверка авторизации
// $isAdmin = true;
// if (!$isAdmin) {
//    header('Location: ../index.php');
//    exit;
//    }

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // Добавление товара
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $categoryId = intval($_POST['categoryId'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        if (!empty($name) && $price > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO products (name, description, price, categoryId, stock) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $price, $categoryId, $stock]);
        }
    }
    elseif ($action === 'delete') {
        // Удаление товара
        $productId = intval($_POST['productId'] ?? 0);
        if ($productId > 0) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
        }
    }

    header('Location: products.php');
    exit;
}

// Получаем данные
$products = getProducts();
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .admin-table th {
            background: #1a4721;
            color: white;
        }
        .admin-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="logo">
            <h1>🎵 Bob Marley Auto Parts - Товары 🎵</h1>
            <p>Управление товарами</p>
        </div>
        <nav class="navbar">
            <ul class="navMenu">
                <li><a href="../index.php">🏠 На сайт</a></li>
                <li><a href="index.php">📊 Статистика</a></li>
                <li><a href="products.php">🛍️ Товары</a></li>
                <li><a href="categories.php">📂 Категории</a></li>
                <li><a href="orders.php">📦 Заказы</a></li>
                <li><a href="logout.php" style="color: #e74c3c;">🚪 Выйти (<?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>)</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="mainContent">
    <div class="container">
        <h1 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
            🛍️ Управление товарами
        </h1>

        <!-- Форма добавления товара -->
        <div class="admin-form">
            <h2 style="color: #1a4721; margin-bottom: 1rem;">➕ Добавить новый товар</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-row">
                    <div>
                        <label class="formLabel">Название товара *</label>
                        <input type="text" name="name" class="formControl" required>
                    </div>
                    <div>
                        <label class="formLabel">Цена *</label>
                        <input type="number" name="price" step="0.01" class="formControl" required>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label class="formLabel">Категория</label>
                        <select name="categoryId" class="formControl">
                            <option value="0">Без категории</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="formLabel">Количество на складе</label>
                        <input type="number" name="stock" value="0" class="formControl">
                    </div>
                </div>

                <div>
                    <label class="formLabel">Описание</label>
                    <textarea name="description" class="formControl" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btnSuccess" style="margin-top: 1rem;">
                    ✅ Добавить товар
                </button>
            </form>
        </div>

        <!-- Список товаров -->
        <div style="background: white; padding: 2rem; border-radius: 10px;">
            <h2 style="color: #1a4721; margin-bottom: 1rem;">📋 Список товаров</h2>

            <?php if (empty($products)): ?>
                <p style="color: #666; text-align: center;">Товаров нет</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>На складе</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <?php if (!empty($product['description'])): ?>
                                        <br><small style="color: #666;"><?php echo mb_substr($product['description'], 0, 50) . '...'; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['categoryName'] ?? 'Без категории'); ?></td>
                                <td><?php echo formatPrice($product['price']); ?></td>
                                <td><?php echo $product['stock']; ?> шт.</td>

                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <!-- КНОПКА РЕДАКТИРОВАНИЯ -->
                                        <a href="editProduct.php?id=<?php echo $product['id']; ?>" class="btn btnPrimary">
                                            ✏️ Редактировать
                                        </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="productId" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btnDanger" onclick="return confirm('Удалить товар?')">
                                            🗑️ Удалить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
