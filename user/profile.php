<?php
/**
 * Личный кабинет пользователя
 * Bob Marley Auto Parts
 */

// Подключаем необходимые файлы
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/userAuth.php';
require_once '../includes/sessionManager.php';

// Проверяем авторизацию пользователя
if (!SessionManager::isUserLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Создаем объект для работы с пользователями
$userAuth = new UserAuth($pdo);

// Получаем данные текущего пользователя
$currentUser = $userAuth->getUserById(SessionManager::getCurrentUserId());

// Переменные для сообщений
$successMessage = '';
$errorMessage = '';

// Обработка формы обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfile'])) {
    $fullName = trim($_POST['fullName']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Валидация данных
    if (empty($fullName)) {
        $errorMessage = 'Имя обязательно для заполнения';
    } else {
        // Обновляем профиль пользователя
        $updateData = [
            'fullName' => $fullName,
            'phone' => $phone,
            'address' => $address
        ];

        if ($userAuth->updateUserProfile($currentUser['id'], $updateData)) {
            $successMessage = 'Профиль успешно обновлен';
            // Обновляем данные в сессии
            $_SESSION['userName'] = $fullName;
            // Обновляем данные пользователя для отображения
            $currentUser = $userAuth->getUserById($currentUser['id']);
        } else {
            $errorMessage = 'Ошибка при обновлении профиля';
        }
    }
}

// Обработка формы смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changePassword'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Валидация пароля
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = 'Все поля пароля обязательны для заполнения';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'Новые пароли не совпадают';
    } elseif (strlen($newPassword) < 6) {
        $errorMessage = 'Новый пароль должен быть не менее 6 символов';
    } else {
        // Проверяем текущий пароль
        $user = $userAuth->getUserByEmail($currentUser['email']);
        if (!password_verify($currentPassword, $user['password'])) {
            $errorMessage = 'Текущий пароль неверен';
        } else {
            // Обновляем пароль
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $currentUser['id']]);
                $successMessage = 'Пароль успешно изменен';
            } catch (PDOException $e) {
                $errorMessage = 'Ошибка при изменении пароля';
            }
        }
    }
}

// Получаем историю заказов пользователя
function getUserOrders($userId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT o.*, 
                COUNT(oi.id) as itemsCount,
                SUM(oi.quantity) as totalQuantity
         FROM orders o 
         LEFT JOIN orderItems oi ON o.id = oi.orderId 
         WHERE o.email = (SELECT email FROM users WHERE id = ?)
         GROUP BY o.id 
         ORDER BY o.createdAt DESC"
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

$userOrders = getUserOrders($currentUser['id']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<!-- Подключаем шапку сайта -->
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="userWelcome">
        <h2>👋 Привет, <?php echo htmlspecialchars($currentUser['fullName']); ?>!</h2>
        <p>Добро пожаловать в ваш личный кабинет</p>
    </div>

    <!-- Вывод сообщений -->
    <?php if ($successMessage): ?>
        <div class="alert alertSuccess"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alertError"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="profileContent">
        <!-- Блок информации о профиле -->
        <div class="profileSection">
            <div class="profileInfo">
                <h3>📊 Информация о профиле</h3>

                <form method="POST" action="">
                    <input type="hidden" name="updateProfile" value="1">

                    <div class="formGroup">
                        <label for="fullName">Полное имя *</label>
                        <input type="text" id="fullName" name="fullName"
                               value="<?php echo htmlspecialchars($currentUser['fullName']); ?>" required>
                    </div>

                    <div class="formGroup">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                        <small style="color: #666;">Email нельзя изменить</small>
                    </div>

                    <div class="formGroup">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                    </div>

                    <div class="formGroup">
                        <label for="address">Адрес доставки</label>
                        <textarea id="address" name="address" rows="3"
                                  class="formControl"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btnPrimary">💾 Сохранить изменения</button>
                </form>
            </div>
        </div>

        <!-- Блок смены пароля -->
        <div class="profileSection">
            <div class="profileInfo">
                <h3>🔒 Смена пароля</h3>

                <form method="POST" action="">
                    <input type="hidden" name="changePassword" value="1">

                    <div class="formGroup">
                        <label for="currentPassword">Текущий пароль *</label>
                        <input type="password" id="currentPassword" name="currentPassword" required>
                    </div>

                    <div class="formGroup">
                        <label for="newPassword">Новый пароль *</label>
                        <input type="password" id="newPassword" name="newPassword" required>
                    </div>

                    <div class="formGroup">
                        <label for="confirmPassword">Подтвердите новый пароль *</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>

                    <button type="submit" class="btn btnPrimary">🔄 Сменить пароль</button>
                </form>
            </div>
        </div>

        <!-- Блок истории заказов -->
        <div class="profileSection">
            <div class="profileInfo">
                <h3>📦 История заказов</h3>

                <?php if (empty($userOrders)): ?>
                    <p>У вас еще нет заказов</p>
                    <a href="../main/products.php" class="btn btnPrimary">🛒 Сделать первый заказ</a>
                <?php else: ?>
                    <div class="ordersList">
                        <?php foreach ($userOrders as $order): ?>
                            <div class="orderItem">
                                <div class="orderHeader">
                                    <span class="orderNumber">Заказ #<?php echo $order['id']; ?></span>
                                    <span class="orderDate"><?php echo date('d.m.Y H:i', strtotime($order['createdAt'])); ?></span>
                                    <span class="orderStatus" style="
                                        background: <?php echo $order['status'] === 'completed' ? '#27ae60' : '#f39c12'; ?>;
                                        color: white;
                                        padding: 0.3rem 0.8rem;
                                        border-radius: 15px;
                                        font-size: 0.8rem;
                                        ">
                                            <?php echo $order['status'] === 'completed' ? '✅ Выполнен' : '🔄 В обработке'; ?>
                                        </span>
                                </div>
                                <div class="orderDetails">
                                    <p><strong>Товаров:</strong> <?php echo $order['itemsCount']; ?> позиций (<?php echo $order['totalQuantity']; ?> шт.)</p>
                                    <p><strong>Сумма:</strong> <?php echo formatPrice($order['totalAmount']); ?></p>
                                    <p><strong>Адрес:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем подвал сайта -->
<?php include '../includes/footer.php'; ?>
</body>
</html>
