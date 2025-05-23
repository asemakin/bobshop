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
    // Параметры: хост, имя пользователя, пароль, название базы данных
    $db = new mysqli('localhost', 'root', '', 'bob_auto_parts');

    // Проверка соединения с БД
    if ($db->connect_error) {
die("Ошибка подключения: " . $db->connect_error);
}

// Обработка формы добавления нового товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
// Защита от SQL-инъекций
$name = $db->real_escape_string($_POST['name']);
$quantity = (int)$_POST['quantity']; // Преобразуем в число
$price = (float)$_POST['price']; // Преобразуем в дробное число

// SQL-запрос для добавления товара
$db->query("INSERT INTO warehouse (product_name, quantity, price) VALUES ('$name', $quantity, $price)");
}

// Обработка удаления товара
if (isset($_GET['delete_id'])) {
$id = (int)$_GET['delete_id']; // Преобразуем ID в число

// SQL-запрос для удаления товара
$db->query("DELETE FROM warehouse WHERE id = $id");

// Перенаправляем на эту же страницу чтобы избежать повторной отправки формы
header("Location: ".$_SERVER['PHP_SELF']);
exit;
}

// Получаем все товары из базы данных
$products = $db->query("SELECT * FROM warehouse ORDER BY product_name");
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
        <td>Цена</td>
        <td>Действия</td>
    </tr>
    </thead>
    <tbody>
    <?php while($product = $products->fetch_assoc()): ?>
    <tr>
        <!-- Выводим название товара с защитой от XSS -->
        <td><?= htmlspecialchars($product['product_name']) ?></td>
        <!-- Выводим количество -->
        <td><?= $product['quantity'] ?></td>
        <!-- Выводим цену с форматированием (2 знака после запятой) -->
        <td><?= number_format($product['price'], 2) ?></td>
        <td>
            <!-- Ссылка для удаления товара с подтверждением -->
            <a href="?delete_id=<?= $product['id'] ?>"
               onclick="return confirm('Вы уверены, что хотите удалить этот товар?')">
                Удалить
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>