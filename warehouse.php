
<?php
/*
 * Подключение к базе данных MySQL
 * Параметры: сервер, пользователь, пароль, имя базы данных
 * В случае ошибки подключения скрипт завершится с сообщением об ошибке
 */
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

/*
 * Обработка добавления нового товара
 * Проверяем, что запрос POST и есть параметр add_product
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Экранируем и проверяем входные данные
    $name = $db->real_escape_string($_POST['name']);
    $quantity = (int)$_POST['quantity']; // Приводим к целому числу
    $price = (float)$_POST['price']; // Приводим к числу с плавающей точкой

    // Подготавливаем SQL запрос с параметрами для безопасности
    $stmt = $db->prepare("INSERT INTO warehouse (productName, quantity, price) VALUES (?, ?, ?)");
    $stmt->bind_param("sid", $name, $quantity, $price); // s - строка, i - целое, d - дробное

    if (!$stmt->execute()) {
        echo "Ошибка при добавлении товара: " . $stmt->error;
    } else {
        // Перенаправляем чтобы избежать повторной отправки формы
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    $stmt->close();
}

/*
 * Обработка удаления товара
 * Проверяем наличие параметра delete_id в URL
 */
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id']; // Приводим ID к целому числу

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

/*
 * Обработка AJAX-запроса на обновление товара
 * Используется для редактирования прямо в таблице
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = (int)$_POST['product_id'];
    $field = $db->real_escape_string($_POST['field']);

    // Для цены преобразуем в float, для других полей - экранируем строку
    $value = $field === 'price' ? (float)$_POST['value'] : $db->real_escape_string($_POST['value']);

    // В зависимости от поля формируем разные запросы
    if ($field === 'productName') {
        $stmt = $db->prepare("UPDATE warehouse SET productName = ? WHERE productID = ?");
        $stmt->bind_param("si", $value, $id);
    }
    elseif ($field === 'quantity') {
        $value = (int)$value; // Дополнительная проверка для количества
        $stmt = $db->prepare("UPDATE warehouse SET quantity = ? WHERE productID = ?");
        $stmt->bind_param("ii", $value, $id);
    }
    elseif ($field === 'price') {
        $stmt = $db->prepare("UPDATE warehouse SET price = ? WHERE productID = ?");
        $stmt->bind_param("di", $value, $id); // d - для дробных чисел
    }

    if (isset($stmt)) {
        if ($stmt->execute()) {
            echo "Успешно обновлено"; // Ответ для AJAX
        } else {
            echo "Ошибка при обновлении товара: " . $stmt->error;
        }
        $stmt->close();
    }

    exit; // Завершаем выполнение для AJAX-запроса
}

// Получаем все товары из базы данных для отображения в таблице
$products = $db->query("SELECT * FROM warehouse ORDER BY productName");
if (!$products) {
    die("Ошибка при получении товаров: " . $db->error);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление складом</title>
    <!-- Подключаем внешние стили -->
    <link rel="stylesheet" href="warehouse.css">
    <?php include("time.php");?>
</head>
<body>
        <h2 style="font-family: cursive; font-size: 30px; color: black;">
            Управление складом
        </h2>

<!-- Форма для добавления новых товаров -->
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
        <th style="font-family: cursive; font-size: 20px; color: white;"> Товар </th>
        <th style="font-family: cursive; font-size: 20px; color: white;"> Количество </th>
        <th style="font-family: cursive; font-size: 20px; color: white;"> Цена </th>
        <th style="font-family: cursive; font-size: 20px; color: white;"> Действия </th>
    </tr>
    </thead>
    <tbody>
    <?php while($product = $products->fetch_assoc()): ?>
        <!-- Каждая строка содержит data-id с ID товара -->
        <tr data-id="<?= $product['productID'] ?>">
            <!-- Ячейки с классом editable можно редактировать -->
            <td class="editable" data-field="productName"><?= htmlspecialchars($product['productName']) ?></td>
            <td class="editable" data-field="quantity"><?= htmlspecialchars($product['quantity']) ?></td>
            <td class="editable" data-field="price"><?= htmlspecialchars(number_format($product['price'], 2)) ?> $</td>
            <td>
                <!-- Ссылка для удаления товара с подтверждением -->
                <a href="?delete_id=<?= $product['productID'] ?>"
                   onclick="return confirm('Вы уверены, что хотите удалить этот товар?')">
                    Удалить
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
         <!-- В конце body, перед закрывающим тегом </body> добавьте: -->
         <script src="edit.js"></script>

</body>
</html>
<?php
// Закрываем соединение с базой данных
$db->close();
?>