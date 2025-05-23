<?php
/* Включение отображения всех ошибок для отладки */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключаем PHPMailer для отправки писем
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer;
require_once("functions.php");

// Проверка, был ли отправлен POST-запрос
if ($_SERVER["REQUEST_METHOD"] == "POST") {
// Безопасное получение адреса доставки (если нет - пустая строка)
$address = $_POST['address'] ?? '';

// Валидация адреса (если не пустой)
if (!empty($address)) {
$orderNumber = generateOrderNumber(); // Генерируем номер и сохраняем

$delivery_date = $_POST['delivery_date'] ?? '';
$delivery_time = $_POST['delivery_time'] ?? '';
$phone = $_POST['tel'] ?? '';
$email = $_POST['email'] ?? '';
$find = $_POST['find'] ?? '';

/* ===== НАЧАЛО HTML-СТРАНИЦЫ С РЕЗУЛЬТАТАМИ ===== */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Автозапчасти Боба Марли - Результаты заказа</title>
    <link rel="stylesheet" href="orderform.css">
</head>
<body>
<h1 style="font-family: cursive; font-size: 30px; color: black;">Автозапчасти Боба Марли</h1>
<h2 style="font-family: cursive; font-size: 20px; color: black;">Результаты заказа</h2>

<div>
    <button class="grey" onclick="window.location.href='orderforms.php'">
        К форме заказа
    </button>
</div>

<?php
/* ===== ВЫВОД ИНФОРМАЦИИ О ЗАКАЗЕ ===== */
$months = [
    1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
];
$month = date('n');

echo "<p>Ваш заказ обработан в: " . date("H:i, d-") . $months[$month] . date("-Y") . "</p>\n";
echo "<p><strong>Номер вашего заказа: $orderNumber</strong></p>";
echo "<p class='blue'>Адрес для доставки: " . htmlspecialchars($address) . "</p>";
echo "<p class='blue'>Дата доставки: " . (!empty($delivery_date) ? $delivery_date : 'не указана') . "</p>";
echo "<p class='blue'>Время доставки: " . (!empty($delivery_time) ? $delivery_time : 'не указано') . "</p>";
echo "<p class='blue'>Ваш номер телефона: " . (!empty($phone) ? htmlspecialchars($phone) : 'не указан') . "</p>";
echo "<p class='blue'>Ваша электронная почта: " . (!empty($email) ? htmlspecialchars($email) : 'не указана') . "</p>";
echo "Ваш заказ выглядит следующим образом:<br>\n<br>\n";

/* ===== ОБРАБОТКА ТОВАРОВ ИЗ ЗАКАЗА ===== */
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bob_auto_parts';

try {
    $conn = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



// Получаем все товары из базы
    $stmt = $conn->query("SELECT * FROM warehouse");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orderedItems = [];
    $subtotal = 0;
    $totalqty = 0;
    $discount_amount = 0;
    $hasItems = false;

    foreach ($products as $product) {
        // Формируем имя поля как в форме
        $fieldName = 'product_' . $product['id'];

        // Получаем количество
        $quantity = isset($_POST[$fieldName]) ? (int)$_POST[$fieldName] : 0;

        if ($quantity > 0) {
            $hasItems = true;
            $price = (float)$product['price'];
            $itemTotal = $quantity * $price;

            $orderedItems[] = [
                'id' => $product['id'],
                'name' => $product['product_name'],
                'quantity' => $quantity,  // Теперь будет правильное количество
                'price' => $price,
                'total' => $itemTotal
            ];

            $subtotal += $itemTotal;
            $totalqty += $quantity;



            // Вывод информации о товаре
                echo htmlspecialchars($product['product_name']) . ": $quantity шт. × $".number_format($price, 2)." = $".number_format($itemTotal, 2)."<br>\n";

                // Проверка скидки для шин
                if (stripos($product['product_name'], 'шины') !== false && $quantity >= 10) {
                    if ($quantity <= 49) {
                        $discount = 5;
                    } elseif ($quantity >= 50 && $quantity <= 99) {
                        $discount = 10;
                    } elseif ($quantity >= 100) {
                        $discount = 15;
                    }

                    $itemDiscount = ($quantity * $price) * ($discount / 100);
                    $discount_amount += $itemDiscount;

                    echo '<p style="font-family: cursive; font-size: 15px; color: forestgreen;">';
                    echo "Предоставляется скидка на шины: -$discount%<br>\n";
                    echo "Сумма скидки: $".number_format($itemDiscount, 2)."<br>\n";
                    echo '</p>';
                }
            }
        }
    echo "<pre>POST данные:";
    print_r($_POST);
    echo "</pre>";


    if (!$hasItems) {
        echo "<p class='highlight'>Вы ничего не заказывали на предыдущей странице!</p>";
    } else {
        $subtotal -= $discount_amount;

        echo "<br>\n";
        echo "Заказано товаров в количестве: $totalqty шт. <br>\n";
        echo "Промежуточный итог: $".number_format($subtotal, 2)."<br>\n";

        // Расчет налога (10%)
        $taxrate = 0.10;
        $tax = $subtotal * $taxrate;
        $totalamount = $subtotal + $tax;
        echo "Итого, включая налог: $".number_format($totalamount, 2)."<br>\n";

        /* ===== СОХРАНЕНИЕ ЗАКАЗА В БАЗУ ДАННЫХ ===== */
        $orderDate = date("Y-m-d H:i:s");

        // Сохраняем основной заказ
        $stmt = $conn->prepare("INSERT INTO orders (
                    id, 
                    order_date, 
                    subtotal, 
                    discount, 
                    tax, 
                    total_amount, 
                    delivery_address, 
                    delivery_date, 
                    delivery_time, 
                    customer_phone, 
                    customer_email, 
                    referral_source
                ) VALUES (
                    :id, 
                    :order_date, 
                    :subtotal, 
                    :discount, 
                    :tax, 
                    :total_amount, 
                    :delivery_address, 
                    :delivery_date, 
                    :delivery_time, 
                    :customer_phone, 
                    :customer_email, 
                    :referral_source
                )");

        $stmt->execute([
            ':id' => $orderNumber,
            ':order_date' => $orderDate,
            ':subtotal' => $subtotal,
            ':discount' => $discount_amount,
            ':tax' => $tax,
            ':total_amount' => $totalamount,
            ':delivery_address' => $address,
            ':delivery_date' => $delivery_date,
            ':delivery_time' => $delivery_time,
            ':customer_phone' => $phone,
            ':customer_email' => $email,
            ':referral_source' => $find
        ]);

        // Сохраняем товары заказа
        foreach ($orderedItems as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (
                        id, 
                        product_id, 
                        product_name, 
                        quantity, 
                        price
                    ) VALUES (
                        :id, 
                        :product_id, 
                        :product_name, 
                        :quantity, 
                        :price
                    )");

            $stmt->execute([
                ':id' => $orderNumber,
                ':product_id' => $item['id'],
                ':product_name' => $item['name'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price']
            ]);
        }

        echo "<p>Заказ успешно сохранен в базе данных!</p>";

        /* ===== ОТПРАВКА ПИСЬМА КЛИЕНТУ ===== */
        if (!empty($email) && $hasItems) {
            $mail = new PHPMailer\PHPMailer(true);

            try {
                // Настройки SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'warnawa80@gmail.com';
                $mail->Password = 'zcwa awyh assr kxcl';
                $mail->SMTPSecure = PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                // Отправитель и получатель
                $mail->setFrom('warnawa80@gmail.com', 'Автозапчасти Боба Марли');
                $mail->addAddress($email);

                // Тема письма
                $mail->Subject = "🚗 Ваш заказ #$orderNumber готов к отправке!";
                $mail->isHTML(true);

                // HTML-версия письма
                $mail->Body = '
                        <!DOCTYPE html>
                        <html lang="ru">
                        <head>
                            <meta charset="UTF-8">
                            <title>Ваш заказ #'.$orderNumber.'</title>
                        </head>
                        <body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5;">
                            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                                <!-- Шапка -->
                                <div style="background: linear-gradient(to right, #1a3e72, #2a5298); padding: 25px; text-align: center; color: white;">
                                    <h1 style="margin: 0; font-size: 28px;">🛠️ Автозапчасти Боба Марли</h1>
                                </div>
                                
                                <!-- Номер заказа -->
                                <div style="background: #f7931e; padding: 15px; text-align: center; color: white;">
                                    <h2 style="margin: 0; font-size: 22px;">Ваш заказ #'.$orderNumber.'</h2>
                                </div>
                                
                                <!-- Основное содержимое -->
                                <div style="padding: 20px;">
                                    <p style="font-size: 16px; margin-bottom: 20px;">Спасибо за доверие! Ваш заказ принят в обработку.</p>
                                    
                                    <!-- Детали заказа -->
                                    <div style="background: #f9f9f9; border-left: 4px solid #f7931e; padding: 15px; margin-bottom: 20px;">
                                        <h3 style="margin-top: 0; color: #1a3e72;">📅 Детали заказа</h3>
                                        <p style="margin: 5px 0;"><strong>Дата:</strong> '.date("H:i, d-") . $months[$month] . date("-Y").'</p>
                                    </div>
                                    
                                    <!-- Состав заказа -->
                                    <h3 style="color: #1a3e72; border-bottom: 2px solid #f7931e; padding-bottom: 5px;">🛒 Состав заказа</h3>';

                // Добавляем товары в письмо
                foreach ($orderedItems as $item) {
                    $mail->Body .= '
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #ddd;">
                                        <div>
                                            <strong>'.htmlspecialchars($item['name']).'</strong>
                                        </div>
                                        <div style="text-align: right;">
                                            <p style="margin: 0;">'.$item['quantity'].' шт. × $'.number_format($item['price'], 2).'</p>
                                            <p style="margin: 5px 0; font-weight: bold;">$'.number_format($item['total'], 2).'</p>
                                        </div>
                                    </div>';
                }

                // Добавляем скидку, если есть
                if ($discount_amount > 0) {
                    $mail->Body .= '
                                    <div style="background: #fff8e1; padding: 10px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <strong>🎉 Ваша скидка</strong>
                                            <strong style="color: #e53935;">-$'.number_format($discount_amount, 2).'</strong>
                                        </div>
                                    </div>';
                }

                // Итоговая сумма
                $mail->Body .= '
                                    <!-- Итоги -->
                                    <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span>Промежуточный итог:</span>
                                            <span>$'.number_format($subtotal, 2).'</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span>Налог (10%):</span>
                                            <span>$'.number_format($tax, 2).'</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: #1a3e72;">
                                            <span>Итоговая сумма:</span>
                                            <span>$'.number_format($totalamount, 2).'</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Детали доставки -->
                                    <h3 style="color: #1a3e72; border-bottom: 2px solid #f7931e; padding-bottom: 5px;">🚚 Детали доставки</h3>
                                    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                                        <p style="margin: 5px 0;"><strong>Адрес:</strong> '.htmlspecialchars($address).'</p>
                                        <p style="margin: 5px 0;"><strong>Дата доставки:</strong> '.(!empty($delivery_date) ? htmlspecialchars($delivery_date) : 'не указана').'</p>
                                        <p style="margin: 5px 0;"><strong>Время доставки:</strong> '.(!empty($delivery_time) ? htmlspecialchars($delivery_time) : 'не указано').'</p>
                                        <p style="margin: 5px 0;"><strong>Контактный телефон:</strong> '.htmlspecialchars($phone).'</p>
                                    </div>
                                    
                                    <!-- Кнопка для отслеживания -->
                                    <div style="text-align: center; margin: 25px 0;">
                                        <a href="https://вашмагазин.ru/track?order='.$orderNumber.'" style="display: inline-block; background: #f7931e; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;">Отследить заказ</a>
                                    </div>
                                    
                                    <!-- Контакты -->
                                    <div style="text-align: center; color: #666; font-size: 14px; padding-top: 20px; border-top: 1px solid #eee;">
                                        <p style="margin: 5px 0;">🚗 <strong>Автозапчасти Боба Марли</strong></p>
                                        <p style="margin: 5px 0;">☎️ +7 (123) 456-78-90</p>
                                        <p style="margin: 5px 0;">🏠 г. Москва, ул. Автозапчастей, 42</p>
                                    </div>
                                </div>
                                
                                <!-- Подвал -->
                                <div style="background: #333; color: #aaa; padding: 15px; text-align: center; font-size: 12px;">
                                    <p style="margin: 5px 0;">© '.date('Y').' Автозапчасти Боба Марли. Все права защищены.</p>
                                </div>
                            </div>
                        </body>
                        </html>';

                // Текстовая версия письма
                $mail->AltBody = "АВТОЗАПЧАСТИ БОБА МАРЛИ\n\n"
                    . "Ваш заказ #$orderNumber\n"
                    . "Дата: " . date("H:i, d-") . $months[$month] . date("-Y") . "\n\n"
                    . "СОСТАВ ЗАКАЗА:\n";

                foreach ($orderedItems as $item) {
                    $mail->AltBody .= htmlspecialchars($item['name']) . ": {$item['quantity']} шт. × $".number_format($item['price'], 2)." = $".number_format($item['total'], 2)."\n";
                }

                $mail->AltBody .= ($discount_amount > 0 ? "Скидка: -$".number_format($discount_amount, 2)."\n" : "")
                    . "Промежуточный итог: $".number_format($subtotal, 2)."\n"
                    . "Налог (10%): $".number_format($tax, 2)."\n"
                    . "Сумма к оплате: $".number_format($totalamount, 2)."\n\n"
                    . "ДОСТАВКА:\n"
                    . "Адрес: $address\n"
                    . "Дата: ".(!empty($delivery_date) ? $delivery_date : 'не указана')."\n"
                    . "Время: ".(!empty($delivery_time) ? $delivery_time : 'не указано')."\n"
                    . "Телефон: $phone\n\n"
                    . "Спасибо за покупку!";

                // Отправка письма
                $mail->send();
                echo "<p style='color: green; padding: 10px; background: #e8f5e9;'>Письмо с подтверждением успешно отправлено!</p>";
            } catch (Exception $e) {
                echo "<p style='color: red; padding: 10px; background: #ffebee;'>Ошибка отправки: {$mail->ErrorInfo}</p>";
            }
        }
    }

    /* ===== ВЫВОД СПОСОБА ПРИВЛЕЧЕНИЯ КЛИЕНТА ===== */
    if ($find == "a") {
        echo "<p class='highlight'>Вы постоянный клиент.!!!</p>";
    } elseif ($find == "b") {
        echo "<p class='highlight'>Привлечение клиентов по телерекламе</p>";
    } elseif ($find == "c") {
        echo "<p class='highlight'>Ссылка на клиента по телефонному справочнику</p>";
    } elseif ($find == "d") {
        echo "<p class='highlight'>Обращение к клиенту из уст в уста</p>";
    }

} catch(PDOException $e) {
    echo "<p class='highlight'>Ошибка базы данных: " . $e->getMessage() . "</p>";
}
} else {
    // Если адрес не указан
    echo "<p class='highlight'>Адрес доставки не указан. Данные не будут сохранены.</p>";
}
}
?>
</body>
</html>

<footer>
    <?php
    include("time.php"); // Подключение внешнего файла с временем
    ?>
</footer>
</html>