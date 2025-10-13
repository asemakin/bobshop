
<?php
/**
 * Управление категориями - Bob Marley Auto Parts
 * Добавление, просмотр и удаление категорий товаров
 */

// Подключаем конфигурацию и функции
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Проверка авторизации (пока просто заглушка)
// $isAdmin = true;
// if (!$isAdmin) {
//    header('Location: ../index.php');
//    exit;
//    }

// Переменные для сообщений
$success = '';
$error = '';

// ОБРАБОТКА ДОБАВЛЕНИЯ НОВОЙ КАТЕГОРИИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    // Получаем данные из формы
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Проверяем что название не пустое
    if (!empty($name)) {
        try {
            // Подготавливаем SQL запрос для добавления категории
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            // Выполняем запрос с параметрами
            $stmt->execute([$name, $description]);

            // Сообщение об успехе
            $success = "✅ Категория '{$name}' успешно добавлена!";

        } catch (PDOException $e) {
            // Если произошла ошибка (например, категория с таким именем уже существует)
            $error = "❌ Ошибка при добавлении категории: " . $e->getMessage();
        }
    } else {
        $error = "❌ Название категории не может быть пустым";
    }
}

// ОБРАБОТКА УДАЛЕНИЯ КАТЕГОРИИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $categoryId = intval($_POST['category_id'] ?? 0);

    if ($categoryId > 0) {
        try {
            // Сначала проверяем есть ли товары в этой категории
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE categoryId = ?");
            $stmt->execute([$categoryId]);
            $productsCount = $stmt->fetchColumn();

            if ($productsCount > 0) {
                $error = "❌ Нельзя удалить категорию, в которой есть товары!";
            } else {
                // Если товаров нет - удаляем категорию
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                $success = "✅ Категория успешно удалена!";
            }

        } catch (PDOException $e) {
            $error = "❌ Ошибка при удалении категории: " . $e->getMessage();
        }
    }
}

// ПОЛУЧАЕМ ВСЕ КАТЕГОРИИ ИЗ БАЗЫ ДАННЫХ
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление категориями - Bob Marley Auto Parts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .category-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .products-count {
            background: #f9a602;
            color: #1a4721;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
<!-- ПОДКЛЮЧАЕМ ШАПКУ САЙТА -->
<?php include '../includes/header.php'; ?>

<main class="mainContent">
    <div class="container">
        <h1 style="color: #1a4721; text-align: center; margin-bottom: 2rem;">
            📂 Управление категориями
        </h1>

        <!-- БЛОК С СООБЩЕНИЯМИ ОБ УСПЕХЕ ИЛИ ОШИБКЕ -->
        <?php if ($success): ?>
            <div style="background: #27ae60; color: white; padding: 1rem; border-radius: 5px; margin-bottom: 2rem; text-align: center;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #e74c3c; color: white; padding: 1rem; border-radius: 5px; margin-bottom: 2rem; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- ФОРМА ДЛЯ ДОБАВЛЕНИЯ НОВОЙ КАТЕГОРИИ -->
        <div class="category-card">
            <h2 style="color: #1a4721; margin-bottom: 1rem;">➕ Добавить новую категорию</h2>

            <form method="POST">
                <div style="display: grid; gap: 1.5rem;">
                    <!-- ПОЛЕ ДЛЯ НАЗВАНИЯ КАТЕГОРИИ -->
                    <div>
                        <label class="formLabel">Название категории *</label>
                        <input type="text"
                               name="name"
                               class="formControl"
                               required
                               placeholder="Например: Электрика, Двигатель, Тормоза"
                               maxlength="100">
                        <small style="color: #666;">Максимум 100 символов</small>
                    </div>

                    <!-- ПОЛЕ ДЛЯ ОПИСАНИЯ КАТЕГОРИИ -->
                    <div>
                        <label class="formLabel">Описание категории</label>
                        <textarea name="description"
                                  class="formControl"
                                  rows="3"
                                  placeholder="Краткое описание категории..."></textarea>
                    </div>

                    <!-- КНОПКА ДОБАВЛЕНИЯ -->
                    <button type="submit" name="add_category" class="btn btnSuccess">
                        ✅ Добавить категорию
                    </button>
                </div>
            </form>
        </div>

        <!-- СПИСОК СУЩЕСТВУЮЩИХ КАТЕГОРИЙ -->
        <div style="background: white; padding: 2rem; border-radius: 10px;">
            <h2 style="color: #1a4721; margin-bottom: 1rem;">📋 Список категорий</h2>

            <!-- ЕСЛИ КАТЕГОРИЙ НЕТ - ПОКАЗЫВАЕМ СООБЩЕНИЕ -->
            <?php if (empty($categories)): ?>
                <div style="text-align: center; padding: 2rem;">
                    <h3 style="color: #666;">Категорий пока нет</h3>
                    <p>Добавьте первую категорию используя форму выше</p>
                </div>
            <?php else: ?>
                <!-- ЕСЛИ КАТЕГОРИИ ЕСТЬ - ВЫВОДИМ ИХ СПИСКОМ -->
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($categories as $category): ?>
                        <?php
                        // СЧИТАЕМ СКОЛЬКО ТОВАРОВ В КАЖДОЙ КАТЕГОРИИ
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE categoryId = ?");
                        $stmt->execute([$category['id']]);
                        $productsCount = $stmt->fetchColumn();
                        ?>

                        <div class="category-card">
                            <div class="category-header">
                                <div>
                                    <!-- НАЗВАНИЕ КАТЕГОРИИ -->
                                    <h3 style="color: #1a4721; margin: 0;">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </h3>

                                    <!-- ОПИСАНИЕ КАТЕГОРИИ (ЕСЛИ ЕСТЬ) -->
                                    <?php if (!empty($category['description'])): ?>
                                        <p style="color: #666; margin: 0.5rem 0 0 0;">
                                            <?php echo htmlspecialchars($category['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <!-- СЧЕТЧИК ТОВАРОВ В КАТЕГОРИИ -->
                                    <span class="products-count">
                                            🛍️ <?php echo $productsCount; ?> товаров
                                        </span>

                                    <!-- КНОПКА УДАЛЕНИЯ КАТЕГОРИИ -->
                                    <?php if ($productsCount == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit"
                                                    name="delete_category"
                                                    class="btn btnDanger"
                                                    onclick="return confirm('❌ Удалить категорию \"<?php echo addslashes($category['name']); ?>\"?')">
                                            🗑️ Удалить
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.9rem;">
                                                ❌ Нельзя удалить
                                            </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ -->
                            <div style="display: flex; gap: 2rem; color: #888; font-size: 0.9rem;">
                                <span>🆔 ID: <?php echo $category['id']; ?></span>
                                <span>📅 Создана: <?php echo date('d.m.Y', strtotime($category['createdAt'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ИНФОРМАЦИОННЫЙ БЛОК -->
        <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; margin-top: 2rem;">
            <h3 style="color: #1a4721; margin-bottom: 1rem;">💡 Подсказки</h3>
            <ul style="color: #666; line-height: 1.6;">
                <li>Категория не может быть удалена если в ней есть товары</li>
                <li>Сначала переместите товары в другие категории или удалите их</li>
                <li>Название категории должно быть уникальным</li>
                <li>Категории помогают покупателям быстрее находить нужные товары</li>
            </ul>
        </div>
    </div>
</main>

<!-- ПОДКЛЮЧАЕМ ПОДВАЛ САЙТА -->
<?php include '../includes/footer.php'; ?>
</body>
</html>