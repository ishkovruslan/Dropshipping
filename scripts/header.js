// Функція для оновлення URL з параметрами запиту та новою темою
function updateUrlWithTheme(newTheme) {
    var urlParams = new URLSearchParams(window.location.search);
    // Видаляємо всі параметри теми з URL
    urlParams.delete('theme');
    // Додаємо новий параметр теми
    urlParams.set('theme', newTheme);
    // Оновлюємо адресу сторінки з новим параметром теми та параметрами запиту
    window.location.search = urlParams.toString();
}
// Обробник подій для кнопки зміни теми
var themeButton = document.getElementById('themeButton');
themeButton.addEventListener('click', function () {
    var currentTheme = themeButton.getAttribute('data-theme');
    var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    updateUrlWithTheme(newTheme);
    // Оновлюємо атрибут data-theme
    themeButton.setAttribute('data-theme', newTheme);
});