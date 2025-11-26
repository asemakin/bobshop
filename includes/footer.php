<?php
/**
 * Футер (подвал) сайта Bob Marley Auto Parts
 * Этот файл подключается в конец каждой страницы
 */
?>

<footer class="footer">
    <div class="container">
        <!-- Информация о магазине -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- Блок контактов -->
            <div>
                <h3 style="color: #f9a602; margin-bottom: 1rem;">🎵 Контакты</h3>
                <p>📞 Телефон: +7 (999) 123-45-67</p>
                <p>📧 Email: info@bobmarleyparts.ru</p>
                <p>📍 Адрес: г. Москва, ул. Регги, д. 1</p>
            </div>

            <!-- Блок информации -->
            <div>
                <h3 style="color: #f9a602; margin-bottom: 1rem;">🛠️ О магазине</h3>
                <p>Качественные автозапчасти</p>
                <p>Быстрая доставка</p>
                <p>Гарантия на все товары</p>
            </div>

            <!-- Блок графика работы -->
            <div>
                <h3 style="color: #f9a602; margin-bottom: 1rem;">🕒 График работы</h3>
                <p>Пн-Пт: 9:00 - 20:00</p>
                <p>Сб-Вс: 10:00 - 18:00</p>
            </div>
        </div>

        <!-- Копирайт и финальное сообщение -->
        <div style="text-align: center; padding-top: 2rem; border-top: 1px solid #2d5a2d;">
            <p>&copy; <?php echo date('Y'); ?> Bob Marley Auto Parts. One Love, One Heart, Quality Auto Parts! 🎵</p>
            <p>Сделано с ❤️ и регги вибрациями |</a></p>
        </div>
    </div>
</footer>

<!-- Подключаем JavaScript файл -->
<script src="js/script.js"></script>

</body>
</html>
