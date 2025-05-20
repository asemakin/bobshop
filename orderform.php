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
$delivery_time = $_POST['delivery_time'] ?? ''; // Время доставки
$phone = $_POST['tel'] ?? ''; // Номер телефона
$email = $_POST['email'] ?? ''; // Электронная почта
// Количество товаров (если нет - 0)
$tireqty = isset($_POST['tireqty']) ? intval($_POST['tireqty']) : 0;
$oilqty = isset($_POST['oilqty']) ? intval($_POST['oilqty']) : 0;
$sparkqty = isset($_POST['sparkqty']) ? intval($_POST['sparkqty']) : 0;

// Как нашли магазин (если нет - пустая строка)
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

// Массив месяцев для красивого вывода
$months = [
    1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
];

// Текущий месяц (цифра)
$month = date('n');

// Вывод времени обработки заказа (текущие дата и время)
echo "<p>Ваш заказ обработан в : " . date("H:i, d-") . $months[$month] . date("-Y") . "</p>\n";

// Вывод номера заказа
echo "<p><strong>Номер вашего заказа: $orderNumber</strong></p>";

// Вывод данных доставки
echo "<p class='blue'>Адрес для доставки : " . htmlspecialchars($address) . "</p>";
echo "<p class='blue'>Дата доставки : " . (!empty($delivery_date) ? $delivery_date : 'не указана') . "</p>";
echo "<p class='blue'>Время доставки : " . (!empty($delivery_time) ? $delivery_time : 'не указано') . "</p>";
echo "<p class='blue'>Ваш номер телефона : " . (!empty($phone) ? htmlspecialchars($phone) : 'не указан') . "</p>";
echo "<p class='blue'>Ваша электронная почта :  " . (!empty($email) ? htmlspecialchars($email) : 'не указан') . "</p>";
echo "Ваш заказ выглядит следующим образом:<br>\n";
echo "<br>\n";

/* ===== РАСЧЕТ СТОИМОСТИ ЗАКАЗА ===== */
// Цены на товары (константы)
define("TIREPRICE", 100); // Цена шины
define("OILPRICE", 10);   // Цена масла
define("SPARKPRICE", 4);  // Цена свечи

// Вывод количества каждого товара
echo "Шины : $tireqty шт. <br>\n";
echo "бутылки с маслом : $oilqty шт. <br>\n";
echo "Свечи зажигания : $sparkqty шт. <br>\n";

// Общее количество товаров
$totalqty = $tireqty + $oilqty + $sparkqty;
echo "<br>\n";
echo "Заказано товаров в количестве : $totalqty шт. <br>\n";

// Если ничего не заказано
if ($totalqty == 0) {
    echo "<p class='highlight'>Вы ничего не заказывали на предыдущей странице!</p>";
}

// Расчет общей суммы
$subtotal = ($tireqty * TIREPRICE) + ($oilqty * OILPRICE) + ($sparkqty * SPARKPRICE);
$discount_amount = 0;

/* ===== СКИДКИ НА ШИНЫ ===== */
if ($tireqty >= 10) {
    // Определяем размер скидки
    if ($tireqty <= 49) {
        $discount = 5; // 5% при 10-49 шин
    } elseif ($tireqty >= 50 && $tireqty <= 99) {
        $discount = 10; // 10% при 50-99 шин
    } elseif ($tireqty >= 100) {
        $discount = 15; // 15% при 100+ шин
    }

    // Расчет суммы скидки
    $discount_amount = ($tireqty * TIREPRICE) * ($discount / 100);
    $subtotal -= $discount_amount; // Вычитаем скидку

    // Вывод информации о скидке
    echo '<p style="font-family: cursive; font-size: 15px; color: forestgreen;";>';
    echo "Предоставляется скидка: -$discount%<br>\n";
    echo "Сумма скидки на шины: $".number_format($discount_amount, 2, '.', '')."<br>\n";
    echo '</p>';
}

/* ===== ВЫВОД СУММ ЗАКАЗА ===== */
echo "Промежуточный итог: $".number_format($subtotal, 2, '.', '')."<br>\n";

// Расчет налога (10%)
$taxrate = 0.10;
$tax = $subtotal * $taxrate;
$totalamount = $subtotal + $tax;
echo "Итого, включая налог: $".number_format($totalamount, 2, '.', '')."<br>\n";

/* ===== СОХРАНЕНИЕ ЗАКАЗА В БАЗУ ДАННЫХ ===== */
$orderDate = date("Y-m-d H:i:s"); // Формат для MySQL

