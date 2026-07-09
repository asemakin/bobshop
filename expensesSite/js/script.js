/**
 * ФАЙЛ: js/script.js
 * ЗА ЧТО ОТВЕЧАЕТ: загружает данные с api.php и показывает на странице
 *
 * ПОРЯДОК РАБОТЫ:
 * 1. Ждём загрузки страницы
 * 2. Запрашиваем данные из api.php
 * 3. Вставляем их в нужные места на странице
 * 4. Настраиваем поиск и сортировку
 */

// Ждём, пока браузер загрузит HTML
document.addEventListener('DOMContentLoaded', function() {

    // ===== 1. НАХОДИМ ВСЕ ЭЛЕМЕНТЫ НА СТРАНИЦЕ =====
    // getElementById — ищет элемент по его id

    // Элементы для расходов на войну
    const visitorEl = document.getElementById('visitorCount');      // Счётчик посетителей
    const totalEl = document.getElementById('totalAmount');         // Крупная сумма
    const totalRawEl = document.getElementById('totalAmountRaw');   // Сумма цифрами
    const daysEl = document.getElementById('daysPassed');           // Количество дней
    const dailyEl = document.getElementById('dailyCost');           // Расход в день
    const datetimeEl = document.getElementById('currentDateTime');   // Дата и время

    // Элементы для регионов
    const totalBudgetEl = document.getElementById('totalBudget');       // Общий бюджет
    const warDaysEl = document.getElementById('warDaysPerYear');        // Дней войны
    const warsEl = document.getElementById('warsPerYear');              // Войн в год
    const tableBody = document.getElementById('regionsTableBody');      // Тело таблицы

    // Элементы управления
    const searchInput = document.getElementById('searchInput');         // Поле поиска
    const sortBudgetBtn = document.getElementById('sortByBudgetBtn');   // Кнопка "по бюджету"
    const sortNameBtn = document.getElementById('sortByNameBtn');       // Кнопка "по названию"

    // Переменные для хранения данных о регионах
    let regionsData = [];       // Исходные данные о регионах
    let currentRows = [];       // Текущие строки в таблице
    let sortBy = null;          // 'name' или 'budget'
    let sortDirection = 'asc';  // 'asc' (возрастание) или 'desc' (убывание)

    // ===== 2. ФУНКЦИЯ ЗАГРУЗКИ ДАННЫХ =====
    async function loadData() {
        try {
            // Показываем индикатор загрузки
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center">⏳ Загрузка...</td></tr>';
            }

            // Отправляем запрос к api.php
            // fetch — встроенная функция для запросов к серверу
            const response = await fetch('api.php');

            // Проверяем, успешно ли загрузилось
            if (!response.ok) {
                throw new Error('Ошибка HTTP: ' + response.status);
            }

            // Преобразуем ответ в JavaScript-объект
            const data = await response.json();

            // ===== 3. ОТОБРАЖАЕМ ДАННЫЕ О РАСХОДАХ =====
            if (data.war) {
                if (visitorEl) visitorEl.textContent = data.visitors;
                if (totalEl) totalEl.innerHTML = data.war.total_formatted + ' <small>₽</small>';
                if (totalRawEl) totalRawEl.textContent = data.war.total_raw + ' рублей';
                if (daysEl) daysEl.textContent = data.war.days_passed;
                if (dailyEl) dailyEl.textContent = data.war.daily_cost + ' ₽';
                if (datetimeEl) datetimeEl.textContent = data.war.datetime;
            }

            // ===== 4. ОТОБРАЖАЕМ СТАТИСТИКУ ПО РЕГИОНАМ =====
            if (data.regions) {
                if (totalBudgetEl) totalBudgetEl.textContent = data.regions.total_budget;
                if (warDaysEl) warDaysEl.textContent = data.regions.war_days_per_year + ' дней';
                if (warsEl) warsEl.textContent = '~' + data.regions.wars_per_year;
            }

            // ===== 5. ОБРАБАТЫВАЕМ ДАННЫЕ РЕГИОНОВ =====
            if (data.regions && data.regions.budgets) {
                const budgets = data.regions.budgets;

                // Превращаем объект в массив объектов (удобнее для сортировки)
                regionsData = Object.keys(budgets).map(function(name) {
                    const budget = budgets[name];
                    let readable;
                    if (budget >= 1000) {
                        readable = (budget / 1000).toFixed(1) + ' трлн';
                    } else {
                        readable = budget.toFixed(1) + ' млрд';
                    }
                    return { name: name, budget: budget, readable: readable };
                });

                // Сортируем по убыванию бюджета
                regionsData.sort(function(a, b) {
                    return b.budget - a.budget;
                });

                // Создаём строки таблицы
                createTableRows(regionsData);
            }

        } catch (error) {
            // Если ошибка — показываем её на странице
            console.error('Ошибка:', error);
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#ff6666;">❌ Ошибка загрузки</td></tr>';
            }
        }
    }

    // ===== 6. ФУНКЦИЯ СОЗДАНИЯ СТРОК ТАБЛИЦЫ =====
    function createTableRows(data) {
        if (!tableBody) return;

        tableBody.innerHTML = '';
        currentRows = [];

        // Перебираем все регионы
        data.forEach(function(item, index) {
            const row = document.createElement('tr');

            // Номер (первая колонка)
            const numCell = document.createElement('td');
            numCell.textContent = (index + 1).toString();

            // Название региона
            const nameCell = document.createElement('td');
            nameCell.textContent = item.name;
            nameCell.style.fontWeight = '600';

            // Бюджет в миллиардах
            const budgetCell = document.createElement('td');
            budgetCell.textContent = item.budget.toFixed(1);
            budgetCell.style.fontFamily = 'monospace';

            // Читаемый бюджет
            const readableCell = document.createElement('td');
            readableCell.textContent = item.readable + ' ₽';
            readableCell.style.color = '#ffd966';
            readableCell.style.fontWeight = '700';

            // Собираем строку
            row.appendChild(numCell);
            row.appendChild(nameCell);
            row.appendChild(budgetCell);
            row.appendChild(readableCell);

            tableBody.appendChild(row);
            currentRows.push(row);
        });
    }

    // ===== 7. ФУНКЦИЯ ОБНОВЛЕНИЯ ТАБЛИЦЫ (ПОИСК + СОРТИРОВКА) =====
    function updateTable() {
        if (!tableBody) return;

        // Получаем текст поиска
        const searchText = searchInput ? searchInput.value.trim().toLowerCase() : '';

        // Фильтрация
        let filtered = currentRows.filter(function(row) {
            if (searchText === '') return true;
            const name = row.cells[1].textContent.toLowerCase();
            return name.includes(searchText);
        });

        // Сортировка
        if (sortBy !== null) {
            filtered.sort(function(a, b) {
                if (sortBy === 'name') {
                    const nameA = a.cells[1].textContent;
                    const nameB = b.cells[1].textContent;
                    if (nameA < nameB) return sortDirection === 'asc' ? -1 : 1;
                    if (nameA > nameB) return sortDirection === 'asc' ? 1 : -1;
                    return 0;
                } else if (sortBy === 'budget') {
                    const budgetA = parseFloat(a.cells[2].textContent);
                    const budgetB = parseFloat(b.cells[2].textContent);
                    if (budgetA < budgetB) return sortDirection === 'asc' ? -1 : 1;
                    if (budgetA > budgetB) return sortDirection === 'asc' ? 1 : -1;
                    return 0;
                }
                return 0;
            });
        }

        // Обновляем номера строк
        filtered.forEach(function(row, idx) {
            row.cells[0].textContent = (idx + 1).toString();
        });

        // Перерисовываем таблицу
        tableBody.innerHTML = '';
        filtered.forEach(function(row) {
            tableBody.appendChild(row);
        });
    }

    // ===== 8. НАСТРАИВАЕМ ОБРАБОТЧИКИ =====
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            updateTable();
        });
    }

    if (sortBudgetBtn) {
        sortBudgetBtn.addEventListener('click', function() {
            if (sortBy === 'budget') {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortBy = 'budget';
                sortDirection = 'desc';
            }
            updateTable();
        });
    }

    if (sortNameBtn) {
        sortNameBtn.addEventListener('click', function() {
            if (sortBy === 'name') {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortBy = 'name';
                sortDirection = 'asc';
            }
            updateTable();
        });
    }

    // ===== 9. ПОДСВЕТКА СТРОКИ ПРИ КЛИКЕ =====
    if (tableBody) {
        tableBody.addEventListener('click', function(e) {
            const row = e.target.closest('tr');
            if (!row) return;

            // Убираем выделение со всех строк
            document.querySelectorAll('.regions-table tr').forEach(function(r) {
                r.classList.remove('selected');
            });
            // Выделяем текущую
            row.classList.add('selected');
        });
    }

    // ===== 10. ЗАПУСКАЕМ ЗАГРУЗКУ =====
    loadData();

    // Сообщение в консоль (для отладки)
    console.log('✅ Скрипт загружен, данные запрашиваются...');
});


