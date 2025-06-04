<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление складом</title>
    <link rel="stylesheet" href="orderform.css">
</head>
<body>
<?php
// Подключение к базе данных MySQL
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');

if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

// Обработка формы добавления нового товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $db->real_escape_string($_POST['name']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $stmt = $db->prepare("INSERT INTO warehouse (productName, quantity, price) VALUES (?, ?, ?)");
    $stmt->bind_param("sid", $name, $quantity, $price);

    if (!$stmt->execute()) {
        echo "Ошибка при добавлении товара: " . $stmt->error;
    } else {
        // Перенаправляем после POST чтобы избежать повторной отправки
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    $stmt->close();
}

// Обработка удаления товара
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];

    $stmt = $db->prepare("DELETE FROM warehouse WHERE productID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Ошибка при удалении товара: " . $stmt->error;
    }
    $stmt->close();
}

// Получаем все товары из базы данных
$products = $db->query("SELECT * FROM warehouse ORDER BY productName");
if (!$products) {
    die("Ошибка при получении товаров: " . $db->error);
}
?>

<h2>Управление складом</h2>

<!-- Форма для добавления нового товара -->
<form method="POST">
    <input type="text" name="name" placeholder="Название товара" required>
    <input type="number" name="quantity" placeholder="Количество" min="0" required>
    <input type="number" name="price" placeholder="Цена" step="0.01" min="0" required>
    <button type="submit" name="add_product">Добавить товар</button>
</form>

<!-- Таблица с товарами -->
<table>
    <thead>
    <tr>
        <td>Товар</td>
        <td>Количество</td>
        <td>Цена </td>
        <td>Действия</td>
    </tr>
    </thead>
    <tbody>
    <?php while($product = $products->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($product['productName']) ?></td>
            <td><?= htmlspecialchars($product['quantity']) ?></td>
            <td><?= htmlspecialchars(number_format($product['price'], 2)) ?>  $</td>
            <td>
                <a href="?delete_id=<?= $product['productID'] ?>"
                   onclick="return confirm('Вы уверены, что хотите удалить этот товар?')">
                    Удалить
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php
// Закрываем соединение
$db->close();
?>
</body>
</html>
