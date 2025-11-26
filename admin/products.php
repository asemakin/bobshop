<?php
/**
 * ПРОДВИНУТАЯ АДМИН-ПАНЕЛЬ ТОВАРОВ - КОМПАКТНАЯ ВЕРСИЯ
 */
require_once '../includes/init.php';
require_once 'auth.php';
require_once '../includes/imageFunctions.php';

// ПАРАМЕТРЫ ФИЛЬТРАЦИИ И ПОИСКА
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';
$stock = $_GET['stock'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// ПОСТРОЕНИЕ ЗАПРОСА
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== 'all') {
    $where[] = "p.categoryId = ?";
    $params[] = $category;
}

if ($stock === 'in') {
    $where[] = "p.stock > 0";
} elseif ($stock === 'out') {
    $where[] = "p.stock = 0";
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// СОРТИРОВКА
$orderBy = match($sort) {
    'name' => "p.name ASC",
    'price_asc' => "p.price ASC",
    'price_desc' => "p.price DESC",
    'stock' => "p.stock DESC",
    default => "p.id DESC"
};

// ПОЛУЧЕНИЕ ДАННЫХ
$sql = "SELECT p.*, c.name as categoryName FROM products p 
        LEFT JOIN categories c ON p.categoryId = c.id 
        $whereClause ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = getCategories();

// ОБРАБОТКА ДЕЙСТВИЙ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // МАССОВОЕ УДАЛЕНИЕ
    if ($action === 'bulk_delete' && isset($_POST['selected_products'])) {
        $deleted = 0;
        foreach ($_POST['selected_products'] as $productId) {
            $productId = intval($productId);
            if ($productId > 0) {
                try {
                    $getStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                    $getStmt->execute([$productId]);
                    $product = $getStmt->fetch();

                    if ($product) {
                        deleteProductImages($product['image']);
                        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                        $delStmt->execute([$productId]);
                        $deleted++;
                    }
                } catch (Exception $e) {
                    error_log("Ошибка удаления товара $productId: " . $e->getMessage());
                }
            }
        }
        $_SESSION['successMessage'] = "Удалено товаров: $deleted";
        header('Location: products.php');
        exit;
    }

    // ДОБАВЛЕНИЕ ТОВАРА
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $categoryId = intval($_POST['categoryId'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!empty($name) && $price > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, categoryId, stock) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $categoryId, $stock]);

                $newId = $pdo->lastInsertId();
                $imagePath = 'uploads/products/default.png';

                if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
                    $errors = validateImageUpload($_FILES['productImage']);
                    if (empty($errors)) {
                        $uploaded = uploadProductImage($_FILES['productImage'], $newId);
                        $imagePath = $uploaded['mainImage'];
                        $updateStmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                        $updateStmt->execute([$imagePath, $newId]);
                    }
                }

                $_SESSION['successMessage'] = "Товар добавлен!";
            } catch (Exception $e) {
                $_SESSION['errorMessage'] = "Ошибка: " . $e->getMessage();
            }
        } else {
            $_SESSION['errorMessage'] = "Заполните название и цену";
        }
        header('Location: products.php');
        exit;
    }

    // УДАЛЕНИЕ ОДНОГО ТОВАРА
    if ($action === 'delete') {
        $productId = intval($_POST['productId'] ?? 0);
        if ($productId > 0) {
            try {
                $getStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                $getStmt->execute([$productId]);
                $product = $getStmt->fetch();

                if ($product) {
                    deleteProductImages($product['image']);
                    $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $delStmt->execute([$productId]);
                    $_SESSION['successMessage'] = "Товар удален!";
                }
            } catch (Exception $e) {
                $_SESSION['errorMessage'] = "Ошибка удаления: " . $e->getMessage();
            }
        }
        header('Location: products.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .compact-table { font-size: 0.85rem; }
        .compact-table th, .compact-table td { padding: 0.5rem; }
        .compact-table th { background: #1a4721; color: white; }
        .product-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 3px; }
        .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .filters { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .filters-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 0.5rem; align-items: end; }
        .bulk-actions { background: #e9ecef; padding: 0.5rem; border-radius: 5px; margin-bottom: 0.5rem; }
        .quick-form { background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #f9a602; }
        .quick-form-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 0.5rem; align-items: end; }
        .stats-bar { display: flex; gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .stat-item { background: white; padding: 0.5rem 1rem; border-radius: 5px; border-left: 3px solid #1a4721; }
        @media (max-width: 768px) {
            .filters-grid, .quick-form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<!-- ШАПКА КАК В admin/index.php -->
<header class="header">
    <div class="container">
        <div class="logo">
            <h1>🎵 Bob Marley Auto Parts - Товары 🎵</h1>
            <p>Управление товарами</p>
        </div>
        <nav class="navbar">
            <ul class="navMenu">
                <li><a href="../index.php">🏠???</a></li>
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

        <!-- СООБЩЕНИЯ -->
        <?php if (isset($_SESSION['successMessage'])): ?>
            <div class="alert alertSuccess"><?= $_SESSION['successMessage'] ?></div>
            <?php unset($_SESSION['successMessage']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['errorMessage'])): ?>
            <div class="alert alertError"><?= $_SESSION['errorMessage'] ?></div>
            <?php unset($_SESSION['errorMessage']); ?>
        <?php endif; ?>

        <!-- БЫСТРАЯ ФОРМА ДОБАВЛЕНИЯ -->
        <div class="quick-form">
            <h3 style="margin: 0 0 0.5rem 0; color: #1a4721;">➕ Быстрое добавление</h3>
            <form method="POST" enctype="multipart/form-data" class="quick-form-grid">
                <input type="hidden" name="action" value="add">
                <div>
                    <input type="text" name="name" class="formControl" placeholder="Название товара" required style="font-size: 0.9rem;">
                </div>
                <div>
                    <input type="number" name="price" step="0.01" min="0" class="formControl" placeholder="Цена" required style="font-size: 0.9rem;">
                </div>
                <div>
                    <select name="categoryId" class="formControl" style="font-size: 0.9rem;">
                        <option value="0">Категория</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <input type="number" name="stock" value="0" min="0" class="formControl" placeholder="Кол-во" style="font-size: 0.9rem;">
                </div>
                <div>
                    <button type="submit" class="btn btnSuccess" style="padding: 0.5rem 1rem;">✅ Добавить</button>
                </div>
            </form>
        </div>

        <!-- ФИЛЬТРЫ И ПОИСК -->
        <div class="filters">
            <form method="GET" class="filters-grid">
                <div>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           class="formControl" placeholder="🔍 Поиск по названию..." style="font-size: 0.9rem;">
                </div>
                <div>
                    <select name="category" class="formControl" style="font-size: 0.9rem;">
                        <option value="all">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select name="stock" class="formControl" style="font-size: 0.9rem;">
                        <option value="all">Любой запас</option>
                        <option value="in" <?= $stock == 'in' ? 'selected' : '' ?>>В наличии</option>
                        <option value="out" <?= $stock == 'out' ? 'selected' : '' ?>>Нет в наличии</option>
                    </select>
                </div>
                <div>
                    <select name="sort" class="formControl" style="font-size: 0.9rem;">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Сначала новые</option>
                        <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>По названию</option>
                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Цена ↑</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Цена ↓</option>
                        <option value="stock" <?= $sort == 'stock' ? 'selected' : '' ?>>По запасу</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btnPrimary">🔍 Применить</button>
                    <a href="products.php" class="btn btnSecondary">🔄 Сбросить</a>
                </div>
            </form>
        </div>

        <!-- ТАБЛИЦА ТОВАРОВ -->
        <div style="background: white; border-radius: 8px; overflow: hidden;">
            <?php if (!empty($products)): ?>
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="action" value="bulk_delete">

                    <!-- МАССОВЫЕ ДЕЙСТВИЯ -->
                    <div class="bulk-actions">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                            <label for="selectAll" style="margin: 0; font-size: 0.9rem;">Выбрать все</label>
                            <button type="submit" class="btn btnDanger btn-xs"
                                    onclick="return confirm('Удалить выбранные товары?')">🗑️ Удалить выбранные</button>
                            <span style="font-size: 0.9rem; color: #666;">
                                Найдено: <?= count($products) ?> товаров
                            </span>
                        </div>
                    </div>

                    <!-- ТАБЛИЦА -->
                    <div style="overflow-x: auto;">
                        <table class="admin-table compact-table" style="width: 100%;">
                            <thead>
                            <tr>
                                <th style="width: 30px;"></th>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 60px;">Фото</th>
                                <th>Название</th>
                                <th style="width: 120px;">Категория</th>
                                <th style="width: 100px;">Цена</th>
                                <th style="width: 100px;">Запас</th>
                                <th style="width: 120px;">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_products[]" value="<?= $product['id'] ?>" class="product-checkbox">
                                    </td>
                                    <td><small style="color: #666;">#<?= $product['id'] ?></small></td>
                                    <td>
                                        <?= getProductImageHtml($product['image'] ?? '', $product['name'], 'product-thumb') ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($product['name']) ?></div>
                                        <?php if (!empty($product['description'])): ?>
                                            <small style="color: #666; display: block; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($product['description']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                            <span style="background: #e9ecef; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">
                                                <?= htmlspecialchars($product['categoryName'] ?? 'Без категории') ?>
                                            </span>
                                    </td>
                                    <td style="font-weight: 600; color: #1a4721;">
                                        <?= formatPrice($product['price']) ?>
                                    </td>
                                    <td>
                                            <span style="color: <?= $product['stock'] > 0 ? '#27ae60' : '#e74c3c'; ?>; font-weight: 500;">
                                                <?= $product['stock'] ?> шт.
                                            </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <a href="editProduct.php?id=<?= $product['id'] ?>" class="btn btnPrimary btn-xs" title="Редактировать">
                                                ✏️
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="productId" value="<?= $product['id'] ?>">
                                                <button type="submit" class="btn btnDanger btn-xs"
                                                        onclick="return confirm('Удалить \"<?= htmlspecialchars($product['name']) ?>\"?')" title="Удалить">
                                                🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">😔</div>
                    <p>Товары не найдены</p>
                    <?php if ($search || $category !== 'all' || $stock !== 'all'): ?>
                        <a href="products.php" class="btn btnPrimary">Показать все товары</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }

    // Авто-отправка формы при изменении фильтров
    document.querySelectorAll('.filters select').forEach(select => {
        select.addEventListener('change', () => {
            document.querySelector('.filters form').submit();
        });
    });
</script>
</body>
</html>