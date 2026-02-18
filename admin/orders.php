<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Проверка авторизации
// $isAdmin = true;
//
// if (!$isAdmin) {
//    header('Location: ../index.php');
//    exit;
//    }

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateStatus'])) {
    $orderId = intval($_POST['orderId']);
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    header('Location: orders.php');
    exit;
}

// Получаем заказы
$orders = $pdo->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM orderItems WHERE orderId = o.id) as itemsCount
    FROM orders o 
    ORDER BY o.createdAt DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .status-badge {
            padding: 0.3rem 1rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="logo">
            <h1>🎵 Bob Marley Auto Parts - Заказы 🎵</h1>
            <p>Управление заказами</p>
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
            📦 Управление заказами
        </h1>

        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3 style="color: #666;">Заказов пока нет</h3>
                <p>Как только клиенты начнут оформлять заказы, они появятся здесь</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3 style="color: #1a4721; margin: 0;">
                                Заказ #<?php echo $order['id']; ?>
                            </h3>
                            <p style="color: #666; margin: 0.5rem 0 0 0;">
                                📅 <?php echo date('d.m.Y H:i', strtotime($order['createdAt'])); ?> |
                                📦 Товаров: <?php echo $order['itemsCount']; ?>
                            </p>
                        </div>
                        <div>
                            <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="hidden" name="orderId" value="<?php echo $order['id']; ?>">
                                <select name="status" class="formControl" style="width: auto;">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                </select>
                                <button type="submit" name="updateStatus" class="btn btnPrimary">
                                    💾
                                </button>
                            </form>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Информация о клиенте -->
                        <div>
                            <h4 style="color: #1a4721; margin-bottom: 0.5rem;">👤 Информация о клиенте</h4>
                            <p><strong>Имя:</strong> <?php echo htmlspecialchars($order['customerName']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            <?php if (!empty($order['phone'])): ?>
                                <p><strong>Телефон:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            <?php endif; ?>
                            <p><strong>Адрес:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        </div>

                        <!-- Детали заказа -->
                        <div>
                            <h4 style="color: #1a4721; margin-bottom: 0.5rem;">💰 Детали заказа</h4>
                            <p><strong>Сумма заказа:</strong> <?php echo formatPrice($order['totalAmount']); ?></p>
                            <p>
                                <strong>Статус:</strong>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php
                                        $statusLabels = [
                                            'pending' => '⏳ Ожидание',
                                            'processing' => '🔄 В обработке',
                                            'completed' => '✅ Завершен',
                                            'cancelled' => '❌ Отменен'
                                        ];
                                        echo $statusLabels[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                            </p>
                        </div>
                    </div>

                    <!-- Товары в заказе -->
                    <div style="margin-top: 1rem;">
                        <h4 style="color: #1a4721; margin-bottom: 0.5rem;">🛍️ Товары в заказе</h4>
                        <?php
                        $orderItems = $pdo->prepare("
                                SELECT oi.*, p.name as productName 
                                FROM orderItems oi 
                                LEFT JOIN products p ON oi.productId = p.id 
                                WHERE oi.orderId = ?
                            ");
                        $orderItems->execute([$order['id']]);
                        $items = $orderItems->fetchAll();
                        ?>

                        <?php foreach ($items as $item): ?>
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0;">
                                <span><?php echo htmlspecialchars($item['productName']); ?></span>
                                <span>
                                        <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?> =
                                        <strong><?php echo formatPrice($item['quantity'] * $item['price']); ?></strong>
                                    </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
