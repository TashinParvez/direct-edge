function toggleView(view) {
    const table = document.getElementById('productTable');
    if (view === 'list') {
        table.style.display = 'table';
        updatePagination();
    }
}

// Global variables
let searchTerm = '';
let filters = ['all']; // Combined array for all filter types
let inventory = []; // In-memory inventory array

// Filter and Search Elements
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
const totalCapacityElement = document.getElementById('totalCapacity');
const freeCapacityElement = document.getElementById('freeCapacity');
const itemCountElement = document.getElementById('itemCount');
const tbody = document.getElementById('productTableBody');
const totalRow = document.getElementById('totalRow');
const totalItemsElement = document.getElementById('totalItems');
const totalQuantityElement = document.getElementById('totalQuantity');

// Reusable function to show success message with error handling
function showSuccessMessage(message) {
    if (!successMessage || !successText) {
        console.error('Success message elements not found:', { successMessage, successText });
        return;
    }
    console.log('Showing success message:', message);
    successText.textContent = message;
    successMessage.classList.add('show');
    setTimeout(() => {
        console.log('Hiding success message');
        successMessage.classList.remove('show');
    }, 2000);
}

// Function to close popups with animation
function closePopup(popupType = 'add') {
    const popupElement = popupType === 'edit' ? editProductPopup : popup;
    const formElement = popupType === 'edit' ? editProductForm : addProductForm;
    if (popupElement) {
        popupElement.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            popupElement.style.display = 'none';
            formElement.reset();
            popupElement.style.animation = '';
        }, 300);
    } else {
        console.error('Popup element not found:', popupType);
    }
}

// Dropdown Functions
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

// Optional: close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        document.getElementById('dropdownMenu')?.classList.remove('show');
    }
});

// Debounced Search Filtering
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

// Event Listeners
if (searchInput) {
    searchInput.addEventListener('input', applyFiltersDebounced);
} else {
    console.error('Search input not found');
}

