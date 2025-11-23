<?php
/**
 * Функции для работы с изображениями товаров
 * Обработка, загрузка, создание миниатюр и управление файлами
 */

/**
 * Загрузка и обработка изображения товара
 * @param array $fileData - данные файла из $_FILES
 * @param int $productId - ID товара для генерации имени файла
 * @return array - пути к основному изображению и миниатюре
 * @throws Exception - в случае ошибок загрузки или обработки
 */
function uploadProductImage($fileData, $productId) {
    // Проверяем, был ли файл загружен
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла. Код ошибки: ' . $fileData['error']);
    }

    // Проверяем тип файла
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $fileData['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        throw new Exception('Недопустимый тип файла. Разрешены только: JPG, PNG, WebP, GIF');
    }

    // Проверяем размер файла (максимум 5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5MB в байтах
    if ($fileData['size'] > $maxFileSize) {
        throw new Exception('Файл слишком большой. Максимальный размер: 5MB');
    }

    // Создаем директории если они не существуют
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/products/';
    $thumbDir = $uploadDir . 'thumbs/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!file_exists($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }

    // Генерируем уникальное имя файла на основе ID товара и временной метки
    $fileExtension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
    $safeFileName = 'product_' . $productId . '_' . time() . '.' . strtolower($fileExtension);
    $mainImagePath = $uploadDir . $safeFileName;
    $thumbImagePath = $thumbDir . $safeFileName;

    // Перемещаем загруженный файл в целевую директорию
    if (!move_uploaded_file($fileData['tmp_name'], $mainImagePath)) {
        throw new Exception('Не удалось сохранить файл на сервер');
    }

    // Создаем миниатюру для быстрой загрузки в каталоге
    createImageThumbnail($mainImagePath, $thumbImagePath, 300, 300);

    // Возвращаем относительные пути для хранения в базе данных
    return [
        'mainImage' => 'uploads/products/' . $safeFileName,
        'thumbImage' => 'uploads/products/thumbs/' . $safeFileName
    ];
}

/**
 * Создание миниатюры изображения с сохранением пропорций
 * @param string $sourcePath - путь к исходному изображению
 * @param string $destinationPath - путь для сохранения миниатюры
 * @param int $targetWidth - желаемая ширина миниатюры
 * @param int $targetHeight - желаемая высота миниатюры
 * @return bool - true при успешном создании, false при ошибке
 */
function createImageThumbnail($sourcePath, $destinationPath, $targetWidth, $targetHeight) {
    // Получаем информацию об исходном изображении
    list($originalWidth, $originalHeight, $imageType) = getimagesize($sourcePath);

    // Определяем функцию для загрузки изображения в зависимости от типа
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false; // Неподдерживаемый тип изображения
    }

    // Вычисляем новые размеры с сохранением пропорций
    $sourceRatio = $originalWidth / $originalHeight;
    $targetRatio = $targetWidth / $targetHeight;

    if ($sourceRatio > $targetRatio) {
        // Исходное изображение шире
        $newWidth = $targetWidth;
        $newHeight = $targetWidth / $sourceRatio;
    } else {
        // Исходное изображение выше
        $newHeight = $targetHeight;
        $newWidth = $targetHeight * $sourceRatio;
    }

    // Создаем новое изображение для миниатюры
    $thumbnailImage = imagecreatetruecolor($targetWidth, $targetHeight);

    // Заполняем фон белым цветом (для PNG с прозрачностью)
    $whiteColor = imagecolorallocate($thumbnailImage, 255, 255, 255);
    imagefill($thumbnailImage, 0, 0, $whiteColor);

    // Вычисляем позицию для центрирования изображения
    $offsetX = ($targetWidth - $newWidth) / 2;
    $offsetY = ($targetHeight - $newHeight) / 2;

    // Копируем и изменяем размер исходного изображения
    imagecopyresampled(
        $thumbnailImage, $sourceImage,
        $offsetX, $offsetY, 0, 0,
        $newWidth, $newHeight, $originalWidth, $originalHeight
    );

    // Сохраняем миниатюру в зависимости от типа изображения
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbnailImage, $destinationPath, 85); // Качество 85%
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbnailImage, $destinationPath, 8); // Сжатие 8
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumbnailImage, $destinationPath, 85); // Качество 85%
            break;
        case IMAGETYPE_GIF:
            imagegif($thumbnailImage, $destinationPath);
            break;
    }

    // Освобождаем память
    imagedestroy($sourceImage);
    imagedestroy($thumbnailImage);

    return true;
}

/**
 * Удаление изображений товара (основного и миниатюры)
 * @param string $imagePath - путь к основному изображению в базе данных
 */
