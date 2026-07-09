<?php
/**
 * =============================================================================
 * ФАЙЛ: api.php
 * ЗА ЧТО ОТВЕЧАЕТ: автоматический парсинг данных Минфина РФ
 * =============================================================================
 *
 * ИСТОЧНИК ДАННЫХ:
 * Официальный портал Минфина России (opendata.minfin.gov.ru)
 *
 * КАК РАБОТАЕТ:
 * 1. Загружает CSV-файл с сайта Минфина
 * 2. Парсит его в массив PHP
 * 3. Кэширует результат (чтобы не перегружать сервер Минфина)
 * 4. Отдаёт данные в формате JSON
 *
 * ОБНОВЛЕНИЕ:
 * Кэш обновляется 1 раз в сутки
 * =============================================================================
 */

// -----------------------------------------------------------------------------
// 1. ЗАГОЛОВКИ ОТВЕТА
// -----------------------------------------------------------------------------
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Europe/Moscow');

// -----------------------------------------------------------------------------
// 2. НАСТРОЙКИ
// -----------------------------------------------------------------------------

// Ссылка на официальный файл Минфина (данные по регионам)
$csv_url = 'https://minfin.gov.ru/opendata/7710168360-subbud_year/structure-20190701T0100.csv';

// Путь к папке с кэшем
$cache_dir = __DIR__ . '/data/';

// Путь к файлу кэша
$cache_file = $cache_dir . 'cache.json';

// Время жизни кэша (86400 секунд = 1 сутки)
$cache_lifetime = 86400;

// -----------------------------------------------------------------------------
// 3. ПРОВЕРЯЕМ КЭШ
// -----------------------------------------------------------------------------

// Создаём папку data, если её нет
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0777, true);
}

// Создаём файл счётчика, если его нет
$counter_file = $cache_dir . 'counter.txt';
if (!file_exists($counter_file)) {
    file_put_contents($counter_file, '0');
}

// -----------------------------------------------------------------------------
// 4. ЧИТАЕМ СЧЁТЧИК ПОСЕТИТЕЛЕЙ
// -----------------------------------------------------------------------------

// Открываем файл, блокируем, читаем, увеличиваем, записываем
$fp = fopen($counter_file, 'r+');
if (flock($fp, LOCK_EX)) {
    $visitors = (int)fread($fp, filesize($counter_file) ?: 1);
    $visitors++;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string)$visitors);
    flock($fp, LOCK_UN);
}
fclose($fp);

// Форматируем число с пробелами между разрядами
$visitors_formatted = number_format($visitors, 0, '.', ' ');

// -----------------------------------------------------------------------------
// 5. ПРОВЕРЯЕМ, НУЖНО ЛИ ОБНОВИТЬ КЭШ
// -----------------------------------------------------------------------------

$use_cache = false;
$cache_data = null;

// Если файл кэша существует и он не устарел
if (file_exists($cache_file)) {
    $file_age = time() - filemtime($cache_file);
    if ($file_age < $cache_lifetime) {
        $use_cache = true;
        $cache_data = json_decode(file_get_contents($cache_file), true);
    }
}

// -----------------------------------------------------------------------------
// 6. ЕСЛИ КЭШ УСТАРЕЛ — ПАРСИМ ЗАНОВО
// -----------------------------------------------------------------------------

if ($use_cache && $cache_data) {
    // Используем кэш
    $regions = $cache_data['regions'];
    $years = $cache_data['years'];
    $update_date = $cache_data['update_date'];
} else {
    // Парсим свежие данные
    try {
        // ===== 6.1. ЗАГРУЖАЕМ CSV =====
        // file_get_contents — загружает файл по URL
        $csv_content = file_get_contents($csv_url);

        // Проверяем, что загрузилось
        if ($csv_content === false) {
            throw new Exception('Не удалось загрузить CSV-файл с сайта Минфина');
        }

        // ===== 6.2. РАЗБИВАЕМ НА СТРОКИ =====
        // explode — разбивает строку по символу \n (перевод строки)
        $lines = explode("\n", $csv_content);

        // Удаляем пустые строки
        $lines = array_filter($lines, function($line) {
            return trim($line) !== '';
        });

        // ===== 6.3. ПАРСИМ ЗАГОЛОВКИ =====
        // str_getcsv — парсит CSV-строку в массив
        $headers = str_getcsv($lines[0]);

        // ===== 6.4. ПАРСИМ ДАННЫЕ =====
        $regions = [];
        $years = [];

        // Проходим по строкам (начиная со второй, где данные)
        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);

            // Пропускаем пустые строки
            if (count($row) < 2) continue;

            // Определяем индексы столбцов (зависит от структуры CSV)
            // У Минфина обычно: регион, год, доходы, расходы
            // Индексы могут меняться — проверьте файл!
            $region_name = trim($row[0]);   // Название региона
            $year = (int)trim($row[1]);     // Год
            $income = floatval(str_replace(',', '.', trim($row[2])));  // Доходы
            $expense = floatval(str_replace(',', '.', trim($row[3]))); // Расходы

            // Сохраняем год в список (если ещё нет)
            if (!in_array($year, $years)) {
                $years[] = $year;
            }

            // Добавляем регион в массив
            $regions[] = [
                'name' => $region_name,
                'year' => $year,
                'income' => $income,
                'expense' => $expense,
            ];
        }

        // Сортируем годы по возрастанию
        sort($years);

        // ===== 6.5. СОХРАНЯЕМ КЭШ =====
        $update_date = date('d.m.Y H:i:s');
        $cache_data = [
            'regions' => $regions,
            'years' => $years,
            'update_date' => $update_date,
        ];

        // file_put_contents — сохраняет данные в файл
        file_put_contents($cache_file, json_encode($cache_data, JSON_UNESCAPED_UNICODE));

    } catch (Exception $e) {
        // Если ошибка при парсинге, пробуем использовать старый кэш
        if (file_exists($cache_file)) {
            $cache_data = json_decode(file_get_contents($cache_file), true);
            $regions = $cache_data['regions'];
            $years = $cache_data['years'];
            $update_date = $cache_data['update_date'] . ' (кэш)';
        } else {
            // Если кэша нет — отдаём ошибку
            $error_response = [
                'error' => $e->getMessage(),
                'regions' => [],
                'years' => [],
                'visitors' => $visitors_formatted,
                'update_date' => date('d.m.Y H:i:s'),
            ];
            echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 7. ФОРМИРУЕМ ОТВЕТ
// -----------------------------------------------------------------------------

$response = [
    'regions' => $regions,           // Все данные по регионам
    'years' => $years,               // Список доступных годов
    'visitors' => $visitors_formatted, // Счётчик посетителей
    'update_date' => $update_date,   // Дата обновления
];

// -----------------------------------------------------------------------------
// 8. ОТПРАВЛЯЕМ JSON
// -----------------------------------------------------------------------------

echo json_encode($response, JSON_UNESCAPED_UNICODE);

