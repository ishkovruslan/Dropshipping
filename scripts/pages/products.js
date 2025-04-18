function resetFilters(event) { /* Сортування продуктів */
    event.preventDefault();
    document.querySelector('input[name="minPrice"]').value = '';
    document.querySelector('input[name="maxPrice"]').value = '';
    document.querySelector('select[name="sort"]').value = 'asc';
    window.location.href = 'products.php?category=' + encodeURIComponent(category);
}
