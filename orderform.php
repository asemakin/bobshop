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
                // Используем orderId вместо id, так как в вашей форме используется price_[orderId]
                $productId = $product['id'] ??  null;

                if ($productId === null) {
                    continue; // Пропускаем товары без идентификатора
                }

                // Имя поля для количества товара
                $quantityField = 'productName' . $productId;
                // Получаем количество заказанного товара (по умолчанию 0)
                $quantity = isset($_POST[$quantityField]) ? (int)$_POST[$quantityField] : 0;

                // Если товар заказан (количество > 0)
                if ($quantity > 0) {
                    $hasItems = true; // Устанавливаем флаг, что в заказе есть товары

                    // Получаем цену товара из базы данных
                    $price = (float)$product['price'];

                    $itemTotal = $quantity * $price;   // Сумма за товар

                    // Добавляем товар в массив заказанных товаров
                    $orderedItems[] = [
                        'itemId' => $productId, // ID товара
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
                echo "<pre>POST данные: ";
                print_r($_POST);
                echo "</pre>";
                exit();
            }

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
                customerPhone, customerEmail, referralSourceId
            ) VALUES (
                :orderDate, :subTotal, :discount, :tax, :totalAmount,
                :deliveryAddress, :deliveryDate, :deliveryTime,
                :customerPhone, :customerEmail, :referralSourceId
            )");

            // Выполняем запрос с параметрами
            $stmt->execute([
                ':orderDate' => $orderDate,
                ':subTotal' => $subtotal,
                ':discount' => $discount_amount,
                ':tax' => $tax,
                ':totalAmount' => $totalamount,
                ':deliveryAddress' => $address,
                ':deliveryDate' => $deliveryDate ?: null,
                ':deliveryTime' => $deliveryTime ?: null,
                ':customerPhone' => $phone,
                ':customerEmail' => $email ?: null,
                ':referralSourceId' => $find ?: null
            ]);

            // Получаем ID созданного заказа
            $id = $conn->lastInsertId();

            // Сохраняем каждый товар из заказа в таблицу orderItem
            foreach ($orderedItems as $item) {
                $stmt = $conn->prepare("INSERT INTO `orderItem` (
                    orderNumber, productID, productName, quantity, price
                ) VALUES (
                    :orderNumber, :productID, :productName, :quantity, :price
                )");

                $stmt->execute([
                    ':orderNumber' => $id,
                    ':productID' => $item['itemId'],
                    ':productName' => $item['name'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price']
                ]);
            }

            // Определяем названия источников
            $sourceNames = [
                'a' => 'Постоянный клиент',
                'b' => 'Телереклама',
                'c' => 'Телефонный справочник',
                'd' => 'Рекомендация друга'
            ];

            // Сохраняем источник заказа, если он указан
            if (!empty($find) && isset($sourceNames[$find])) {
                $stmt = $conn->prepare("INSERT INTO `referralSource` 
                    (id, sourceName) 
                    VALUES (:id, :name)");
                $stmt->execute([
                    ':id' => $id,
                    ':name' => $sourceNames[$find]
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
            <h1 style="font-family: cursive; font-size: 30px; color: black;">+Автозапчасти Боба Марли+</h1>
            <h2 style="font-family: cursive; font-size: 20px; color: midnightblue;">-Результаты вашего заказа-</h2>

            <div>
                <button class="grey" onclick="window.location.href='orderforms.php'">
                    Вернуться к форме заказа
                </button>
            </div>

            <?php
            // Выводим основную информацию о заказе
            echo "<p>Заказ обработан: " . date("H:i, d.m.Y") . "</p>";
            echo "<p><strong>Номер вашего заказа: $id</strong></p>";
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
                    $mail->Subject = "Ваш заказ #$id принят";

                    // HTML-содержимое письма
                    $mail->isHTML(true);
                    $mail->Body = '
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение заказа #'.$id.'</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.5; 
            color: #333; 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 0; 
            background: #f5f5f5;
        }
        .container {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            margin: 20px 0;
        }
        .header { 
            background-color: #2c3e50; 
            color: white; 
            padding: 15px; 
            text-align: center;
            border-bottom: 3px solid #e67e22;
        }
        .content { 
            padding: 20px; 
        }
        .footer { 
            background-color: #ecf0f1; 
            padding: 15px; 
            text-align: center; 
            font-size: 12px; 
            color: #7f8c8d;
        }
        .order-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
            font-size: 14px;
        }
        .order-table th { 
            background-color: #34495e; 
            color: white; 
            padding: 8px; 
            text-align: left; 
        }
        .order-table td { 
            padding: 8px; 
            border-bottom: 1px solid #eee; 
        }
        .total-row { 
            font-weight: bold; 
            background: #f9f9f9;
        }
        .button { 
            display: inline-block; 
            background-color: #e67e22; 
            color: white; 
            padding: 8px 15px; 
            text-decoration: none; 
            border-radius: 3px; 
            margin-top: 10px;
            font-size: 14px;
        }
        .discount { 
            color: #e74c3c; 
        }
        .delivery-info {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 3px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;font-size:20px;">Автозапчасти Боба</h2>
            <p style="margin:5px 0 0;font-size:16px;">Заказ #'.$id.'</p>
        </div>
        
        <div class="content">
            <p>Здравствуйте,</p>
            <p>Ваш заказ успешно оформлен. Ниже приведены детали:</p>
            
            <h3 style="margin-top:20px;font-size:16px;">Состав заказа:</h3>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Наименование</th>
                        <th>Кол-во</th>
                        <th>Цена</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>';

                    // Добавляем строки с товарами
                    foreach ($orderedItems as $item) {
                        $mail->Body .= '
                <tr>
                    <td>'.htmlspecialchars($item['name']).'</td>
                    <td>'.$item['quantity'].'</td>
                    <td>$'.number_format($item['price'], 2).'</td>
                    <td>$'.number_format($item['total'], 2).'</td>
                </tr>';
                    }

                    // Добавляем скидку, если есть
                    if ($discount_amount > 0) {
                        $mail->Body .= '
                <tr class="discount">
                    <td colspan="3" style="text-align: right;">Скидка:</td>
                    <td>-$'.number_format($discount_amount, 2).'</td>
                </tr>';
                    }

                    $mail->Body .= '
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Итого:</td>
                    <td>$'.number_format($totalamount, 2).'</td>
                </tr>
                </tbody>
            </table>
            
            <div class="delivery-info">
                <h3 style="margin-top:0;font-size:16px;">Доставка:</h3>
                <p><strong>Адрес:</strong> '.htmlspecialchars($address).'</p>'.
                        (!empty($deliveryDate) ? '<p><strong>Дата:</strong> '.htmlspecialchars($deliveryDate).'</p>' : '').
                        (!empty($deliveryTime) ? '<p><strong>Время:</strong> '.htmlspecialchars($deliveryTime).'</p>' : '').'
                <p><strong>Телефон:</strong> '.htmlspecialchars($phone).'</p>
            </div>
            
            <p style="text-align:center;">
                <a href="https://bob-autoparts.ru/track?order='.$id.'" class="button">Отследить заказ</a>
            </p>
            
            <p>По вопросам звоните: <strong>+7 (123) 456-78-90</strong></p>
        </div>
        
        <div class="footer">
            <p>© '.date('Y').' Автозапчасти Боба</p>
            <p>г. Москва, ул. Автозапчастей, 42</p>
        </div>
    </div>
</body>
</html>';

                    // Текстовая версия письма
                    $mail->AltBody = "Автозапчасти Боба\n\n"
                        . "Ваш заказ #$id\n\n"
                        . "Товары:\n";

                    foreach ($orderedItems as $item) {
                        $mail->AltBody .= "- ".htmlspecialchars($item['name'])." (".$item['quantity']." шт.) - $".number_format($item['total'], 2)."\n";
                    }

                    if ($discount_amount > 0) {
                        $mail->AltBody .= "\nСкидка: -$".number_format($discount_amount, 2)."\n";
                    }

                    $mail->AltBody .= "\nИтого: $".number_format($totalamount, 2)."\n\n"
                        . "Доставка:\n"
                        . "Адрес: ".htmlspecialchars($address)."\n";

                    if (!empty($deliveryDate)) {
                        $mail->AltBody .= "Дата: ".htmlspecialchars($deliveryDate)."\n";
                    }

                    if (!empty($deliveryTime)) {
                        $mail->AltBody .= "Время: ".htmlspecialchars($deliveryTime)."\n";
                    }

                    $mail->AltBody .= "\nТелефон: ".htmlspecialchars($phone)."\n\n"
                        . "Спасибо за заказ!\n"
                        . "Автозапчасти Боба\n"
                        . "Тел: +7 (123) 456-78-90";

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
        } catch (PDOException $e) {
            // В случае ошибки откатываем транзакцию
            if (isset($conn)) $conn->rollBack();
            echo "<p class='error'>Ошибка базы данных: " . $e->getMessage() . "</p>";
            echo "<pre>POST данные: ";
            print_r($_POST);
            echo "</pre>";
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollBack();
            echo "<p class='error'>Ошибка: " . $e->getMessage() . "</p>";
            echo "<pre>POST данные: ";
            print_r($_POST);
            echo "</pre>";
        }
    } else {
        // Если адрес не был указан
        echo "<p class='error'>Пожалуйста, укажите адрес доставки</p>";
        echo "<pre>POST данные: ";
        print_r($_POST);
        echo "</pre>";
    }
} else {
    // Если страница была загружена не через POST-запрос
    echo "<p class='error'>Неверный метод запроса. Пожалуйста, отправьте форму.</p>";
    echo "<pre>POST данные: ";
    print_r($_POST);
    echo "</pre>";
}
?>

