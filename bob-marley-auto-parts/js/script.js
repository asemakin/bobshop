/**
 * Bob Marley Auto Parts - JavaScript
 * One Love, One Cart! 🎵
 *
 * Этот файл содержит весь JavaScript для магазина
 * Включает анимации, валидацию форм и интерактивные элементы
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎵 Bob Marley Auto Parts loaded! One Love! 🎵');

    // Анимация при добавлении в корзину - подсвечиваем кнопку
    const addToCartButtons = document.querySelectorAll('form[action="cart.php"] button');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Временное изменение стиля кнопки для обратной связи
            const originalBg = this.style.background;
            this.style.background = '#27ae60';
            this.style.color = 'white';

            setTimeout(() => {
                this.style.background = originalBg;
                this.style.color = '';
            }, 300);

            console.log('🛒 Товар добавлен в корзину!');
        });
    });

    // Валидация форм перед отправкой - проверяем обязательные поля
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#e74c3c'; // Красная рамка для ошибки
                } else {
                    field.style.borderColor = ''; // Убираем красную рамку
                }
            });

            // Если есть незаполненные поля - останавливаем отправку
            if (!valid) {
                e.preventDefault();
                alert('🎵 Пожалуйста, заполните все обязательные поля!');
            }
        });
    });

    // Плавная прокрутка для внутренних ссылок (якорей)
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId !== '#') {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    // Плавно прокручиваем к элементу
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Подтверждение удаления товаров из корзины
    const removeButtons = document.querySelectorAll('form[action="cart.php"] button.btnDanger');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Удалить товар из корзины?')) {
                e.preventDefault(); // Отменяем отправку формы если пользователь сказал "нет"
            }
        });
    });

    // Добавляем CSS стили для анимаций через JavaScript
    const style = document.createElement('style');
    style.textContent = `
        /* Анимация появления карточек товаров */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Применяем анимацию к карточкам товаров */
        .productCard {
            animation: fadeInUp 0.6s ease;
        }
        
        /* Задержка для последовательного появления карточек */
        .productsGrid .productCard:nth-child(1) { animation-delay: 0.1s; }
        .productsGrid .productCard:nth-child(2) { animation-delay: 0.2s; }
        .productsGrid .productCard:nth-child(3) { animation-delay: 0.3s; }
        .productsGrid .productCard:nth-child(4) { animation-delay: 0.4s; }
        .productsGrid .productCard:nth-child(5) { animation-delay: 0.5s; }
        .productsGrid .productCard:nth-child(6) { animation-delay: 0.6s; }
        
        /* Плавные переходы для кнопок */
        .btn {
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        /* Анимация при наведении на карточку товара */
        .productCard {
            transition: all 0.3s ease;
        }
        
        .productCard:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    `;
    document.head.appendChild(style);

    console.log('🎵 JavaScript initialized successfully!');
});

// Глобальная функция для проверки email (можно использовать в формах)
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Глобальная функция для подтверждения действий
function confirmAction(message) {
    return confirm(message || 'Вы уверены?');
}
