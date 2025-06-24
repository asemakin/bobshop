<?php
// Настройки отображения ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к БД с обработкой ошибок
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

// Проверка метода запроса
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Требуется POST-запрос");
}

// Получение и очистка данных формы
$customerData = [
    'address' => trim($_POST['address'] ?? ''),
    'phone' => trim($_POST['tel'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'deliveryDate' => trim($_POST['deliveryDate'] ?? ''),
    'deliveryTime' => trim($_POST['deliveryTime'] ?? ''),
    'source' => trim($_POST['find'] ?? '')
];

// Валидация обязательных полей
if (empty($customerData['address']) || empty($customerData['phone'])) {
    die("Заполните адрес и телефон");
}

// Получение товаров со склада
$products = $db->query("SELECT * FROM warehouse");
if (!$products) {
    die("Ошибка получения товаров: " . $db->error);
}

// Формирование списка заказанных товаров
$orderItems = [];
$total = 0;

while($product = $products->fetch_assoc()) {
    $qtyField = 'product_' . $product['orderId'];
    $quantity = (int)($_POST[$qtyField] ?? 0);

    if ($quantity > 0) {
        $price = (float)$product['price'];
        $sum = $quantity * $price;

        $orderItems[] = [
            'name' => $product['productName'],
            'qty' => $quantity,
            'price' => $price,
            'sum' => $sum,
            'productId' => $product['orderId']
        ];

        $total += $sum;
    }
}

if (empty($orderItems)) {
    die("Не выбрано ни одного товара");
}

// Расчет налогов и итоговой суммы
$taxRate = 0.10;
$tax = $total * $taxRate;
$totalWithTax = $total + $tax;
$discount = 0; // Скидка по умолчанию
$referralSourceId = 1; // Источник заказа по умолчанию

// Сохранение заказа в БД
$orderDate = date("Y-m-d H:i:s");

// Используем обратные кавычки для order (зарезервированное слово)
$stmt = $db->prepare("INSERT INTO `order` (
    id, orderDate, subTotal, discount, tax, totalAmount, 
    deliveryAddress, deliveryDate, deliveryTime,
    customerPhone, customerEmail, referralSourceId
) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("Ошибка подготовки запроса: " . $db->error);
}

// Привязка параметров для основного заказа
$stmt->bind_param("sddddsssssi",
    $orderDate,
    $total,
    $discount,
    $tax,
    $totalWithTax,
    $customerData['address'],
    $customerData['deliveryDate'],
    $customerData['deliveryTime'],
    $customerData['phone'],
    $customerData['email'],
    $referralSourceId
);

if (!$stmt->execute()) {
    die("Ошибка сохранения заказа: " . $stmt->error);
}

$orderId = $stmt->insert_id;
$stmt->close();

// Сохранение позиций заказа
foreach ($orderItems as $item) {
    // Исправлено: 6 полей = 6 значений (NULL + 5 параметров)
    $stmt = $db->prepare("INSERT INTO `orderItem` (
        itemId, orderNumber, productId, productName, quantity, price
    ) VALUES (NULL, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Ошибка подготовки запроса товара: " . $db->error);
    }

    // Исправлено: передаем productId из массива товаров
    $stmt->bind_param("iisid",
        $orderId,
        $item['productId'],
        $item['name'],
        $item['qty'],
        $item['price']
    );

    if (!$stmt->execute()) {
        die("Ошибка сохранения товара: " . $stmt->error);
    }
    $stmt->close();

    // Обновление остатков на складе
    $updateStmt = $db->prepare("UPDATE warehouse SET quantity = quantity - ? WHERE orderId = ?");
    $updateStmt->bind_param("ii", $item['qty'], $item['productId']);
    $updateStmt->execute();
    $updateStmt->close();
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение заказа</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #2c3e50; }
        .order-info { background: #f9f9f9; padding: 15px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #4a6fa5; color: white; }
        .total-row { font-weight: bold; background-color: #f2f2f2; }
        .print-btn { display: inline-block; padding: 8px 15px; background: #4a6fa5; color: white;
            text-decoration: none; border-radius: 4px; margin-top: 15px; }
    </style>
</head>
<body>
<!-- HTML-разметка остается без изменений -->
<h1>Автозапчасти Боба Марли</h1>
<h2>Ваш заказ успешно оформлен</h2>

<div class="order-info">
    <p><strong>Номер заказа:</strong> <?= $orderId ?></p>
    <p><strong>Дата и время заказа:</strong> <?= date("d.m.Y H:i", strtotime($orderDate)) ?></p>
</div>

<!-- ... остальная HTML-разметка без изменений ... -->

<?php include("time.php"); ?>
</body>
</html>