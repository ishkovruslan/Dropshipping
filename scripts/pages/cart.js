document.addEventListener('DOMContentLoaded', () => { /* Пошук по фрагменту ПІБ кінцевого користувача */
    const searchInput = document.querySelector('#searchConsumer');
    const resultsContainer = document.querySelector('#searchResults');

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        if (query.length > 2) {
            fetch(`../../php/cart.php?action=search&query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.textContent = item.full_name;
                        div.dataset.id = item.id;
                        div.classList.add('result-item');
                        resultsContainer.appendChild(div);
                    });
                });
        }
    });

    resultsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('result-item')) {
            const id = e.target.dataset.id;
            fetch(`../../php/cart.php?action=details&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.querySelector('#full_name').value = data.full_name || '';
                    document.querySelector('#phone').value = data.phone || '';
                    document.querySelector('#email').value = data.email || '';
                    document.querySelector('#post_type').value = data.post || '';
                    document.querySelector('#city').value = data.city || '';
                    document.querySelector('#post_number').value = data.post_number || '';
                    
                    resultsContainer.innerHTML = '';
                });
        }
    });    
});

$(document).ready(function() {
    $('form').on('submit', function(e) {
        if ($('.error-message').length) {
            e.preventDefault();
            alert('Будь ласка, виправте помилки перед продовженням.');
        }
    });
});
