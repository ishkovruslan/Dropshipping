function updateButtonText(selectElement) {
    const submitButton = selectElement.nextElementSibling;
    if (selectElement.value === 'delete') {
        submitButton.textContent = 'Видалити';
        submitButton.name = 'delete_user';
    } else {
        submitButton.textContent = 'Змінити';
        submitButton.name = 'change_role';
    }
}
function openEditModal(id, image, title, description, startDate, endDate) {
    document.getElementById('news_id').value = id;
    document.getElementById('delete_news_id').value = id;
    document.getElementById('news_title').value = title;
    document.getElementById('news_description').value = description;
    document.getElementById('start_date').value = startDate;
    document.getElementById('end_date').value = endDate;
    document.getElementById('editModal').style.display = "block";
}

function closeEditModal() {
    document.getElementById('editModal').style.display = "none";
}

function openEditProductModal(id, image, category, name, count, price, characteristics) {
    document.getElementById('product_id').value = id;
    document.getElementById('delete_product_id').value = id;
    document.getElementById('category').value = category;
    document.getElementById('product_name').value = name;
    document.getElementById('count').value = count;
    document.getElementById('price').value = price;
    document.getElementById('characteristics').value = characteristics;
    document.getElementById('editProductModal').style.display = "block";
}

function closeEditProductModal() {
    document.getElementById('editProductModal').style.display = "none";
}

window.onclick = function (event) {
    var editModal = document.getElementById('editModal');
    var editProductModal = document.getElementById('editProductModal');
    if (event.target == editModal) {
        editModal.style.display = "none";
    }
    if (event.target == editProductModal) {
        editProductModal.style.display = "none";
    }
}