// Function to apply all filters
function filterTable() {
    const tableRows = document.querySelectorAll('#productTableBody tr');
    tableRows.forEach(row => {
        const product = row.cells[2].textContent.toLowerCase();
        const productCode = row.cells[1].textContent.toLowerCase();
        const status = row.cells[6].textContent;
        const quantity = parseInt(row.cells[5].textContent);
        const warehouse = row.cells[7].textContent;

        const searchMatch = !searchTerm || (product.includes(searchTerm) || productCode.includes(searchTerm));

        let filterMatch = filters.includes('all');
        if (!filterMatch) {
            filterMatch = filters.some(filter => {
                if (filter === 'In progress' || filter === 'Completed') {
                    return status === filter;
                } else if (filter === 'low') {
                    return quantity < 10;
                } else if (filter === 'medium') {
                    return quantity >= 10 && quantity <= 50;
                } else if (filter === 'high') {
                    return quantity > 50;
                } else if (filter === 'Warehouse A' || filter === 'Warehouse B' || filter === 'Warehouse C') {
                    return warehouse === filter;
                }
                return false;
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

// Pagination Logic
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

// Function to update totals based on filtered rows
function updateTotals() {
    if (!totalRow || !totalItemsElement || !totalQuantityElement) {
        console.error('Total row elements not found:', { totalRow, totalItemsElement, totalQuantityElement });
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr'));
    const visibleRows = rows.filter(row => !row.classList.contains('filtered-out'));

    totalItemsElement.textContent = visibleRows.length;
    totalQuantityElement.textContent = visibleRows.reduce((sum, row) => sum + parseInt(row.cells[5].textContent), 0);

    totalRow.style.display = visibleRows.length > 0 ? 'table-row' : 'none';
}

// Popup and Form Handling
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

if (addProductForm) {
    addProductForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const productCode = document.getElementById('productCode')?.value.trim();
        const productName = document.getElementById('productName')?.value.trim();
        const dateAdded = document.getElementById('dateAdded')?.value;
        const quantity = parseInt(document.getElementById('quantity')?.value);
        const unit = document.getElementById('unit')?.value.trim();
        const warehouse = document.getElementById('warehouse')?.value;

        if (!productCode || !productName || !dateAdded || isNaN(quantity) || quantity <= 0 || !unit || !warehouse) {
            alert('Please fill all required fields correctly. Quantity must be greater than 0.');
            return;
        }

        const newProduct = {
            id: Date.now(),
            product_code: productCode,
            product: productName,
            special_instructions: document.getElementById('specialInstructions')?.value || null,
            date_added: dateAdded,
            quantity: quantity,
            status: document.getElementById('status')?.value,
            unit: unit,
            warehouse: warehouse
        };

        const existingIds = inventory.map(item => item.id);
        if (existingIds.includes(newProduct.id)) {
            alert('A product with this ID already exists. Please try again.');
            return;
        }

        inventory.push(newProduct);
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-id', newProduct.id);
        newRow.innerHTML = `
            <td>${newProduct.id}</td>
            <td>${newProduct.product_code}</td>
            <td>${newProduct.product}</td>
            <td>${newProduct.special_instructions || '—'}</td>
            <td>${newProduct.date_added}</td>
            <td class="${newProduct.quantity < 10 ? 'low-stock' : ''}">${newProduct.quantity}</td>
            <td class="${newProduct.status.toLowerCase() === 'completed' ? 'completed' : 'in-progress'}">${newProduct.status}</td>
            <td>${newProduct.warehouse}</td>
            <td>
                <button class="action-btn edit-btn" onclick="editProduct(${newProduct.id})">✎</button>
                <button class="action-btn delete-btn" onclick="deleteProduct(${newProduct.id})">🗑</button>
            </td>
        `;
        tbody.appendChild(newRow);

        const totalCapacity = 10000;
        const currentItemCount = inventory.length;
        const currentFreeCapacity = parseInt(freeCapacityElement.textContent.split(' ')[0]) - newProduct.quantity;
        itemCountElement.textContent = currentItemCount;
        freeCapacityElement.textContent = `${currentFreeCapacity} units`;

        showSuccessMessage('Product added successfully!');
        closePopup('add');
        filterTable();
        updatePagination();
        const quantities = document.querySelectorAll('#productTableBody .low-stock');
        if (quantities.length > 0 && document.getElementById('lowStockAlert').style.display === 'none') {
            document.getElementById('lowStockAlert').style.display = 'block';
        }
    });
}

function editProduct(id) {
    const product = inventory.find(item => item.id === id);
    if (!product) {
        console.error('Product not found for ID:', id);
        return;
    }

    document.getElementById('editProductId').value = product.id;
    document.getElementById('editProductCode').value = product.product_code;
    document.getElementById('editProductName').value = product.product;
    document.getElementById('editSpecialInstructions').value = product.special_instructions || '';
    document.getElementById('editDateAdded').value = product.date_added;
    document.getElementById('editQuantity').value = product.quantity;
    document.getElementById('editStatus').value = product.status;
    document.getElementById('editUnit').value = product.unit;
    document.getElementById('editWarehouse').value = product.warehouse;

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

if (editProductForm) {
    editProductForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const productCode = document.getElementById('editProductCode')?.value.trim();
        const productName = document.getElementById('editProductName')?.value.trim();
        const dateAdded = document.getElementById('editDateAdded')?.value;
        const quantity = parseInt(document.getElementById('editQuantity')?.value);
        const unit = document.getElementById('editUnit')?.value.trim();
        const warehouse = document.getElementById('editWarehouse')?.value;

        if (!productCode || !productName || !dateAdded || isNaN(quantity) || quantity <= 0 || !unit || !warehouse) {
            alert('Please fill all required fields correctly. Quantity must be greater than 0.');
            return;
        }

        const id = parseInt(document.getElementById('editProductId')?.value);
        const productIndex = inventory.findIndex(item => item.id === id);
        if (productIndex === -1) {
            console.error('Product not found for ID:', id);
            return;
        }

        const oldQuantity = inventory[productIndex].quantity;
        inventory[productIndex] = {
            id: id,
            product_code: productCode,
            product: productName,
            special_instructions: document.getElementById('editSpecialInstructions')?.value || null,
            date_added: dateAdded,
            quantity: quantity,
            status: document.getElementById('editStatus')?.value,
            unit: unit,
            warehouse: warehouse
        };

        const row = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
        if (row) {
            row.cells[1].textContent = productCode;
            row.cells[2].textContent = productName;
            row.cells[3].textContent = document.getElementById('editSpecialInstructions')?.value || '—';
            row.cells[4].textContent = dateAdded;
            row.cells[5].textContent = quantity;
            row.cells[5].className = quantity < 10 ? 'low-stock' : '';
            row.cells[6].textContent = document.getElementById('editStatus')?.value;
            row.cells[6].className = document.getElementById('editStatus')?.value.toLowerCase() === 'completed' ? 'completed' : 'in-progress';
            row.cells[7].textContent = warehouse;
        } else {
            console.error('Row not found for ID:', id);
        }

        const currentFreeCapacity = parseInt(freeCapacityElement.textContent.split(' ')[0]) + (oldQuantity - quantity);
        freeCapacityElement.textContent = `${currentFreeCapacity} units`;

        showSuccessMessage('Product updated successfully!');
        closePopup('edit');
        filterTable();
        updatePagination();
        const quantities = document.querySelectorAll('#productTableBody .low-stock');
        if (quantities.length > 0 && document.getElementById('lowStockAlert').style.display === 'none') {
            document.getElementById('lowStockAlert').style.display = 'block';
        } else if (quantities.length === 0) {
            document.getElementById('lowStockAlert').style.display = 'none';
        }
    });
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        const productIndex = inventory.findIndex(item => item.id === id);
        if (productIndex === -1) {
            console.error('Product not found for ID:', id);
            return;
        }

        const quantity = inventory[productIndex].quantity;
        inventory.splice(productIndex, 1);
        const row = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
        if (row) {
            row.remove();
        } else {
            console.error('Row not found for ID:', id);
        }

        const currentItemCount = inventory.length;
        const currentFreeCapacity = parseInt(freeCapacityElement.textContent.split(' ')[0]) + quantity;
        itemCountElement.textContent = currentItemCount;
        freeCapacityElement.textContent = `${currentFreeCapacity} units`;

        showSuccessMessage('Product deleted successfully!');
        filterTable();
        updatePagination();
        const quantities = document.querySelectorAll('#productTableBody .low-stock');
        if (quantities.length > 0 && document.getElementById('lowStockAlert').style.display === 'none') {
            document.getElementById('lowStockAlert').style.display = 'block';
        } else if (quantities.length === 0) {
            document.getElementById('lowStockAlert').style.display = 'none';
        }
    }
}

// Initialize inventory from DOM on load
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('#productTableBody tr');
    inventory = Array.from(rows).map(row => {
        const cells = row.cells;
        return {
            id: parseInt(row.getAttribute('data-id')),
            product_code: cells[1].textContent,
            product: cells[2].textContent,
            special_instructions: cells[3].textContent === '—' ? null : cells[3].textContent,
            date_added: cells[4].textContent,
            quantity: parseInt(cells[5].textContent),
            status: cells[6].textContent,
            unit: cells[7].textContent,
            warehouse: cells[8].textContent
        };
    });

    filterTable();
    updatePagination();
    const quantities = document.querySelectorAll('#productTableBody .low-stock');
    if (quantities.length > 0) {
        document.getElementById('lowStockAlert').style.display = 'block';
    }

    if (!successMessage || !successText) {
        console.error('Success message elements not found on load:', { successMessage, successText });
    }
});