function deleteProductImages($imagePath) {
    // Не удаляем изображение по умолчанию
    if (!$imagePath || $imagePath === 'uploads/products/default.png') {
        return;
    }

    $basePath = $_SERVER['DOCUMENT_ROOT'] . '/';
    $mainImagePath = $basePath . $imagePath;
    $thumbImagePath = $basePath . dirname($imagePath) . '/thumbs/' . basename($imagePath);

    // Удаляем основное изображение если оно существует
    if (file_exists($mainImagePath)) {
        unlink($mainImagePath);
    }

    // Удаляем миниатюру если она существует
    if (file_exists($thumbImagePath)) {
        unlink($thumbImagePath);
    }
}

/**
 * Получение HTML-кода для отображения изображения товара
 * @param string $imagePath - путь к изображению из базы данных
 * @param string $productName - название товара для alt текста
 * @param string $cssClass - CSS класс для стилизации
 * @return string - HTML код изображения или emoji fallback
 */
function getProductImageHtml($imagePath, $productName, $cssClass = 'productImage') {
    // Если изображение не задано или это изображение по умолчанию, используем emoji
    if (!$imagePath || $imagePath === 'uploads/products/default.png') {
        $emoji = getProductEmoji($productName);
        return '<div class="' . $cssClass . ' emojiFallback" title="' . htmlspecialchars($productName) . '">' . $emoji . '</div>';
    }

    // Проверяем существование файла на сервере
    $fullImagePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $imagePath;
    if (!file_exists($fullImagePath)) {
        $emoji = getProductEmoji($productName);
        return '<div class="' . $cssClass . ' emojiFallback" title="' . htmlspecialchars($productName) . '">' . $emoji . '</div>';
    }

    // Генерируем путь к миниатюре
    $thumbPath = dirname($imagePath) . '/thumbs/' . basename($imagePath);
    $fullThumbPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $thumbPath;

    // Если миниатюра существует, используем её для быстрой загрузки
    if (file_exists($fullThumbPath)) {
        return '<img src="/' . $thumbPath . '" 
                     data-src="/' . $imagePath . '"
                     alt="' . htmlspecialchars($productName) . '" 
                     class="' . $cssClass . ' lazyImage" 
                     loading="lazy">';
    }

    // Иначе используем основное изображение
    return '<img src="/' . $imagePath . '" 
                 alt="' . htmlspecialchars($productName) . '" 
                 class="' . $cssClass . '" 
                 loading="lazy">';
}

/**
 * Получение emoji для товара на основе его названия
 * @param string $productName - название товара
 * @return string - соответствующий emoji
 */
function getProductEmoji($productName) {
    // Словарь соответствия ключевых слов и emoji
    $emojiDictionary = [
        'масло' => '🛢️',      // Моторные масла
        'тормоз' => '🛑',     // Тормозные системы
        'фильтр' => '🧹',     // Фильтры
        'свеч' => '⚡',       // Свечи зажигания
        'амортизатор' => '🚗', // Амортизаторы
        'колодк' => '⏹️',     // Тормозные колодки
        'жидкост' => '💧',    // Жидкости
        'катушк' => '🌀',     // Катушки зажигания
        'подвеск' => '🔩',    // Подвеска
        'рулев' => '🚘',      // Рулевое управление
        'аккумулятор' => '🔋', // Аккумуляторы
        'шина' => '🌀',       // Шины
        'диск' => '⚫',       // Диски
        'фара' => '💡',       // Фары
        'стекло' => '🔍',     // Стекла
        'цеп' => '⛓️',        // Цепи
        'ремен' => '📿',      // Ремни
        'датчик' => '📡',     // Датчики
        'провод' => '🔌',     // Провода
        'насос' => '💦',      // Насосы
        'радиатор' => '🌡️',   // Радиаторы
        'вентилятор' => '💨', // Вентиляторы
        'генератор' => '⚡',   // Генераторы
        'стартер' => '🔧',    // Стартеры
    ];

    $lowerCaseName = mb_strtolower($productName);

    // Ищем ключевые слова в названии товара
    foreach ($emojiDictionary as $keyword => $emoji) {
        if (strpos($lowerCaseName, $keyword) !== false) {
            return $emoji;
        }
    }

    // Emoji по умолчанию для автозапчастей
    return '🔧';
}

/**
 * Валидация загружаемого изображения перед сохранением
 * @param array $fileData - данные файла
 * @return array - массив с ошибками или пустой массив если ошибок нет
 */
function validateImageUpload($fileData) {
    $errors = [];

    // Проверяем код ошибки загрузки
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер, разрешенный сервером',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер, указанный в форме',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Расширение PHP остановило загрузку файла'
        ];

        $errors[] = $uploadErrors[$fileData['error']] ?? 'Неизвестная ошибка загрузки';
        return $errors;
    }

    // Проверяем MIME-тип
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $fileData['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Недопустимый тип файла. Разрешены только: JPG, PNG, WebP, GIF';
    }

    // Проверяем размер файла (максимум 5MB)
    if ($fileData['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Файл слишком большой. Максимальный размер: 5MB';
    }

    // Проверяем, что файл действительно является изображением
    if (!getimagesize($fileData['tmp_name'])) {
        $errors[] = 'Загружаемый файл не является изображением';
    }

    return $errors;
}

