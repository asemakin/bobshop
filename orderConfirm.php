<?php
// === Включаем отображение ошибок (для отладки) ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// === Подключение к базе данных ===
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

// === Проверяем метод запроса ===
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Требуется POST-запрос");
}

// === Проверка выбора источника информации ===
if (empty($_POST['find'])) {
    die("Пожалуйста, выберите вариант!");
}

$selectedOption = $_POST['find'] ?? '';
$options = [
    'a' => 'Вы постоянный клиент !!!',
    'b' => 'Вы узнали о нас из ТВ рекламы !!!',
    'c' => 'Вы узнали о нас из телефонного справочника !!!',
    'd' => 'Вы узнали о нас от друзей и близких !!!'
];
$referralSourceText = $options[$selectedOption] ?? 'Неизвестно';

// === Данные клиента ===
$customerData = [
    'address' => trim($_POST['address'] ?? ''),
    'phone' => trim($_POST['tel'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'deliveryDate' => trim($_POST['deliveryDate'] ?? ''),
    'deliveryTime' => trim($_POST['deliveryTime'] ?? '')
];

if (empty($customerData['address']) || empty($customerData['phone'])) {
    die("Заполните адрес и телефон");
}

// === Получаем список товаров ===
$products = $db->query("SELECT * FROM warehouse");
if (!$products) {
    die("Ошибка получения товаров: " . $db->error);
}

$orderItems = [];
$total = 0;

while ($product = $products->fetch_assoc()) {
    $qtyField = 'product_' . $product['orderId'];
    $quantity = (int)($_POST[$qtyField] ?? 0);

    if ($quantity > 0) {
        if ($quantity > $product['quantity']) {
            die("Недостаточно товара: " . htmlspecialchars($product['productName']));
        }

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

// === Расчёт итогов ===
// === ЛОГИКА СКИДКИ ===

// === Получаем скидку из таблицы настроек ===
$settings = [];
$res = $db->query("SELECT settingKey, settingValue FROM `settings`");
while ($row = $res->fetch_assoc()) {
    $settings[$row['settingKey']] = $row['settingValue'];
}

$discountThreshold = (float)$settings['discountThreshold'];
$discountRate = (float)$settings['discountRate'];
$discount = 0.0;

if ($total >= $discountThreshold) {
    $discount = $discountRate;
}

$subTotal = $total;
$totalAfterDiscount = $subTotal * (1 - $discount);
$taxRate = 0.10;
$tax = $totalAfterDiscount * $taxRate;
$totalWithTax = $totalAfterDiscount + $tax;

$orderDate = date("Y-m-d H:i:s");

// === Сохраняем заказ ===
$stmt = $db->prepare("INSERT INTO `order` (
    orderDate, subTotal, discount, tax, totalAmount, 
    deliveryAddress, deliveryDate, deliveryTime,
    customerPhone, customerEmail, referralSourceId
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sddddssssss",
    $orderDate,
    $subTotal,
    $discount,
    $tax,
    $totalWithTax,
    $customerData['address'],
    $customerData['deliveryDate'],
    $customerData['deliveryTime'],
    $customerData['phone'],
    $customerData['email'],
    $referralSourceText
);
$stmt->execute();
$orderId = $stmt->insert_id;
$stmt->close();

// === Сохраняем товары заказа ===
foreach ($orderItems as $item) {
    $stmt = $db->prepare("INSERT INTO `orderItem` (
        orderNumber, productId, productName, quantity, price
    ) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisid",
        $orderId,
        $item['productId'],
        $item['name'],
        $item['qty'],
        $item['price']
    );
    $stmt->execute();
    $stmt->close();

    // Обновляем склад
    $updateStmt = $db->prepare("UPDATE warehouse SET quantity = quantity - ? WHERE orderId = ?");
    $updateStmt->bind_param("ii", $item['qty'], $item['productId']);
    $updateStmt->execute();
    $updateStmt->close();
}

$db->close();

// === Перенаправляем на страницу подтверждения ===
header("Location: orderform.php?orderId=" . $orderId);
exit;

