function updateUrlWithTheme(newTheme) { /* Теми */
    var urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('theme');
    urlParams.set('theme', newTheme);
    window.location.search = urlParams.toString();
}

var themeButton = document.getElementById('themeButton'); /* Обробник зміни тем */
themeButton.addEventListener('click', function () {
    var currentTheme = themeButton.getAttribute('data-theme');
    var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    updateUrlWithTheme(newTheme);
    themeButton.setAttribute('data-theme', newTheme);
});