<?php
// Включение отображения всех ошибок для удобства отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение необходимых библиотек
require __DIR__ . '/vendor/autoload.php'; // PHPMailer для отправки писем
use PHPMailer\PHPMailer;
require_once("functions.php"); // Пользовательские функции

// Проверяем, что форма была отправлена методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаем и проверяем основные данные из формы
    $address = $_POST['address'] ?? '';

    // Если адрес доставки указан - продолжаем обработку
    if (!empty($address)) {
        // Получаем дополнительные данные из формы
        $deliveryDate = $_POST['deliveryDate'] ?? '';
        $deliveryTime = $_POST['deliveryTime'] ?? '';
        $phone = $_POST['tel'] ?? '';
        $email = $_POST['email'] ?? '';
        $find = $_POST['find'] ?? '';

        // Настройки подключения к базе данных
        $dbHost = 'localhost';
        $dbUser = 'root';
        $dbPass = '';
        $dbName = 'bob_auto_parts';

        try {
            // Устанавливаем соединение с базой данных
            $conn = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Получаем список всех товаров из базы данных
            $stmt = $conn->query("SELECT * FROM `warehouse`");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Инициализируем переменные для хранения информации о заказе
            $orderedItems = []; // Массив для хранения заказанных товаров
            $subtotal = 0;      // Общая сумма заказа без учета скидок и налогов
            $totalqty = 0;      // Общее количество товаров в заказе
            $discount_amount = 0; // Сумма скидки
            $hasItems = false;  // Флаг, указывающий есть ли товары в заказе

            // Обрабатываем каждый товар из базы данных
            foreach ($products as $product) {
                // Формируем имя поля для количества товара в форме
                $fieldName = 'productName' . $product['productID'];
                // Получаем количество заказанного товара (по умолчанию 0)
                $quantity = isset($_POST[$fieldName]) ? (int)$_POST[$fieldName] : 0;

                // Если товар заказан (количество > 0)
                if ($quantity > 0) {
                    $hasItems = true; // Устанавливаем флаг, что в заказе есть товары
                    $price = (float)$product['price']; // Цена товара
                    $itemTotal = $quantity * $price;   // Сумма за товар

                    // Добавляем товар в массив заказанных товаров
                    $orderedItems[] = [
                        'itemID' => $product['productID'], // ID товара
                        'name' => $product['productName'], // Название товара
                        'quantity' => $quantity,           // Количество
                        'price' => $price,                // Цена за единицу
                        'total' => $itemTotal             // Общая стоимость
                    ];

                    // Увеличиваем общую сумму и количество товаров
                    $subtotal += $itemTotal;
                    $totalqty += $quantity;
                }
            }

            // Если в заказе нет товаров
            if (!$hasItems) {
                echo "<p class='highlight'>Вы не выбрали ни одного товара!</p>";
            } else {
                // Применяем скидку к общей сумме (если скидка есть)
                $subtotal -= $discount_amount;

                // Рассчитываем налог (10%) и итоговую сумму
                $taxrate = 0.10;
                $tax = $subtotal * $taxrate;
                $totalamount = $subtotal + $tax;

                // Начинаем транзакцию для сохранения заказа
                $conn->beginTransaction();
                $orderDate = date("Y-m-d H:i:s"); // Текущая дата и время

                // Подготавливаем запрос для сохранения основного заказа
                $stmt = $conn->prepare("INSERT INTO `order` (
                    orderDate, subTotal, discount, tax, totalAmount,
                    deliveryAddress, deliveryDate, deliveryTime,
                    customerPhone, customerEmail, referralSource
                ) VALUES (
                    :orderDate, :subTotal, :discount, :tax, :totalAmount,
                    :deliveryAddress, :deliveryDate, :deliveryTime,
                    :customerPhone, :customerEmail, :referralSource
                )");

                // Выполняем запрос с параметрами
                $stmt->execute([
                    ':orderDate' => $orderDate,
                    ':subTotal' => $subtotal,
                    ':discount' => $discount_amount,
                    ':tax' => $tax,
                    ':totalAmount' => $totalamount,
                    ':deliveryAddress' => $address,
                    ':deliveryDate' => $deliveryDate ?: null, // Если дата не указана - сохраняем NULL
                    ':deliveryTime' => $deliveryTime ?: null, // Если время не указано - сохраняем NULL
                    ':customerPhone' => $phone,
                    ':customerEmail' => $email ?: null,       // Если email не указан - сохраняем NULL
                    ':referralSource' => $find ?: null       // Если источник не указан - сохраняем NULL
                ]);

                // Получаем ID созданного заказа
                $orderID = $conn->lastInsertId();

                // Сохраняем каждый товар из заказа в таблицу orderitems
                foreach ($orderedItems as $item) {
                    $stmt = $conn->prepare("INSERT INTO `orderitems` (
                        orderNumber, productID, productName, quantity, price
                    ) VALUES (
                        :orderNumber, :productID, :productName, :quantity, :price
                    )");

                    $stmt->execute([
                        ':orderNumber' => $orderID,       // ID заказа
                        ':productID' => $item['itemID'],  // ID товара
                        ':productName' => $item['name'],  // Название товара
                        ':quantity' => $item['quantity'], // Количество
                        ':price' => $item['price']        // Цена
                    ]);
                }

                // Завершаем транзакцию - сохраняем все изменения
                $conn->commit();

                // Выводим HTML-страницу с результатами заказа
                ?>
                <!DOCTYPE html>
                <html lang="ru">
                <head>
                    <meta charset="UTF-8">
                    <title>Результаты заказа - Автозапчасти Боба Марли</title>
                    <link rel="stylesheet" href="orderform.css">
                </head>
                <body>
                <h1>Автозапчасти Боба Марли</h1>
                <h2>Результаты вашего заказа</h2>

                <div>
                    <button class="grey" onclick="window.location.href='orderforms.php'">
                        Вернуться к форме заказа
                    </button>
                </div>

                <?php
                // Выводим основную информацию о заказе
                echo "<p>Заказ обработан: " . date("H:i, d.m.Y") . "</p>";
                echo "<p><strong>Номер вашего заказа: $orderID</strong></p>";
                echo "<p class='blue'>Адрес доставки: " . htmlspecialchars($address) . "</p>";
                echo "<p class='blue'>Дата доставки: " . (!empty($deliveryDate) ? htmlspecialchars($deliveryDate) : 'не указана') . "</p>";
                echo "<p class='blue'>Время доставки: " . (!empty($deliveryTime) ? htmlspecialchars($deliveryTime) : 'не указано') . "</p>";
                echo "<p class='blue'>Телефон: " . htmlspecialchars($phone) . "</p>";
                echo "<p class='blue'>Email: " . (!empty($email) ? htmlspecialchars($email) : 'не указан') . "</p>";

                echo "<h3>Состав заказа:</h3>";

                // Выводим список заказанных товаров
                foreach ($orderedItems as $item) {
                    echo htmlspecialchars($item['name']) . ": {$item['quantity']} шт. × $" .
                        number_format($item['price'], 2) . " = $" .
                        number_format($item['total'], 2) . "<br>";
                }

                // Выводим итоговую информацию
                echo "<h3>Итоговая информация:</h3>";
                echo "<p>Всего товаров: $totalqty шт.</p>";
                echo "<p>Общая сумма: $" . number_format($totalamount, 2) . "</p>";
                ?>

                <!-- Отправка письма с подтверждением заказа -->
                <?php
                if (!empty($email) && $hasItems) {
                    try {
                        $mail = new PHPMailer\PHPMailer(true);

                        // Настройки SMTP
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'warnawa80@gmail.com';
                        $mail->Password = 'zcwa awyh assr kxcl';
                        $mail->SMTPSecure = PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->CharSet = 'UTF-8';

                        // Настройки письма
                        $mail->setFrom('warnawa80@gmail.com', 'Автозапчасти Боба Марли');
                        $mail->addAddress($email);
                        $mail->Subject = "Ваш заказ #$orderID принят";

                        // HTML-содержимое письма
                        $mail->isHTML(true);
                        $mail->Body = "
                                <h2>Спасибо за ваш заказ #$orderID</h2>
                                <p>Дата заказа: " . date("d.m.Y H:i") . "</p>
                                <h3>Состав заказа:</h3>
                                <ul>";

                        foreach ($orderedItems as $item) {
                            $mail->Body .= "<li>{$item['name']} - {$item['quantity']} шт. × $" .
                                number_format($item['price'], 2) . " = $" .
                                number_format($item['total'], 2) . "</li>";
                        }

                        $mail->Body .= "</ul>
                                <p><strong>Итого: $" . number_format($totalamount, 2) . "</strong></p>
                                <p>Адрес доставки: " . htmlspecialchars($address) . "</p>";

                        // Отправляем письмо
                        $mail->send();
                        echo "<p class='success'>Письмо с подтверждением заказа отправлено на $email</p>";
                    } catch (Exception $e) {
                        echo "<p class='error'>Не удалось отправить письмо: " . $e->getMessage() . "</p>";
                    }
                }
                ?>

                <!-- Вывод информации о способе привлечения клиента -->
                <?php
                switch ($find) {
                    case "a":
                        echo "<p class='highlight'>Вы постоянный клиент!</p>";
                        break;
                    case "b":
                        echo "<p class='highlight'>Вы узнали о нас из телерекламы</p>";
                        break;
                    case "c":
                        echo "<p class='highlight'>Вы нашли нас в телефонном справочнике</p>";
                        break;
                    case "d":
                        echo "<p class='highlight'>Вы узнали о нас от друзей</p>";
                        break;
                }
                ?>
                <footer>
                    <?php include("time.php");?>
                </footer>
                </body>
                </html>
                <?php
            }
        } catch (PDOException $e) {
            // В случае ошибки откатываем транзакцию
            if (isset($conn)) $conn->rollBack();
            echo "<p class='error'>Ошибка базы данных: " . $e->getMessage() . "</p>";
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollBack();
            echo "<p class='error'>Ошибка: " . $e->getMessage() . "</p>";
        }
    } else {
        // Если адрес не был указан
        echo "<p class='error'>Пожалуйста, укажите адрес доставки</p>";
    }
} else {
    // Если страница была загружена не через POST-запрос
    echo "<p class='error'>Неверный метод запроса. Пожалуйста, отправьте форму.</p>";
}
?>