// Настройки подключения к базе данных
$dbHost = 'localhost';
$dbUser = 'root'; // Замените на ваше имя пользователя
$dbPass = ''; // Замените на ваш пароль
$dbName = 'bob_auto_parts';

try {
    // Создаем подключение
    $conn = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Подготовка SQL-запроса
    $stmt = $conn->prepare("INSERT INTO orders (
                order_number, 
                order_date, 
                tire_quantity, 
                oil_quantity, 
                spark_quantity,
                total_quantity,   
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
                :order_number, 
                :order_date, 
                :tire_quantity, 
                :oil_quantity, 
                :spark_quantity,
                :total_quantity,      
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

    // Привязка параметров
    $stmt->bindParam(':order_number', $orderNumber);
    $stmt->bindParam(':order_date', $orderDate);
    $stmt->bindParam(':tire_quantity', $tireqty);
    $stmt->bindParam(':oil_quantity', $oilqty);
    $stmt->bindParam(':spark_quantity', $sparkqty);
    $stmt->bindParam(':total_quantity', $totalqty);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':discount', $discount_amount);
    $stmt->bindParam(':tax', $tax);
    $stmt->bindParam(':total_amount', $totalamount);
    $stmt->bindParam(':delivery_address', $address);
    $stmt->bindParam(':delivery_date', $delivery_date);
    $stmt->bindParam(':delivery_time', $delivery_time);
    $stmt->bindParam(':customer_phone', $phone);
    $stmt->bindParam(':customer_email', $email);
    $stmt->bindParam(':referral_source', $find);

    // Выполнение запроса
    $stmt->execute();

    echo "<p>Заказ успешно сохранен в базе данных!</p>";
} catch(PDOException $e) {
    echo "<p class='highlight'>Ошибка сохранения в базу данных: " . $e->getMessage() . "</p>";
}

