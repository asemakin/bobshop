<?php
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    header('Content-Type: application/json');

    $id = (int)$_POST['product_id'];
    $field = $db->real_escape_string($_POST['field']);
    $value = $field === 'price' ? (float)$_POST['value'] : $db->real_escape_string($_POST['value']);

    if ($field === 'productName') {
        $stmt = $db->prepare("UPDATE warehouse SET productName = ? WHERE orderId = ?");
        $stmt->bind_param("si", $value, $id);
    }
    elseif ($field === 'quantity') {
        $value = (int)$value;
        $stmt = $db->prepare("UPDATE warehouse SET quantity = ? WHERE orderId = ?");
        $stmt->bind_param("ii", $value, $id);
    }
    elseif ($field === 'price') {
        $stmt = $db->prepare("UPDATE warehouse SET price = ? WHERE orderId = ?");
        $stmt->bind_param("di", $value, $id);
    }

    if (isset($stmt)) {
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $db->real_escape_string($_POST['name']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $stmt = $db->prepare("INSERT INTO warehouse (productName, quantity, price) VALUES (?, ?, ?)");
    $stmt->bind_param("sid", $name, $quantity, $price);

    if (!$stmt->execute()) {
        die("Ошибка при добавлении товара: " . $stmt->error);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];

    $stmt = $db->prepare("DELETE FROM warehouse WHERE orderId = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("Ошибка при удалении товара: " . $stmt->error);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

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
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .editable {
                cursor: pointer;
                transition: background-color 0.3s;
            }
            .editable:hover {
                background-color: #f0f0f0;
            }
            .editing {
                background-color: #fffacd;
            }
            .edit-input {
                width: 100%;
                padding: 5px;
                box-sizing: border-box;
            }
            form {
                margin-bottom: 20px;
                background: #f5f5f5;
                padding: 15px;
                border-radius: 5px;
            }
            input, button {
                padding: 8px;
                margin-right: 10px;
            }
            button {
                cursor: pointer;
            }
            .delete-btn {
                color: red;
                text-decoration: none;
            }
            .delete-btn:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
    <h2>Управление складом</h2>

    <form method="POST">
        <input type="text" name="name" placeholder="Название товара" required>
        <input type="number" name="quantity" placeholder="Количество" min="0" required>
        <input type="number" name="price" placeholder="Цена" step="0.01" min="0" required>
        <button type="submit" name="add_product">Добавить товар</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>Товар</th>
            <th>Количество</th>
            <th>Цена</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php while($product = $products->fetch_assoc()): ?>
            <tr data-id="<?= $product['orderId'] ?>">
                <td class="editable" data-field="productName"><?= htmlspecialchars($product['productName']) ?></td>
                <td class="editable" data-field="quantity"><?= htmlspecialchars($product['quantity']) ?></td>
                <td class="editable" data-field="price">$ <?= htmlspecialchars(number_format($product['price'], 2)) ?></td>
                <td>
                    <a href="?delete_id=<?= $product['orderId'] ?>" class="delete-btn"
                       onclick="return confirm('Удалить этот товар?')">
                        Удалить
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.editable').forEach(cell => {
                cell.addEventListener('click', function() {
                    if (this.classList.contains('editing')) return;

                    const field = this.dataset.field;
                    const productId = this.closest('tr').dataset.id;
                    let originalValue = this.textContent.trim();

                    if (field === 'price') {
                        originalValue = originalValue.replace('$', '').trim();
                    }

                    const inputType = field === 'quantity' ? 'number' : 'text';
                    const step = field === 'price' ? '0.01' : '1';
                    this.innerHTML = `<input type="${inputType}"
                                        value="${originalValue}"
                                        step="${step}"
                                        class="edit-input">`;
                    this.classList.add('editing');

                    const input = this.querySelector('.edit-input');
                    input.focus();

                    const saveEdit = () => {
                        const newValue = input.value.trim();
                        if (newValue !== originalValue) {
                            fetch(window.location.href, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `update_product=1&product_id=${productId}&field=${field}&value=${encodeURIComponent(newValue)}`
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        let displayValue = newValue;
                                        if (field === 'price') {
                                            displayValue = '$ ' + parseFloat(newValue).toFixed(2);
                                        }
                                        this.textContent = displayValue;
                                    } else {
                                        alert(data.message || 'Ошибка при обновлении');
                                        this.textContent = field === 'price' ? '$ ' + parseFloat(originalValue).toFixed(2) : originalValue;
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    this.textContent = field === 'price' ? '$ ' + parseFloat(originalValue).toFixed(2) : originalValue;
                                });
                        } else {
                            this.textContent = field === 'price' ? '$ ' + parseFloat(originalValue).toFixed(2) : originalValue;
                        }
                        this.classList.remove('editing');
                    };

                    input.addEventListener('blur', saveEdit);
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            saveEdit();
                        } else if (e.key === 'Escape') {
                            this.textContent = field === 'price' ? '$ ' + parseFloat(originalValue).toFixed(2) : originalValue;
                            this.classList.remove('editing');
                        }
                    });
                });
            });
        });
    </script>
    </body>
    </html>
<?php
$db->close();
?>