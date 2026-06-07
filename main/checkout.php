<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/sessionManager.php';
require_once '../includes/cartIntegration.php';

// Получаем корзину пользователя
$cart = getCart();

// Если корзина пуста - переходим в корзину
if (empty($cart['items'])) {
    header('Location: cart.php');
    exit;
}

$error = '';
$success = '';

// Обрабатываем оформление заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $customerName = trim($_POST['customerName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Проверяем обязательные поля
    if (empty($customerName) || empty($email) || empty($address)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email адрес';
    } else {
        try {
            // Создаем массив с данными клиента
            $customerData = [
                    'customerName' => $customerName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address
            ];

            // Создаем заказ в базе данных с учетом авторизации
            if (SessionManager::isUserLoggedIn()) {
                // Для авторизованных пользователей
                $orderId = createUserOrder(
                        SessionManager::getCurrentUserId(),
                        $customerData,
                        $cart
                );
            } else {
                // Для гостей (старая функция)
                $orderId = createOrder($customerData, $cart);
            }

            // Очищаем корзину после успешного заказа
            clearCart();

            // Показываем сообщение об успехе
            $success = "Заказ №{$orderId} успешно оформлен! Скоро мы с вами свяжемся.";

        } catch (Exception $e) {
            $error = 'Произошла ошибка при оформлении заказа: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<!-- Подключаем шапку сайта -->
<?php include '../includes/header.php'; ?>

<main class="mainContent">
    <div class="container">
        <h1 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
            📦 Оформление заказа
        </h1>

        <!-- Если пользователь авторизован - показываем уведомление -->
        <?php if (SessionManager::isUserLoggedIn() && !$success): ?>
            <div class="alert alertSuccess" style="margin-bottom: 2rem;">
                ✅ Вы авторизованы как <?php echo htmlspecialchars(SessionManager::getUserName()); ?>
                <?php if (SessionManager::getUserEmail()): ?>
                    <br><small>Ваш email: <?php echo htmlspecialchars(SessionManager::getUserEmail()); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Если заказ успешно оформлен - показываем сообщение -->
        <?php if ($success): ?>
            <div style="background: #27ae60; color: white; padding: 2rem; border-radius: 10px; text-align: center; margin-bottom: 2rem;">
                <h3>🎉 <?php echo $success; ?></h3>
                <p>Спасибо за ваш заказ! One Love! ❤️</p>
                <a href="products.php" class="btn btnPrimary">Вернуться к покупкам</a>

                <?php if (!SessionManager::isUserLoggedIn()): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.3);">
                        <p>Хотите отслеживать статус заказа?</p>
                        <a href="../user/register.php" class="btn" style="background: white; color: #27ae60; margin: 0.5rem;">
                            📝 Зарегистрироваться
                        </a>
                        <a href="../user/login.php" class="btn" style="background: transparent; border: 2px solid white; color: white; margin: 0.5rem;">
                            🔑 Войти
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Если заказ еще не оформлен - показываем форму -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">

                <!-- Форма для ввода данных клиента -->
                <div>
                    <h2 style="color: #1a4721; margin-bottom: 1.5rem;">👤 Данные для заказа</h2>

                    <!-- Если есть ошибка - показываем ее -->
                    <?php if ($error): ?>
                        <div class="alert alertError">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Форма оформления заказа -->
                    <form method="POST" action="">
                        <!-- Поле для ФИО -->
                        <div class="formGroup">
                            <label for="customerName" class="formLabel">ФИО *</label>
                            <input type="text"
                                   id="customerName"
                                   name="customerName"
                                   value="<?php echo htmlspecialchars($_POST['customerName'] ?? (SessionManager::isUserLoggedIn() ? SessionManager::getUserName() : '')); ?>"
                                   class="formControl"
                                   required
                                   placeholder="Введите ваше полное имя">
                        </div>

                        <!-- Поле для Email -->
                        <div class="formGroup">
                            <label for="email" class="formLabel">Email *</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? (SessionManager::isUserLoggedIn() ? SessionManager::getUserEmail() : '')); ?>"
                                   class="formControl"
                                   required
                                   placeholder="example@mail.ru"
                                    <?php echo SessionManager::isUserLoggedIn() ? 'readonly' : ''; ?>>
                            <?php if (SessionManager::isUserLoggedIn()): ?>
                                <small style="color: #666;">Email нельзя изменить для авторизованных пользователей</small>
                            <?php endif; ?>
                        </div>

                        <!-- Поле для телефона -->
                        <div class="formGroup">
                            <label for="phone" class="formLabel">Телефон</label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   class="formControl"
                                   placeholder="+7 (999) 999-99-99">
                        </div>

                        <!-- Поле для адреса -->
                        <div class="formGroup">
                            <label for="address" class="formLabel">Адрес доставки *</label>
                            <textarea id="address"
                                      name="address"
                                      class="formControl"
                                      rows="4"
                                      required
                                      placeholder="Введите полный адрес доставки"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>

                        <!-- Кнопка подтверждения заказа -->
                        <button type="submit" class="btn btnSuccess" style="width: 100%; padding: 1rem; font-size: 1.2rem;">
                            ✅ Подтвердить заказ
                        </button>

                        <!-- Ссылка для неавторизованных пользователей -->
                        <?php if (!SessionManager::isUserLoggedIn()): ?>
                            <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                                <p style="color: #666; margin-bottom: 0.5rem;">Есть аккаунт?</p>
                                <a href="../user/login.php" style="color: #f9a602; text-decoration: none; font-weight: bold;">
                                    🔑 Войдите для быстрого оформления
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Блок с информацией о заказе -->
                <div>
                    <h2 style="color: #1a4721; margin-bottom: 1.5rem;">🛒 Ваш заказ</h2>

                    <!-- Список товаров в заказе -->
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #ddd;">
                                <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                                <span><?php echo formatPrice($item['subtotal']); ?></span>
                            </div>
                        <?php endforeach; ?>

                        <!-- Общая сумма заказа -->
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2rem; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #1a4721;">
                            <span>Итого:</span>
                            <span><?php echo formatPrice($cart['total']); ?></span>
                        </div>
                    </div>

                    <!-- Информация о доставке -->
                    <div style="background: #fff3cd; padding: 1rem; border-radius: 5px; border-left: 4px solid #f9a602;">
                        <p>🎵 <strong>One Love Delivery!</strong></p>
                        <p>Бесплатная доставка при заказе от 5000 ₽</p>
                        <p>Срок доставки: 1-3 рабочих дня</p>
                    </div>

                    <!-- Преимущества для авторизованных пользователей -->
                    <?php if (!SessionManager::isUserLoggedIn()): ?>
                        <div style="background: #e8f5e8; padding: 1rem; border-radius: 5px; border-left: 4px solid #27ae60; margin-top: 1rem;">
                            <p>🌟 <strong>Преимущества регистрации:</strong></p>
                            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                <li>Отслеживание статуса заказа</li>
                                <li>История всех заказов</li>
                                <li>Быстрое оформление</li>
                                <li>Сохранение корзины</li>
                            </ul>
                            <a href="../user/register.php" style="color: #27ae60; font-weight: bold;">
                                📝 Зарегистрироваться бесплатно
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Подключаем подвал сайта -->
<?php include '../includes/footer.php'; ?>
</body>
</html>