/* ===== СТИЛИЗОВАННОЕ ПИСЬМО ДЛЯ МАГАЗИНА АВТОЗАПЧАСТЕЙ ===== */
if (!empty($email)) {
    $mail = new PHPMailer(true);

    try {
        // Настройки SMTP (оставляем без изменений)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'warnawa80@gmail.com';
        $mail->Password = 'zcwa awyh assr kxcl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Отправитель и получатель
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('barnawa80@gmail.com', 'Автозапчасти Боба Марли');
        $mail->addAddress($email);

        // Тема письма
        $mail->Subject = "🚗 Ваш заказ #$orderNumber готов к отправке!";

        // HTML-версия с тематическим дизайном
        $mail->isHTML(true);
        // Добавьте в начало письма этот блок с "лентой спецпредложений"
        $mail->Body .= '
<div style="background: #e53935; color: white; padding: 8px; text-align: center; font-size: 14px;">
🔧 <strong>АКЦИЯ:</strong> Следующий заказ со скидкой 10% по промокоду BOB10
</div>';
        $mail->Body = '
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ваш заказ #'.$orderNumber.'</title>
        </head>
        <body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5;">
            <!-- Контейнер письма -->
            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <!-- Шапка с автомобильной тематикой -->
                <div style="background: linear-gradient(to right, #1a3e72, #2a5298); padding: 25px; text-align: center; color: white;">
                    <h1 style="margin: 0; font-size: 28px;">🛠️ Автозапчасти Боба Марли</h1>
                    <p style="margin: 5px 0 0; font-size: 16px;">Качество и надежность для вашего авто</p>
                </div>
                
                <!-- Блок с номером заказа -->
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
                    <h3 style="color: #1a3e72; border-bottom: 2px solid #f7931e; padding-bottom: 5px;">🛒 Состав заказа</h3>
                    
                    <!-- Товар 1 -->
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #ddd;">
                        <div>
                            <strong>Шины</strong>
                            <p style="margin: 5px 0; color: #666;">Код: T-'.$orderNumber.'-1</p>
                        </div>
                        <div style="text-align: right;">
                            <p style="margin: 0;">'.$tireqty.' шт. × $'.TIREPRICE.'</p>
                            <p style="margin: 5px 0; font-weight: bold;">$'.number_format($tireqty * TIREPRICE, 2).'</p>
                        </div>
                    </div>
                    
                    <!-- Товар 2 -->
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #ddd;">
                        <div>
                            <strong>Моторное масло</strong>
                            <p style="margin: 5px 0; color: #666;">Код: O-'.$orderNumber.'-2</p>
                        </div>
                        <div style="text-align: right;">
                            <p style="margin: 0;">'.$oilqty.' шт. × $'.OILPRICE.'</p>
                            <p style="margin: 5px 0; font-weight: bold;">$'.number_format($oilqty * OILPRICE, 2).'</p>
                        </div>
                    </div>
                    
                    <!-- Товар 3 -->
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                        <div>
                            <strong>Свечи зажигания</strong>
                            <p style="margin: 5px 0; color: #666;">Код: S-'.$orderNumber.'-3</p>
                        </div>
                        <div style="text-align: right;">
                            <p style="margin: 0;">'.$sparkqty.' шт. × $'.SPARKPRICE.'</p>
                            <p style="margin: 5px 0; font-weight: bold;">$'.number_format($sparkqty * SPARKPRICE, 2).'</p>
                        </div>
                    </div>';

        // Блок скидки (если есть)
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
                        <p style="margin: 5px 0;">⏰ Пн-Пт: 9:00-19:00, Сб-Вс: 10:00-17:00</p>
                    </div>
                </div>';
                $mail->Body.='
                <!-- Подвал -->
                <div style="background: #333; color: #aaa; padding: 15px; text-align: center; font-size: 12px;">
                    <p style="margin: 5px 0;">© '.date('Y').' Автозапчасти Боба Марли. Все права защищены.</p>
                    <p style="margin: 5px 0;">Это письмо отправлено автоматически, пожалуйста, не отвечайте на него.</p>
                </div>
            </div>
        </body>
        </html>';

        // Текстовая версия для почтовых клиентов
        $mail->AltBody = "АВТОЗАПЧАСТИ БОБА МАРЛИ\n\n"
            . "Ваш заказ #$orderNumber\n"
            . "Дата: " . date("H:i, d-") . $months[$month] . date("-Y") . "\n\n"
            . "СОСТАВ ЗАКАЗА:\n"
            . "Шины: $tireqty шт. × $".TIREPRICE." = $".number_format($tireqty * TIREPRICE, 2)."\n"
            . "Масло: $oilqty шт. × $".OILPRICE." = $".number_format($oilqty * OILPRICE, 2)."\n"
            . "Свечи: $sparkqty шт. × $".SPARKPRICE." = $".number_format($sparkqty * SPARKPRICE, 2)."\n"
            . ($discount_amount > 0 ? "Скидка: -$".number_format($discount_amount, 2)."\n" : "")
            . "Промежуточный итог: $".number_format($subtotal, 2)."\n"
            . "Налог (10%): $".number_format($tax, 2)."\n"
            . "Сумма к оплате: $".number_format($totalamount, 2)."\n\n"
            . "ДОСТАВКА:\n"
            . "Адрес: $address\n"
            . "Дата: ".(!empty($delivery_date) ? $delivery_date : 'не указана')."\n"
            . "Время: ".(!empty($delivery_time) ? $delivery_time : 'не указано')."\n"
            . "Телефон: $phone\n\n"
            . "Спасибо за покупку!\n\n"
            . "Контакты магазина:\n"
            . "Телефон: +7 (123) 456-78-90\n"
            . "Адрес: г. Москва, ул. Автозапчастей, 42\n"
            . "Часы работы: Пн-Пт 9:00-19:00, Сб-Вс 10:00-17:00";

        // Отправка письма
        $mail->send();
        echo "<p style='color: green; padding: 10px; background: #e8f5e9;'>Письмо с подтверждением успешно отправлено!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red; padding: 10px; background: #ffebee;'>Ошибка отправки: {$mail->ErrorInfo}</p>";
    }
}

/* ===== ВЫВОД СПОСОБА ПРИВЛЕЧЕНИЯ КЛИЕНТА ===== */
if ($find == "a") {
    echo "<p class='highlight'>'Вы постоянный клиент.!!!'</p>";
} elseif ($find == "b") {
    echo "<p class='highlight'>'Привлечение клиентов по телерекламе'</p>";
} elseif ($find == "c") {
    echo "<p class='highlight'>'Ссылка на клиента по телефонному справочнику'</p>";
} elseif ($find == "d") {
    echo "<p class='highlight'>'Обращение к клиенту из уст в уста'</p>";
}
} else {
    // Если адрес не указан
    echo "<p class='highlight'>'Адрес доставки не указан. Данные не будут сохранены'.</p>";
}
}
?>

</body>
<footer>
    <?php
    include("time.php"); // Подключение внешнего файла с временем
    ?>
</footer>
</html>