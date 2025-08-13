<?php
/*
 * НАСТРОЙКА ОТОБРАЖЕНИЯ ОШИБОК
 * Включаем вывод всех ошибок для удобства отладки
 */
ini_set('display_errors', 1);  // Показывать ошибки выполнения
ini_set('display_startup_errors', 1);  // Показывать ошибки инициализации
error_reporting(E_ALL);  // Отчет обо всех типах ошибок

/*
 * ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
 * Создаем новое подключение к MySQL серверу
 */
require_once __DIR__ . '/db_connect.php';

/*
 * ОБРАБОТКА AJAX-ЗАПРОСА НА ОБНОВЛЕНИЕ ТОВАРА
 * Проверяем, что запрос был отправлен методом POST и содержит параметр update_product
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    // Устанавливаем заголовок для ответа в формате JSON
    header('Content-Type: application/json');

    /*
     * ПОДГОТОВКА ДАННЫХ
     * Получаем и проверяем данные из запроса
     */
    $id = (int)$_POST['product_id'];  // Преобразуем ID товара в целое число
    $field = $db->real_escape_string($_POST['field']);  // Экранируем название поля

    // Для поля price преобразуем значение в число с плавающей точкой, для остальных - экранируем строку
    $value = $field === 'price' ? (float)$_POST['value'] : $db->real_escape_string($_POST['value']);

    /*
     * ФОРМИРОВАНИЕ SQL-ЗАПРОСА В ЗАВИСИМОСТИ ОТ ПОЛЯ
     * Для каждого типа поля (productName, quantity, price) готовим отдельный запрос
     */
    if ($field === 'productName') {
        // Для названия товара: строковый параметр (s) и целочисленный ID (i)
        $stmt = $db->prepare("UPDATE warehouse SET productName = ? WHERE orderId = ?");
        $stmt->bind_param("si", $value, $id);
    }
    elseif ($field === 'quantity') {
        // Для количества: преобразуем в целое число и используем два целочисленных параметра (ii)
        $value = (int)$value;
        $stmt = $db->prepare("UPDATE warehouse SET quantity = ? WHERE orderId = ?");
        $stmt->bind_param("ii", $value, $id);
    }
    elseif ($field === 'price') {
        // Для цены: число с плавающей точкой (d) и целочисленный ID (i)
        $stmt = $db->prepare("UPDATE warehouse SET price = ? WHERE orderId = ?");
        $stmt->bind_param("di", $value, $id);
    }

    /*
     * ВЫПОЛНЕНИЕ ЗАПРОСА И ФОРМИРОВАНИЕ ОТВЕТА
     */
    if (isset($stmt)) {
        if ($stmt->execute()) {
            // Если запрос выполнен успешно, возвращаем JSON с статусом success
            echo json_encode(['status' => 'success']);
        } else {
            // При ошибке возвращаем статус error и сообщение об ошибке
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();  // Всегда закрываем подготовленное выражение
    } else {
        // Если не удалось подготовить запрос (неизвестное поле)
        echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
    }
    exit;  // Завершаем выполнение скрипта после обработки AJAX-запроса
}

/*
 * ДОБАВЛЕНИЕ НОВОГО ТОВАРА
 * Обработка формы добавления товара
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Получаем и проверяем данные из формы
    $name = $db->real_escape_string($_POST['name']);  // Экранируем название
    $quantity = (int)$_POST['quantity'];  // Преобразуем количество в целое число
    $price = (float)$_POST['price'];  // Преобразуем цену в число с плавающей точкой

    // Подготавливаем SQL-запрос для вставки нового товара
    $stmt = $db->prepare("INSERT INTO warehouse (productName, quantity, price) VALUES (?, ?, ?)");
    // Привязываем параметры: строка, целое число, число с плавающей точкой
    $stmt->bind_param("sid", $name, $quantity, $price);

    // Выполняем запрос
    if (!$stmt->execute()) {
        die("Ошибка при добавлении товара: " . $stmt->error);  // В случае ошибки выводим сообщение
    }
    // После успешного добавления перенаправляем на эту же страницу
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;  // Завершаем выполнение
}

/*
 * УДАЛЕНИЕ ТОВАРА
 * Обработка запроса на удаление товара
 */
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];  // Преобразуем ID в целое число

    // Подготавливаем запрос на удаление
    $stmt = $db->prepare("DELETE FROM warehouse WHERE orderId = ?");
    $stmt->bind_param("i", $id);  // Привязываем целочисленный параметр

    // Выполняем запрос
    if (!$stmt->execute()) {
        die("Ошибка при удалении товара: " . $stmt->error);
    }
    // После удаления перенаправляем на эту же страницу
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/*
 * ПОЛУЧЕНИЕ СПИСКА ТОВАРОВ
 * Запрашиваем все товары из базы, отсортированные по названию
 */
