
// edit.js - скрипт для редактирования таблицы

function initializeTableEditing() {
    document.querySelectorAll('.editable').forEach(cell => {
        cell.addEventListener('click', function() {
            const id = this.parentElement.getAttribute('data-id');
            const field = this.getAttribute('data-field');
            const originalValue = this.textContent.trim();
            const value = field === 'price' ? originalValue.replace('$', '').trim() : originalValue;

            const input = document.createElement('input');
            input.type = field === 'quantity' ? 'number' : (field === 'price' ? 'number' : 'text');
            input.className = 'edit-field';
            input.value = value;

            if (field === 'quantity') input.min = '0';
            if (field === 'price') {
                input.step = '0.01';
                input.min = '0';
            }

            this.innerHTML = '';
            this.appendChild(input);
            input.focus();

            const finishEdit = () => {
                const newValue = input.value.trim();

                if (newValue !== value) {
                    const formData = new FormData();
                    formData.append('update_product', '1');
                    formData.append('product_id', id);
                    formData.append('field', field);
                    formData.append('value', newValue);

                    // Используем текущий URL страницы для AJAX-запроса
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.text())
                        .then(result => {
                            if (result.includes('Успешно')) {
                                this.textContent = field === 'price' ?
                                    parseFloat(newValue).toFixed(2) + ' $' : newValue;
                            } else {
                                alert('Ошибка: ' + result);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.textContent = originalValue;
                        });
                } else {
                    this.textContent = originalValue;
                }
            };

            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') finishEdit();
            });

            input.addEventListener('blur', finishEdit);
        });
    });
}

// Инициализируем редактирование после загрузки DOM
document.addEventListener('DOMContentLoaded', initializeTableEditing);