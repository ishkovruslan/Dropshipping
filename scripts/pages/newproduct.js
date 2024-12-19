document.addEventListener("DOMContentLoaded", function () { /* Вивід наявних категорій для запису нових товарів */
    const categorySelect = document.getElementById("category");
    const characteristicsDiv = document.getElementById("characteristics");

    categorySelect.addEventListener("change", function () {
        const selectedCategory = this.value;
        characteristicsDiv.innerHTML = "";

        categories.forEach(function (category) {
            if (selectedCategory === category.category_name) {
                const specifications = category.specifications.split(",");
                specifications.forEach(function (spec, index) {
                    const formGroup = document.createElement("div");
                    formGroup.className = "form-group";

                    const label = document.createElement("label");
                    label.setAttribute("for", `characteristic_${index}`);
                    label.textContent = `${spec}:`;

                    const input = document.createElement("input");
                    input.type = "text";
                    input.name = `characteristics[${index}]`;
                    input.id = `characteristic_${index}`;
                    input.required = true;

                    formGroup.appendChild(label);
                    formGroup.appendChild(input);
                    characteristicsDiv.appendChild(formGroup);
                });
            }
        });
    });
});