$products = $db->query("SELECT * FROM warehouse ORDER BY productName");
if (!$products) {
    die("Ошибка при получении товаров: " . $db->error);
}
?>

    <!-- НАЧАЛО HTML-ДОКУМЕНТА -->
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Управление складом</title>

        <link rel="stylesheet" href="orderform.css">

        <style>
            /* ОСНОВНЫЕ СТИЛИ СТРАНИЦЫ */
            body {
                font-family: Arial, sans-serif;  // Шрифт для всей страницы
            margin: 20px;  // Отступы от краев
            }

            /* СТИЛИ ДЛЯ ТАБЛИЦЫ */
            table {
                width: 100%;  // Таблица на всю ширину
            border-collapse: collapse;  // Убираем двойные границы
            margin-top: 20px;  // Отступ сверху
            }

            /* СТИЛИ ДЛЯ ЯЧЕЕК ТАБЛИЦЫ */
            th, td {
                border: 1px solid #ddd;  // Границы ячеек
            padding: 8px;  // Внутренние отступы
            text-align: left;  // Выравнивание текста
            }

            /* СТИЛИ ДЛЯ ЗАГОЛОВКОВ ТАБЛИЦЫ */
            th {
                background-color: #f2f2f2;  // Фон заголовков
            }

            /* ЧЕРЕДОВАНИЕ ЦВЕТА СТРОК */
            tr:nth-child(even) {
                background-color: #f9f9f9;  // Фон четных строк
            }

            /* СТИЛИ ДЛЯ РЕДАКТИРУЕМЫХ ЯЧЕЕК */
            .editable {
                cursor: pointer;  // Курсор-указатель
            transition: background-color 0.3s;  // Плавное изменение фона
            }
            .editable:hover {
                background-color: #f0f0f0;  // Фон при наведении
            }
            .editing {
                background-color: #fffacd;  // Фон при редактировании
            }

            /* СТИЛИ ДЛЯ ПОЛЯ ВВОДА ПРИ РЕДАКТИРОВАНИИ */
            .edit-input {
                width: 100%;  // На всю ширину ячейки
            padding: 5px;  // Внутренние отступы
            box-sizing: border-box;  // Учет padding в ширине
            }

            /* СТИЛИ ДЛЯ ФОРМЫ ДОБАВЛЕНИЯ */
            form {
                margin-bottom: 20px;  // Отступ снизу
            background: #f5f5f5;  // Фон формы
            padding: 15px;  // Внутренние отступы
            border-radius: 5px;  // Закругленные углы
            }

            /* СТИЛИ ДЛЯ ПОЛЕЙ ВВОДА И КНОПОК */
            input, button {
                padding: 8px;  // Внутренние отступы
            margin-right: 10px;  // Отступ справа
            }
            button {
                cursor: pointer;  // Курсор-указатель
            }

            /* СТИЛИ ДЛЯ ССЫЛКИ УДАЛЕНИЯ */
            .delete-btn {
                color: red;  // Красный цвет
            text-decoration: none;  // Убираем подчеркивание
            }
            .delete-btn:hover {
                text-decoration: underline;  // Подчеркивание при наведении
            }
        </style>
    </head>
    <body>
    <!-- ЗАГОЛОВОК СТРАНИЦЫ -->
    <h2>Управление складом</h2>

    <!-- ФОРМА ДОБАВЛЕНИЯ НОВОГО ТОВАРА -->
    <form method="POST">
        <!-- Поле для названия товара -->
        <input type="text" name="name" placeholder="Название товара" required>

        <!-- Поле для количества (только положительные числа) -->
        <input type="number" name="quantity" placeholder="Количество" min="0" required>

        <!-- Поле для цены (с шагом 0.01) -->
        <input type="number" name="price" placeholder="Цена" step="0.01" min="0" required>

        <!-- Кнопка отправки формы -->
        <button type="submit" name="add_product">Добавить товар</button>
    </form>

    <!-- ТАБЛИЦА С ТОВАРАМИ -->
    <table>
        <thead>
        <tr>
            <!-- Заголовки столбцов -->
            <th>Товар</th>
            <th>Количество</th>
            <th>Цена</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <!-- ВЫВОД ТОВАРОВ ИЗ БАЗЫ ДАННЫХ -->
        <?php while($product = $products->fetch_assoc()): ?>
            <tr data-id="<?= $product['orderId'] ?>">  <!-- data-id содержит ID товара -->
                <!-- Ячейка с названием товара (редактируемая) -->
                <td class="editable" data-field="productName">
                    <?= htmlspecialchars($product['productName']) ?>
                </td>

                <!-- Ячейка с количеством (редактируемая) -->
                <td class="editable" data-field="quantity">
                    <?= htmlspecialchars($product['quantity']) ?>
                </td>

                <!-- Ячейка с ценой (редактируемая) -->
                <td class="editable" data-field="price">
                    $ <?= htmlspecialchars(number_format($product['price'], 2)) ?>
                </td>

                <!-- Ячейка с действиями (удаление) -->
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
        /*
         * JAVASCRIPT ДЛЯ РЕДАКТИРОВАНИЯ ТОВАРОВ
         * Обработка кликов по ячейкам таблицы
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Для каждой ячейки с классом editable
            document.querySelectorAll('.editable').forEach(cell => {
                cell.addEventListener('click', function() {
                    // Если уже в режиме редактирования - выходим
                    if (this.classList.contains('editing')) return;

                    // Получаем данные из атрибутов
                    const field = this.dataset.field;  // Какое поле редактируем
                    const productId = this.closest('tr').dataset.id;  // ID товара
                    let originalValue = this.textContent.trim();  // Текущее значение

                    // Для цены убираем символ доллара
                    if (field === 'price') {
                        originalValue = originalValue.replace('$', '').trim();
                    }

                    // Определяем тип поля ввода
                    const inputType = field === 'quantity' ? 'number' : 'text';
                    // Шаг для числовых полей
                    const step = field === 'price' ? '0.01' : '1';

                    // Заменяем содержимое ячейки на поле ввода
                    this.innerHTML = `<input type="${inputType}"
                                    value="${originalValue}"
                                    step="${step}"
                                    class="edit-input">`;
                    this.classList.add('editing');  // Добавляем класс редактирования

                    const input = this.querySelector('.edit-input');
                    input.focus();  // Устанавливаем фокус на поле ввода

                    // Функция сохранения изменений
                    const saveEdit = () => {
                        const newValue = input.value.trim();  // Получаем новое значение

                        // Если значение изменилось
                        if (newValue !== originalValue) {
                            // Отправляем AJAX-запрос на сервер
                            fetch(window.location.href, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `update_product=1&product_id=${productId}&field=${field}&value=${encodeURIComponent(newValue)}`
                            })
                                .then(response => response.json())  // Парсим JSON-ответ
                                .then(data => {
                                    if (data.status === 'success') {
                                        // Форматируем отображаемое значение
                                        let displayValue = newValue;
                                        if (field === 'price') {
                                            displayValue = '$ ' + parseFloat(newValue).toFixed(2);
                                        }
                                        this.textContent = displayValue;
                                    } else {
                                        // В случае ошибки показываем сообщение и восстанавливаем старое значение
                                        alert(data.message || 'Ошибка при обновлении');
                                        this.textContent = field === 'price' ? '$ ' + parseFloat(originalValue).toFixed(2) : originalValue;
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    this.textContent = field === 'price' ? '$ ' + parseFloat(originalValue).toFixed(2) : originalValue;
                                });
                        } else {
                            // Если значение не изменилось, просто восстанавливаем
                            this.textContent = field === 'price' ? '$ ' + parseFloat(originalValue).toFixed(2) : originalValue;
                        }
                        this.classList.remove('editing');  // Выходим из режима редактирования
                    };

                    // Сохраняем при потере фокуса
                    input.addEventListener('blur', saveEdit);

                    // Обработка клавиш
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            saveEdit();  // Enter - сохранить
                        } else if (e.key === 'Escape') {
                            // Escape - отменить редактирование
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
// ЗАКРЫТИЕ СОЕДИНЕНИЯ С БАЗОЙ ДАННЫХ
$db->close();
?>