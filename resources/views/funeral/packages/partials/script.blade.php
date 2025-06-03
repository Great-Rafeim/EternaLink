<script>
let categoryCount = 0;

function addCategory() {
    const container = document.getElementById('categoriesContainer');
    const template = document.getElementById('categoryTemplate').innerHTML.replace(/{index}/g, categoryCount);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template;
    container.appendChild(wrapper);
    categoryCount++;
    updateTotalPrice();
}

function removeCategory(button) {
    button.closest('.category').remove();
    updateTotalPrice();
}

function addItem(button) {
    const categoryDiv = button.closest('.category');
    const categoryIndex = categoryDiv.getAttribute('data-category-index');
    const itemsList = categoryDiv.querySelector('.itemsList');

    const itemIndex = itemsList.children.length;
    const itemTemplate = document.getElementById('itemTemplate').innerHTML
        .replace(/{categoryIndex}/g, categoryIndex)
        .replace(/{itemIndex}/g, itemIndex);

    const wrapper = document.createElement('div');
    wrapper.innerHTML = itemTemplate;
    itemsList.appendChild(wrapper);
    updateTotalPrice();
}

function removeItem(button) {
    button.closest('.item').remove();
    updateTotalPrice();
}

document.addEventListener('input', updateTotalPrice);

function updateTotalPrice() {
    let total = 0;
    const items = document.querySelectorAll('.item');
    items.forEach(item => {
        const qty = parseFloat(item.querySelector('.item-quantity')?.value || 0);
        const price = parseFloat(item.querySelector('.item-price')?.value || 0);
        total += qty * price;
    });

    document.getElementById('calculatedTotal').value = total.toFixed(2);
}
</script>
