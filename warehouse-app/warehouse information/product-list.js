function toggleView(view) {
    const table = document.getElementById('productTable');
    if (view === 'list') {
        table.style.display = 'table';
        updatePagination();
    }
}

// Global variables
let searchTerm = '';
let filters = ['all'];

const searchInput = document.getElementById('searchInput');
const addProductBtn = document.getElementById('addProductBtn');
const popup = document.getElementById('addProductPopup');
const closeBtn = document.querySelector('.close-btn');
const addProductForm = document.getElementById('addProductForm');
const editProductPopup = document.getElementById('editProductPopup');
const editCloseBtn = document.querySelector('#editProductPopup .close-btn');
const editProductForm = document.getElementById('editProductForm');
const successMessage = document.getElementById('successMessage');
const successText = document.getElementById('successText');
const tbody = document.getElementById('productTableBody');
const totalRow = document.getElementById('totalRow');
const totalProductsCountElement = document.getElementById('totalProductsCount');
const imageFileInput = document.getElementById('imageFile');
const editImageFileInput = document.getElementById('editImageFile');
const imagePreview = document.getElementById('imagePreview');
const editImagePreview = document.getElementById('editImagePreview');

function showSuccessMessage(message) {
    if (!successMessage || !successText) {
        console.error('Success message elements not found:', { successMessage, successText });
        return;
    }
    successText.textContent = message;
    successMessage.classList.add('show');
    setTimeout(() => {
        successMessage.classList.remove('show');
    }, 2000);
}

function closePopup(popupType = 'add') {
    const popupElement = popupType === 'edit' ? editProductPopup : popup;
    const formElement = popupType === 'edit' ? editProductForm : addProductForm;
    const previewElement = popupType === 'edit' ? editImagePreview : imagePreview;
    if (popupElement) {
        popupElement.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            popupElement.style.display = 'none';
            formElement.reset();
            previewElement.innerHTML = '';
            previewElement.style.display = 'none';
            popupElement.style.animation = '';
        }, 300);
    } else {
        console.error('Popup element not found:', popupType);
    }
}

function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    if (menu) {
        menu.classList.toggle('show');
    } else {
        console.error('Dropdown menu not found');
    }
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.filter-option');
    checkboxes.forEach(cb => cb.checked = true);
}

function applyFilters() {
    filters = Array.from(document.querySelectorAll('.filter-option:checked'))
        .map(cb => cb.value);
    filterTable();
    currentPage = 1;
    updatePagination();
    document.getElementById('dropdownMenu')?.classList.remove('show');
}

document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        document.getElementById('dropdownMenu')?.classList.remove('show');
    }
});

let searchTimeout;
function applyFiltersDebounced() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchTerm = searchInput.value.toLowerCase();
        filterTable();
        currentPage = 1;
        updatePagination();
    }, 300);
}

if (searchInput) {
    searchInput.addEventListener('input', applyFiltersDebounced);
} else {
    console.error('Search input not found');
}

function filterTable() {
    const tableRows = document.querySelectorAll('#productTableBody tr');
    tableRows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const category = row.cells[1].textContent;
        const unit = row.cells[3].textContent;

        const searchMatch = !searchTerm || name.includes(searchTerm);

        let filterMatch = filters.includes('all');
        if (!filterMatch) {
            filterMatch = filters.some(filter => {
                return category === filter || unit === filter;
            });
        }

        if (searchMatch && filterMatch) {
            row.classList.remove('filtered-out');
        } else {
            row.classList.add('filtered-out');
        }
    });
    updateTotals();
}

let currentPage = 1;
let rowsPerPage = 5;

function updatePagination() {
    const table = document.getElementById('productTable');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const pageNumbers = document.getElementById('pageNumbers');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    const visibleRows = rows.filter(row => !row.classList.contains('filtered-out'));
    const totalPages = Math.max(1, Math.ceil(visibleRows.length / rowsPerPage));

    if (currentPage > totalPages) currentPage = totalPages;

    pageNumbers.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        if (i === currentPage) btn.classList.add('active');
        btn.addEventListener('click', () => {
            currentPage = i;
            updatePagination();
        });
        pageNumbers.appendChild(btn);
    }

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;

    const startIdx = (currentPage - 1) * rowsPerPage;
    const endIdx = startIdx + rowsPerPage;

    rows.forEach(row => {
        if (row.classList.contains('filtered-out')) {
            row.style.display = 'none';
        } else {
            const index = visibleRows.indexOf(row);
            row.style.display = index >= startIdx && index < endIdx ? '' : 'none';
        }
    });

    table.style.display = visibleRows.length > 0 ? 'table' : 'none';
    updateTotals();
}

function changePage(delta) {
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const visibleRows = rows.filter(row => !row.classList.contains('filtered-out'));
    const totalPages = Math.max(1, Math.ceil(visibleRows.length / rowsPerPage));

    currentPage += delta;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;

    updatePagination();
}

function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
    currentPage = 1;
    updatePagination();
}

function updateTotals() {
    if (!totalRow || !totalProductsCountElement) {
        console.error('Total row elements not found:', { totalRow, totalProductsCountElement });
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr'));
    const visibleRows = rows.filter(row => !row.classList.contains('filtered-out'));
    totalProductsCountElement.textContent = visibleRows.length;
    totalRow.style.display = visibleRows.length > 0 ? 'table-row' : 'none';
}

if (addProductBtn) {
    addProductBtn.addEventListener('click', () => {
        if (popup) {
            popup.style.display = 'block';
            popup.style.animation = 'fadeIn 0.3s ease-in';
            const form = document.querySelector('#addProductForm');
            form.style.animation = 'slideDown 0.5s ease-out';
        } else {
            console.error('Add product popup not found');
        }
    });
}

if (closeBtn) {
    closeBtn.addEventListener('click', () => closePopup('add'));
}

if (imageFileInput) {
    imageFileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.innerHTML = '';
            imagePreview.style.display = 'none';
            alert('Please select a valid image file.');
        }
    });
}

if (editImageFileInput) {
    editImageFileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                editImagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                editImagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            editImagePreview.innerHTML = '';
            editImagePreview.style.display = 'none';
            alert('Please select a valid image file.');
        }
    });
}

function editProduct(id) {
    const row = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
    if (!row) {
        console.error('Row not found for ID:', id);
        return;
    }

    const cells = row.cells;
    document.getElementById('editProductId').value = id;
    document.getElementById('editProductName').value = cells[0].textContent;
    document.getElementById('editCategory').value = cells[1].textContent;
    document.getElementById('editPrice').value = parseFloat(cells[2].textContent);
    document.getElementById('editUnit').value = cells[3].textContent;
    document.getElementById('editSpecialInstructions').value = cells[5].textContent === '—' ? '' : cells[5].textContent;
    
    // Show existing image in preview
    const img = cells[4].querySelector('img');
    editImagePreview.innerHTML = img ? `<img src="${img.src}" alt="Current Image">` : '';
    editImagePreview.style.display = img ? 'block' : 'none';

    if (editProductPopup) {
        editProductPopup.style.display = 'block';
        editProductPopup.style.animation = 'fadeIn 0.3s ease-in';
        const form = document.querySelector('#editProductForm');
        form.style.animation = 'slideDown 0.5s ease-out';
    } else {
        console.error('Edit product popup not found');
    }
}

if (editCloseBtn) {
    editCloseBtn.addEventListener('click', () => closePopup('edit'));
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'product-list.php';
        form.style.display = 'none';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleteProduct';
        input.value = '1';
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'productId';
        idInput.value = id;
        form.appendChild(input);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    filterTable();
    updatePagination();
});