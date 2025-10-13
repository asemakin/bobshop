<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

// Получаем все товары для каталога
$productsRes = $db->query("SELECT orderId, productName, price, quantity FROM warehouse ORDER BY productName");
$products = [];
while($row = $productsRes->fetch_assoc()) {
    $products[] = $row;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог автозапчастей Боба Марли</title>
    <link rel="stylesheet" href="catalog.css">
    <script src="/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5dc;
            color: #333;
            margin: 0;
        }
        h1 { text-align: center; font-family: cursive; color: #2e8b57; margin: 20px 0; }
        .catalog { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; padding: 20px; }
        .productCard {
            background: #fff8dc;
            border: 2px solid #2e8b57;
            border-radius: 10px;
            width: 220px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .productCard:hover { transform: translateY(-5px); }
        .productCard img { max-width: 100%; height: auto; border-radius: 5px; }
        .productCard h3 { font-size: 18px; color: #000; margin: 10px 0; }
        .productCard p { margin: 5px 0; }
        .addBtn { background-color: #4a6fa5; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 5px; font-weight: bold; }
        .addBtn:hover { background-color: #3a5a8f; }
        .cart { position: fixed; top: 20px; right: 20px; width: 300px; background: #fffacd; border: 2px solid #2e8b57; padding: 15px; border-radius: 10px; }
        .cart h2 { text-align: center; margin-top: 0; }
        .cartItem { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .checkoutBtn { background-color: #d9534f; color: white; border: none; padding: 10px; width: 100%; cursor: pointer; font-weight: bold; border-radius: 5px; margin-top: 10px; }
        .checkoutBtn:hover { background-color: #c9302c; }
    </style>
</head>
<body>
<h1>Каталог автозапчастей Боба Марли</h1>

<div class="catalog">
    <?php foreach($products as $product): ?>
        <div class="productCard" data-id="<?= $product['orderId'] ?>" data-price="<?= $product['price'] ?>" data-name="<?= htmlspecialchars($product['productName']) ?>">
            <img src="images/<?= htmlspecialchars($product['productImage'] ?? 'default.png') ?>" alt="<?= htmlspecialchars($product['productName']) ?>">
            <h3><?= htmlspecialchars($product['productName']) ?></h3>
            <p>Цена: <?= number_format($product['price'], 2) ?> ₽</p>
            <p>В наличии: <?= $product['quantity'] ?> шт.</p>
            <input type="number" class="quantityInput" min="1" max="<?= $product['quantity'] ?>" value="1" style="width:50px;">
            <button class="addBtn">Добавить в корзину</button>
        </div>
    <?php endforeach; ?>
</div>

<div class="cart">
    <h2>Корзина</h2>
    <div id="cartItems"></div>
    <p><strong>Итого: </strong><span id="cartTotal">0</span> ₽</p>
    <button class="checkoutBtn" onclick="proceedCheckout()">Оформить заказ</button>
</div>

<script>
    let cart = {};

    function updateCartDisplay() {
        const cartItemsDiv = $('#cartItems');
        cartItemsDiv.empty();
        let total = 0;
        for (const id in cart) {
            const item = cart[id];
            total += item.price * item.qty;
            cartItemsDiv.append(`<div class="cartItem">${item.name} x ${item.qty} = ${item.price*item.qty} ₽ <button onclick="removeItem(${id})">❌</button></div>`);
        }
        $('#cartTotal').text(total.toFixed(2));
    }

    function removeItem(id) {
        delete cart[id];
        updateCartDisplay();
    }

    $('.addBtn').click(function() {
        const card = $(this).closest('.productCard');
        const id = card.data('id');
        const name = card.data('name');
        const price = parseFloat(card.data('price'));
        const qty = parseInt(card.find('.quantityInput').val());

        if(cart[id]) {
            cart[id].qty += qty;
        } else {
            cart[id] = {name:name, price:price, qty:qty};
        }
        updateCartDisplay();
    });

    function proceedCheckout() {
        if(Object.keys(cart).length === 0) {
            alert('Корзина пуста!');
            return;
        }

        const form = $('<form action="orderConfirm.php" method="post"></form>');
        for(const id in cart) {
            form.append(`<input type="hidden" name="product_${id}" value="${cart[id].qty}">`);
        }
        $('body').append(form);
        form.submit();
    }
</script>
</body>
</html>
