<?php
/**
 * Вспомогательные функции для магазина
 * Bob Marley Auto Parts - Функции
 */

/**
 * Форматирование цены
 */
function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' ₽';
}

/**
 * Получение товаров из базы данных (без параметров в LIMIT)
 */
function getProducts($categoryId = null, $limit = null) {
    global $pdo;

    $sql = "SELECT p.*, c.name as categoryName 
            FROM products p 
            LEFT JOIN categories c ON p.categoryId = c.id 
            WHERE 1=1";

    $params = [];

    if ($categoryId) {
        $sql .= " AND p.categoryId = ?";
        $params[] = $categoryId;
    }

    $sql .= " ORDER BY p.createdAt DESC";

    // LIMIT добавляем без параметров (как чистое число)
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Получение одного товара по ID
 */
function getProduct($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT p.*, c.name as categoryName 
                          FROM products p 
                          LEFT JOIN categories c ON p.categoryId = c.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

/**
 * Получение категорий
 */
function getCategories() {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Получение товаров по категории (альтернативный метод)
 */
function getProductsByCategory($categoryId) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT p.*, c.name as categoryName 
                          FROM products p 
                          LEFT JOIN categories c ON p.categoryId = c.id 
                          WHERE p.categoryId = ? 
                          ORDER BY p.createdAt DESC");
    $stmt->execute([$categoryId]);

    return $stmt->fetchAll();
}

/**
 * Получение популярных товаров (фиксированное количество)
 */
function getPopularProducts($count = 6) {
    global $pdo;

    $sql = "SELECT p.*, c.name as categoryName 
            FROM products p 
            LEFT JOIN categories c ON p.categoryId = c.id 
            ORDER BY p.createdAt DESC 
            LIMIT " . (int)$count;

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Добавление товара в корзину
 */
function addToCart($productId, $quantity = 1) {
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Обновление количества товара в корзине
 */
function updateCartItem($productId, $quantity) {
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

/**
 * Удаление товара из корзины
 */
function removeFromCart($productId) {
    unset($_SESSION['cart'][$productId]);
}

/**
 * Получение содержимого корзины
 */
function getCart() {
    $cart = [];
    $total = 0;

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = getProduct($productId);
            if ($product) {
                $product['quantity'] = $quantity;
                $product['subtotal'] = $product['price'] * $quantity;
                $cart[] = $product;
                $total += $product['subtotal'];
            }
        }
    }

    return [
        'items' => $cart,
        'total' => $total
    ];
}

/**
 * Очистка корзины
 */
function clearCart() {
    $_SESSION['cart'] = [];
}

/**
 * Создание нового заказа
 */

function createOrder($customerData, $cart) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Вставляем заказ БЕЗ createdAt (используем DEFAULT значение)
        $stmt = $pdo->prepare("INSERT INTO orders 
            (customerName, email, phone, address, totalAmount, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')");

        $stmt->execute([
            $customerData['customerName'],
            $customerData['email'],
            $customerData['phone'] ?? '',
            $customerData['address'],
            $cart['total']
        ]);

        $orderId = $pdo->lastInsertId();

        // Вставляем элементы заказа БЕЗ createdAt
        $stmt = $pdo->prepare("INSERT INTO orderItems (orderId, productId, quantity, price) 
                              VALUES (?, ?, ?, ?)");

        foreach ($cart['items'] as $item) {
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['quantity'],
                $item['price']
            ]);
        }

        $pdo->commit();
        return $orderId;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Создание заказа для авторизованного пользователя
 */
function createUserOrder($userId, $customerData, $cart) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Вставляем заказ с userId БЕЗ createdAt
        $stmt = $pdo->prepare("INSERT INTO orders 
            (userId, customerName, email, phone, address, totalAmount, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')");

        $stmt->execute([
            $userId,
            $customerData['customerName'],
            $customerData['email'],
            $customerData['phone'] ?? '',
            $customerData['address'],
            $cart['total']
        ]);

        $orderId = $pdo->lastInsertId();

        // Вставляем элементы заказа БЕЗ createdAt
        $stmt = $pdo->prepare("INSERT INTO orderItems (orderId, productId, quantity, price) 
                              VALUES (?, ?, ?, ?)");

        foreach ($cart['items'] as $item) {
            $stmt->execute([
                $orderId,
                $item['id'],
                $item['quantity'],
                $item['price']
            ]);
        }

        $pdo->commit();
        return $orderId;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Поиск товаров
 */

function searchProducts($searchTerm) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT p.*, c.name as categoryName 
                          FROM products p 
                          LEFT JOIN categories c ON p.categoryId = c.id 
                          WHERE p.name LIKE ? OR p.description LIKE ? 
                          ORDER BY p.createdAt DESC");

    $searchPattern = '%' . $searchTerm . '%';
    $stmt->execute([$searchPattern, $searchPattern]);

    return $stmt->fetchAll();
}


/**
 * УМНАЯ ФУНКЦИЯ ДЛЯ АВТОМАТИЧЕСКОГО ПОДБОРА EMOJI
 * Анализирует название товара и подбирает подходящую иконку
 */

/**
 * Автоматически подбирает emoji на основе названия товара
 */
/**function getProductEmoji($productName, $categoryId = null)
{
    // Приводим название к нижнему регистру для поиска
    $name = mb_strtolower(trim($productName));

    // СЛОВАРЬ КЛЮЧЕВЫХ СЛОВ И СООТВЕТСТВУЮЩИХ EMOJI
    $keywordEmojis = [
        // ДВИГАТЕЛЬ И СМАЗОЧНЫЕ МАТЕРИАЛЫ
        'масло' => '🛢️', 'oil' => '🛢️', 'смазк' => '🛢️', 'lubricant' => '🛢️',
        'двигатель' => '⚙️', 'engine' => '⚙️', 'мотор' => '⚙️', 'motor' => '⚙️',
        'поршень' => '🔩', 'piston' => '🔩', 'цилиндр' => '🛠️', 'cylinder' => '🛠️',
        'коленвал' => '⚙️', 'crankshaft' => '⚙️', 'распредвал' => '⚙️', 'camshaft' => '⚙️',
        'фильтр' => '🧹', 'filter' => '🧹', 'air filter' => '🧹', 'oil filter' => '🛢️',
        'свеч' => '⚡', 'spark' => '⚡', 'зажиган' => '⚡', 'ignition' => '⚡',

        // ТОРМОЗНАЯ СИСТЕМА
        'тормоз' => '🛑', 'brake' => '🛑', 'стоп' => '🛑', 'stop' => '🛑',
        'колодк' => '⏹️', 'pad' => '⏹️', 'brake pad' => '⏹️',
        'диск' => '⭕', 'disc' => '⭕', 'rotor' => '⭕', 'brake disc' => '⭕',
        'суппорт' => '🔧', 'caliper' => '🔧', 'brake caliper' => '🔧',
        'тормозная жидкость' => '💧', 'brake fluid' => '💧',

        // ПОДВЕСКА И РУЛЕВОЕ УПРАВЛЕНИЕ
        'амортизатор' => '🚗', 'shock' => '🚗', 'стойк' => '🚗', 'strut' => '🚗',
        'пружин' => '🌀', 'spring' => '🌀', 'coil' => '🌀',
        'рычаг' => '🔗', 'lever' => '🔗', 'arm' => '🔗', 'control arm' => '🔗',
        'рулев' => '🚘', 'steering' => '🚘', 'руль' => '🚘', 'wheel' => '🚘',
        'тяг' => '🔗', 'rod' => '🔗', 'link' => '🔗',

        // ЭЛЕКТРИКА И ОСВЕЩЕНИЕ
        'аккумулятор' => '🔋', 'battery' => '🔋', 'accumulator' => '🔋',
        'генератор' => '⚡', 'generator' => '⚡', 'alternator' => '⚡',
        'стартер' => '🔌', 'starter' => '🔌',
        'фар' => '💡', 'light' => '💡', 'лампа' => '💡', 'lamp' => '💡',
        'провод' => '🔌', 'wire' => '🔌', 'cable' => '🔌', 'проводка' => '🔌',
        'датчик' => '📡', 'sensor' => '📡',

        // ТРАНСМИССИЯ И СЦЕПЛЕНИЕ
        'сцеплен' => '🔄', 'clutch' => '🔄', 'коробк' => '🔀', 'gearbox' => '🔀',
        'трансмисси' => '🔀', 'transmission' => '🔀', 'кпп' => '🔀',
        'привод' => '⚙️', 'drive' => '⚙️', 'cardan' => '⚙️',

        // ВЫХЛОПНАЯ СИСТЕМА
        'глушитель' => '📢', 'muffler' => '📢', 'выхлоп' => '📢', 'exhaust' => '📢',
        'катализатор' => '♻️', 'catalyst' => '♻️', 'catalytic' => '♻️',

        // ОХЛАЖДЕНИЕ И ОТОПЛЕНИЕ
        'радиатор' => '❄️', 'radiator' => '❄️', 'охлажден' => '❄️', 'cooling' => '❄️',
        'термостат' => '🌡️', 'thermostat' => '🌡️', 'вентилятор' => '🌀', 'fan' => '🌀',
        'печк' => '🔥', 'heater' => '🔥', 'отоплен' => '🔥', 'heating' => '🔥',

        // ШИНЫ И ДИСКИ
        'шин' => '🛞', 'tire' => '🛞', 'tyre' => '🛞', 'колес' => '🛞', 'wheel' => '🛞',
        'диск' => '⭕', 'rim' => '⭕', 'колпак' => '🔘', 'cover' => '🔘',
        'камер' => '🎯', 'tube' => '🎯',

        // КУЗОВНЫЕ ДЕТАЛИ
        'зеркал' => '🔍', 'mirror' => '🔍',
        'стекло' => '🔍', 'glass' => '🔍', 'окно' => '🔍', 'window' => '🔍',
        'двер' => '🚪', 'door' => '🚪',
        'капот' => '📦', 'hood' => '📦', 'bonnet' => '📦',
        'бампер' => '🚗', 'bumper' => '🚗',

        // ОБЩИЕ АВТОЗАПЧАСТИ
        'ремень' => '📏', 'belt' => '📏', 'ремень грм' => '⚙️',
        'цеп' => '⛓️', 'chain' => '⛓️', 'цепь грм' => '⚙️',
        'подшипник' => '⚪', 'bearing' => '⚪',
        'сальник' => '⭕', 'seal' => '⭕', 'gasket' => '⭕',
        'втулк' => '🔘', 'bushing' => '🔘',
        'гайк' => '🔩', 'nut' => '🔩', 'болт' => '🔩', 'bolt' => '🔩',
        'шайб' => '⭕', 'washer' => '⭕',
    ];

    // EMOJI ПО УМОЛЧАНИЮ ДЛЯ КАТЕГОРИЙ (если не нашли по ключевым словам)
    $categoryEmojis = [
        1 => '⚙️',  // Двигатель
        2 => '🛑',  // Тормоза
        3 => '🚗',  // Подвеска
        4 => '🔋',  // Электрика
        5 => '🛞',  // Шины
    ];

    // ПРОБЕГАЕМСЯ ПО ВСЕМ КЛЮЧЕВЫМ СЛОВАМ И ИЩЕМ СОВПАДЕНИЯ
    foreach ($keywordEmojis as $keyword => $emoji) {
        if (strpos($name, $keyword) !== false) {
            return $emoji;
        }
    }

    // ЕСЛИ НЕ НАШЛИ ПО КЛЮЧЕВЫМ СЛОВАМ - ИСПОЛЬЗУЕМ EMOJI ПО КАТЕГОРИИ
    if ($categoryId && isset($categoryEmojis[$categoryId])) {
        return $categoryEmojis[$categoryId];
    }

    // ЕСЛИ ВСЁ ПЛОХО - ИСПОЛЬЗУЕМ ОБЩУЮ ИКОНКУ
    return '🛠️';
}
*/
/**
 * Получаем цвет фона для карточки товара
 */
function getProductColor($categoryId)
{
    $colors = [
        1 => '#1a4721',  // Двигатель - тёмно-зелёный
        2 => '#e74c3c',  // Тормоза - красный
        3 => '#f9a602',  // Подвеска - жёлтый
        4 => '#3498db',  // Электрика - синий
        5 => '#2d5a2d',  // Шины - зелёный
    ];

    return $colors[$categoryId] ?? '#2d5a2d';
}

/**
 * Универсальная функция для получения изображения товара
 * Используется во всех местах где нужна картинка товара
 */
function getProductImage($product)
{
    $name = $product['name'];
    $categoryId = $product['categoryId'] ?? null;

    // Возвращаем emoji для использования в div
    //return getProductEmoji($name, $categoryId);
}
