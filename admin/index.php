<?php
require_once '../includes/init.php';

require_once 'auth.php';
//require_once '../includes/config.php';
//require_once '../includes/functions.php';

// Простая проверка авторизации (в реальном проекте нужно сделать нормальную авторизацию)
//$isAdmin = true; // Для теста всегда true

//if (!$isAdmin) {
//    header('Location: ../index.php');
//    exit;
//    }

// Получаем статистику
$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalSales = $pdo->query("SELECT COALESCE(SUM(totalAmount), 0) FROM orders")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1a4721;
            margin-bottom: 0.5rem;
        }
        .admin-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        .menu-card {
            background: #1a4721;
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            background: #2d5a2d;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="logo">
            <h1>🎵 Bob Marley Auto Parts - Админка 🎵</h1>
            <p>Управление магазином</p>
        </div>
        <nav class="navbar">
            <ul class="navMenu">
                <li><a href="../main/index.php">🏠???</a></li>
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
            📊 Панель управления
        </h1>

        <!-- Статистика -->
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $productsCount; ?></div>
                <div>Товаров</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $categoriesCount; ?></div>
                <div>Категорий</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $ordersCount; ?></div>
                <div>Заказов</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo formatPrice($totalSales); ?></div>
                <div>Общие продажи</div>
            </div>
        </div>

        <!-- Меню админки -->
        <div class="admin-menu">
            <a href="products.php" class="menu-card">
                <h3>🛍️ Управление товарами</h3>
                <p>Добавление, редактирование и удаление товаров</p>
            </a>
            <a href="orders.php" class="menu-card">
                <h3>📦 Управление заказами</h3>
                <p>Просмотр и обработка заказов</p>
            </a>
            <a href="../main/products.php" class="menu-card">
                <h3>👀 Посмотреть магазин</h3>
                <p>Перейти на сайт как покупатель</p>
            </a>
        </div>

        <!-- Последние заказы -->
        <div style="background: white; padding: 2rem; border-radius: 10px; margin-top: 2rem;">
            <h2 style="color: #1a4721; margin-bottom: 1rem;">📋 Последние заказы</h2>
            <?php
            $recentOrders = $pdo->query("
                    SELECT * FROM orders 
                    ORDER BY createdAt DESC 
                    LIMIT 3
                ")->fetchAll();
            ?>

            <?php if (empty($recentOrders)): ?>
                <p style="color: #666; text-align: center;">Заказов пока нет</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 1rem; text-align: left;">ID</th>
                            <th style="padding: 1rem; text-align: left;">Клиент</th>
                            <th style="padding: 1rem; text-align: left;">Сумма</th>
                            <th style="padding: 1rem; text-align: left;">Дата</th>
                            <th style="padding: 1rem; text-align: left;">Статус</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 1rem;">#<?php echo $order['id']; ?></td>
                                <td style="padding: 1rem;"><?php echo htmlspecialchars($order['customerName']); ?></td>
                                <td style="padding: 1rem;"><?php echo formatPrice($order['totalAmount']); ?></td>
                                <td style="padding: 1rem;"><?php echo date('d.m.Y H:i', strtotime($order['createdAt'])); ?></td>
                                <td style="padding: 1rem;">
                                            <span style="background: #f9a602; color: #1a4721; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem;">
                                                <?php echo $order['status']; ?>
                                            </span>
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
