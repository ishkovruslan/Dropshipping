document.getElementById("category").addEventListener("change", function () {
    var selectedCategory = this.value;
    var characteristicsDiv = document.getElementById("characteristics");
    characteristicsDiv.innerHTML = "";

    categories.forEach(function (category) {
        if (selectedCategory === category.category_name) {
            var specifications = category.specifications.split(",");
            specifications.forEach(function (spec, index) {
                characteristicsDiv.innerHTML += '<div class="form-group"><label for="characteristic_' + index + '">' + spec + ':</label><input type="text" name="characteristics[' + index + ']" id="characteristic_' + index + '"></div>';
            });
        }
    });
});
