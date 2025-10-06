function toggleView(view) {
    const table = document.getElementById('productTable');
    if (view === 'list') {
        table.style.display = 'table';
        // Switch to server-side pagination
        updatePagination();
    }
}

// Global variables
let searchTerm = '';
let filters = ['all']; // legacy; server-side filters are collected from UI groups
let inventory = []; // In-memory inventory array (seeded from DOM, kept in sync with DB)
let offers = []; // In-memory offers array (for quick lookup if needed)
const API_URL = '../warehouse information/warehouse-info-api.php';

async function api(action, payload) {
    const res = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: payload ? JSON.stringify(payload) : undefined
    });
    const data = await res.json().catch(() => ({ ok: false, error: 'Invalid JSON' }));
    if (!data.ok) throw new Error(data.error || 'Request failed');
    return data;
}

async function refreshMetrics() {
    try {
        const { metrics } = await api('metrics');
        if (metrics) {
            if (totalCapacityElement) totalCapacityElement.textContent = `${metrics.totalCapacity} units`;
            if (freeCapacityElement) freeCapacityElement.textContent = `${metrics.freeCapacity} units`;
            if (itemCountElement) itemCountElement.textContent = metrics.itemCount;
        }
    } catch (e) {
        console.error('Failed to refresh metrics:', e.message);
    }
}

// Filter and Search Elements
const searchInput = document.getElementById('searchInput');
const addProductBtn = document.getElementById('addProductBtn');
const popup = document.getElementById('addProductPopup');
const closeBtn = document.querySelector('.close-btn');
const addProductForm = document.getElementById('addProductForm');
const addProductCodeSelect = document.getElementById('productCodeSelect');
const addProductNameSelect = document.getElementById('productNameSelect');
const addSpecialInstructions = document.getElementById('specialInstructions');
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
const createOfferBtn = document.getElementById('createOfferBtn');
const offerPopup = document.getElementById('offerPopup');
const offerForm = document.getElementById('offerForm');
const offerCloseBtn = document.querySelector('#offerPopup .close-btn');

// Reusable function to show success message with error handling
function showSuccessMessage(message) {
    if (!successMessage || !successText) {
        console.error('Success message elements not found:', { successMessage, successText });
        return;
    }
    console.log('Showing success message:', message);
    successText.textContent = message;
    successMessage.classList.add('show');
    // start Also show a blocking alert as explicitly requested
    try {
        alert(message);
    } catch (e) {
        console.warn('Alert failed:', e);
    }
    //end
    setTimeout(() => {
        console.log('Hiding success message');
        successMessage.classList.remove('show');
    }, 2000);
}

// Function to close popups with animation
function closePopup(popupType = 'add') {
    const popupElement = popupType === 'edit' ? editProductPopup : popupType === 'offer' ? offerPopup : popup;
    const formElement = popupType === 'edit' ? editProductForm : popupType === 'offer' ? offerForm : addProductForm;
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
    // When all are selected, treat as no filter and reload from server
    currentPage = 1;
    loadList(1);
    document.getElementById('dropdownMenu')?.classList.remove('show');
}

function applyFilters() {
    // Server-side: collect filters per group and reload
    currentPage = 1;
    loadList(1);
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
        searchTerm = searchInput.value;
        currentPage = 1;
        loadList(1);
    }, 300);
}

// Event Listeners
if (searchInput) {
    searchInput.addEventListener('input', applyFiltersDebounced);
} else {
    console.error('Search input not found');
}

// Client-side filtering replaced by server-side list API
function filterTable() { /* no-op (server-side) */ }

// Helpers to collect filters by group title
function getGroupCheckedValues(groupTitle) {
    const groups = document.querySelectorAll('#dropdownMenu .group');
    for (const g of groups) {
        const title = g.querySelector('.group-title')?.textContent?.trim().toLowerCase();
        if (title === groupTitle.toLowerCase()) {
            const checked = Array.from(g.querySelectorAll('input.filter-option:checked'))
                .map(cb => cb.value)
                .filter(v => v.toLowerCase() !== 'all');
            // If all options (excluding 'all') are selected, treat as no filter
            const totalOptions = Array.from(g.querySelectorAll('input.filter-option'))
                .map(cb => cb.value)
                .filter(v => v.toLowerCase() !== 'all');
            if (checked.length === 0 || checked.length === totalOptions.length) return [];
            return checked;
        }
    }
    return [];
}

function collectFiltersFromUI() {
    return {
        statuses: getGroupCheckedValues('Status'),
        capacities: getGroupCheckedValues('Capacity'),
        warehouses: getGroupCheckedValues('Warehouse'),
        units: getGroupCheckedValues('Unit')
    };
}

let currentPage = 1;
let rowsPerPage = 5;
let lastPagination = { page: 1, pageSize: rowsPerPage, total: 0, totalPages: 1 };
let lastTotals = { totalItems: 0, totalQuantity: 0 };

function buildRowHtml(row) {
    const lowCls = row.quantity < 10 ? 'low-stock' : '';
    const statusCls = row.status.toLowerCase() === 'completed' ? 'completed' : 'in-progress';
    return `
        <tr data-id="${row.id}" data-warehouse-id="${row.warehouse_id}" data-product-id="${row.product_id}" data-unit="${row.product_unit || ''}" data-offer="${row.offer_text || 'No Offer'}">
            <td>${row.product_code}</td>
            <td>${row.product_name}</td>
            <td>${row.special_instructions || '—'}</td>
            <td class="${lowCls}">${row.quantity}</td>
            <td>${row.unit_volume ?? ''}</td>
            <td class="${statusCls}">${row.status}</td>
            <td>${row.warehouse_name}</td>
            <td>${row.agent_id || '—'}</td>
            <td>${row.offer_text || 'No Offer'}</td>
            <td>${row.inbound_stock_date || '—'}</td>
            <td>${row.expiry_date || '—'}</td>
            <td>${row.last_updated || ''}</td>
            <td>
                <button class="action-btn edit-btn" onclick="editProduct(${row.id})">✎</button>
                <button class="action-btn delete-btn" onclick="deleteProduct(${row.id})">🗑</button>
                <button class="action-btn offer-btn" onclick="manageOffer(${row.id})">🏷️</button>
            </td>
        </tr>`;
}

function updateTotalsFromApi(totals) {
    if (!totalRow || !totalItemsElement || !totalQuantityElement) return;
    totalItemsElement.textContent = totals.totalItems ?? 0;
    totalQuantityElement.textContent = totals.totalQuantity ?? 0;
    totalRow.style.display = (totals.totalItems ?? 0) > 0 ? 'table-row' : 'none';
}

function updatePaginationControls(pagination) {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const pageNumbers = document.getElementById('pageNumbers');
    pageNumbers.innerHTML = '';
    const totalPages = Math.max(1, pagination.totalPages || 1);
    const page = Math.min(Math.max(1, pagination.page || 1), totalPages);
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        if (i === page) btn.classList.add('active');
        btn.addEventListener('click', () => {
            currentPage = i;
            loadList(currentPage);
        });
        pageNumbers.appendChild(btn);
    }
    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = page >= totalPages;
}

async function loadList(page = 1) {
    try {
        // keep rowsPerPage in sync with selector
        const rppSelect = document.getElementById('rowsPerPage');
        if (rppSelect) rowsPerPage = parseInt(rppSelect.value) || rowsPerPage;
        const { statuses, capacities, warehouses, units } = collectFiltersFromUI();
        const payload = {
            search: searchTerm || (searchInput ? searchInput.value : ''),
            statuses,
            capacities,
            warehouses,
            units,
            page,
            pageSize: rowsPerPage
        };
        const { rows, pagination, totals } = await api('list', payload);
        // Render rows
        tbody.innerHTML = '';
        (rows || []).forEach(row => {
            tbody.insertAdjacentHTML('beforeend', buildRowHtml(row));
        });
        // Update inventory cache
        inventory = (rows || []).map(row => ({
            id: row.id,
            product_code: row.product_code,
            product: row.product_name,
            special_instructions: row.special_instructions || null,
            date_added: row.inbound_stock_date || '',
            quantity: row.quantity,
            unit: row.product_unit || '',
            status: row.status,
            warehouse: row.warehouse_name,
            warehouse_id: row.warehouse_id,
            agent_id: row.agent_id || null,
            expiry_date: row.expiry_date || null,
            last_updated: row.last_updated || ''
        }));
        // Totals & pagination
        lastPagination = pagination || lastPagination;
        lastTotals = totals || lastTotals;
        updateTotalsFromApi(lastTotals);
        updatePaginationControls(lastPagination);
        // Low stock alert
        const hasLow = !!document.querySelector('#productTableBody .low-stock');
        const alertEl = document.getElementById('lowStockAlert');
        if (alertEl) alertEl.style.display = hasLow ? 'block' : 'none';
    } catch (e) {
        console.error('Failed to load list:', e.message);
    }
}

function updatePagination() {
    // Server-side pagination: just reload current page
    loadList(currentPage);
}

function changePage(delta) {
    const totalPages = lastPagination.totalPages || 1;
    let target = currentPage + delta;
    if (target < 1) target = 1;
    if (target > totalPages) target = totalPages;
    if (target !== currentPage) {
        currentPage = target;
        loadList(currentPage);
    }
}

function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
    currentPage = 1;
    loadList(1);
}

// Totals are provided by the API now
function updateTotals() { /* replaced by updateTotalsFromApi */ }

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

// Sync product code/name selects and display special instructions
function syncProductSelects(fromSelect, toSelect) {
    const val = fromSelect.value;
    if (val) toSelect.value = val;
    const opt = fromSelect.options[fromSelect.selectedIndex];
    if (opt) {
        const instr = opt.getAttribute('data-instructions') || '';
        addSpecialInstructions.value = instr;
    }
}

if (addProductCodeSelect && addProductNameSelect && addSpecialInstructions) {
    addProductCodeSelect.addEventListener('change', () => syncProductSelects(addProductCodeSelect, addProductNameSelect));
    addProductNameSelect.addEventListener('change', () => syncProductSelects(addProductNameSelect, addProductCodeSelect));
}

if (addProductForm) {
    addProductForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const productId = parseInt(addProductCodeSelect?.value || addProductNameSelect?.value || '');
        const productCode = productId ? `PRD-${String(productId).padStart(3,'0')}` : '';
        const productName = addProductNameSelect?.options[addProductNameSelect.selectedIndex]?.text || '';
        const dateAdded = document.getElementById('dateAdded')?.value; // inbound stock date
        const quantity = parseInt(document.getElementById('quantity')?.value);
        const unitVolume = parseFloat(document.getElementById('unitVolume')?.value);
        const warehouse = document.getElementById('warehouse')?.value;
        const agentId = document.getElementById('agentId')?.value || null;
        const expiryDate = document.getElementById('expiryDate')?.value || null;

        if (!productId || !dateAdded || isNaN(quantity) || quantity <= 0 || isNaN(unitVolume) || unitVolume < 0 || !warehouse) {
            alert('Please fill all required fields. Quantity > 0 and Unit Volume >= 0.');
            return;
        }

        try {
            const payload = {
                productId,
                dateAdded,
                quantity,
                unitVolume,
                status: document.getElementById('status')?.value,
                warehouseId: parseInt(warehouse),
                agentId: agentId ? parseInt(agentId) : null,
                expiryDate,
                specialInstructions: addSpecialInstructions?.value || ''
            };
            const { row, metrics } = await api('add', payload);
            // Update DOM with returned row
            tbody.insertAdjacentHTML('afterbegin', buildRowHtml(row));

            // Update local inventory for edit/delete UX
            inventory.push({
                id: row.id,
                product_code: row.product_code,
                product: row.product_name,
                special_instructions: row.special_instructions || null,
                date_added: row.inbound_stock_date || '',
                quantity: row.quantity,
                unit: row.product_unit || '',
                status: row.status,
                warehouse: row.warehouse_name,
                agent_id: row.agent_id || null,
                expiry_date: row.expiry_date || null,
                last_updated: row.last_updated || ''
            });

            if (metrics) {
                totalCapacityElement.textContent = `${metrics.totalCapacity} units`;
                freeCapacityElement.textContent = `${metrics.freeCapacity} units`;
                itemCountElement.textContent = metrics.itemCount;
            } else {
                await refreshMetrics();
            }

            showSuccessMessage('Product added successfully!');
            closePopup('add');
            // Reload current page to reflect server-side list and totals
            loadList(currentPage);
            const quantities = document.querySelectorAll('#productTableBody .low-stock');
            if (quantities.length > 0 && document.getElementById('lowStockAlert').style.display === 'none') {
                document.getElementById('lowStockAlert').style.display = 'block';
            }
        } catch (err) {
            alert('Failed to add product: ' + err.message);
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
    document.getElementById('editUnitVolume').value = (function(){
        const rowEl = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
        return rowEl ? rowEl.cells[4].textContent : '';
    })();
    document.getElementById('editStatus').value = product.status;
    // If row has data-warehouse-id, prefer it; else keep as is
    const row = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
    const whId = row ? row.getAttribute('data-warehouse-id') : null;
    document.getElementById('editWarehouse').value = whId || document.getElementById('editWarehouse').value;
    document.getElementById('editAgentId').value = product.agent_id || '';
    document.getElementById('editExpiryDate').value = product.expiry_date || '';

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
    editProductForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const productCode = document.getElementById('editProductCode')?.value.trim();
        const productName = document.getElementById('editProductName')?.value.trim();
        const dateAdded = document.getElementById('editDateAdded')?.value;
        const quantity = parseInt(document.getElementById('editQuantity')?.value);
        const unitVolume = parseFloat(document.getElementById('editUnitVolume')?.value);
        const warehouse = document.getElementById('editWarehouse')?.value;
        const agentId = document.getElementById('editAgentId')?.value || null;
        const expiryDate = document.getElementById('editExpiryDate')?.value || null;

        if (!productCode || !productName || !dateAdded || isNaN(quantity) || quantity <= 0 || isNaN(unitVolume) || unitVolume < 0 || !warehouse) {
            alert('Please fill all required fields. Quantity > 0 and Unit Volume >= 0.');
            return;
        }

        try {
            const id = parseInt(document.getElementById('editProductId')?.value);
            // productId stays the same; retrieve from row data attribute
            const rowEl = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
            const productId = rowEl ? parseInt(rowEl.getAttribute('data-product-id')) : null;
            const payload = {
                id,
                productId,
                dateAdded,
                quantity,
                unitVolume,
                status: document.getElementById('editStatus')?.value,
                warehouseId: parseInt(warehouse),
                agentId: agentId ? parseInt(agentId) : null,
                expiryDate,
                specialInstructions: document.getElementById('editSpecialInstructions')?.value || ''
            };
            const { row, metrics } = await api('edit', payload);

            const idx = inventory.findIndex(i => i.id === id);
            if (idx !== -1) {
                inventory[idx] = {
                    id,
                    product_code: row.product_code,
                    product: row.product_name,
                    special_instructions: row.special_instructions || null,
                    date_added: row.inbound_stock_date || '',
                    quantity: row.quantity,
                    unit: row.product_unit || '',
                    status: row.status,
                    warehouse: row.warehouse_name,
                    agent_id: row.agent_id || null,
                    expiry_date: row.expiry_date || null,
                    last_updated: row.last_updated || ''
                };
            }

            const trEl = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
            if (trEl) {
                trEl.setAttribute('data-warehouse-id', row.warehouse_id);
                trEl.setAttribute('data-product-id', row.product_id);
                trEl.setAttribute('data-unit', row.product_unit || '');
                trEl.cells[0].textContent = row.product_code;
                trEl.cells[1].textContent = row.product_name;
                trEl.cells[2].textContent = row.special_instructions || '—';
                trEl.cells[3].textContent = row.quantity;
                trEl.cells[3].className = row.quantity < 10 ? 'low-stock' : '';
                trEl.cells[4].textContent = row.unit_volume ?? '';
                trEl.cells[5].textContent = row.status;
                trEl.cells[5].className = row.status.toLowerCase() === 'completed' ? 'completed' : 'in-progress';
                trEl.cells[6].textContent = row.warehouse_name;
                trEl.cells[7].textContent = row.agent_id || '—';
                trEl.cells[8].textContent = row.offer_text || 'No Offer';
                trEl.setAttribute('data-offer', row.offer_text || 'No Offer');
                trEl.cells[9].textContent = row.inbound_stock_date || '—';
                trEl.cells[10].textContent = row.expiry_date || '—';
                trEl.cells[11].textContent = row.last_updated || '';
            }

            if (metrics) {
                totalCapacityElement.textContent = `${metrics.totalCapacity} units`;
                freeCapacityElement.textContent = `${metrics.freeCapacity} units`;
                itemCountElement.textContent = metrics.itemCount;
            } else {
                await refreshMetrics();
            }

            showSuccessMessage('Product updated successfully!');
            closePopup('edit');
            // Reload current list and totals
            loadList(currentPage);
            const quantities = document.querySelectorAll('#productTableBody .low-stock');
            if (quantities.length > 0 && document.getElementById('lowStockAlert').style.display === 'none') {
                document.getElementById('lowStockAlert').style.display = 'block';
            } else if (quantities.length === 0) {
                document.getElementById('lowStockAlert').style.display = 'none';
            }
        } catch (err) {
            alert('Failed to update product: ' + err.message);
        }
    });
}

async function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        try {
            const productIndex = inventory.findIndex(item => item.id === id);
            if (productIndex === -1) {
                console.error('Product not found for ID:', id);
            }
            await api('delete', { id });
            if (productIndex !== -1) inventory.splice(productIndex, 1);
            const row = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
            if (row) row.remove();

            await refreshMetrics();

            showSuccessMessage('Product deleted successfully!');
            loadList(currentPage);
            const quantities = document.querySelectorAll('#productTableBody .low-stock');
            if (quantities.length > 0 && document.getElementById('lowStockAlert').style.display === 'none') {
                document.getElementById('lowStockAlert').style.display = 'block';
            } else if (quantities.length === 0) {
                document.getElementById('lowStockAlert').style.display = 'none';
            }
        } catch (err) {
            alert('Failed to delete product: ' + err.message);
        }
    }
}

// Offer Management Functions
function populateProductsSelect() {
    const select = document.getElementById('offerProductId');
    if (!select) return;
    select.innerHTML = '';
    inventory.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.product} (${item.product_code})`;
        select.appendChild(option);
    });
}

if (createOfferBtn) {
    createOfferBtn.addEventListener('click', () => {
        if (offerPopup) {
            populateProductsSelect();
            document.getElementById('offerProductId').disabled = false;
            document.getElementById('offerProductId').value = '';
            document.getElementById('offerDiscount').value = '';
            document.getElementById('offerStartDate').value = '';
            document.getElementById('offerEndDate').value = '';
            offerPopup.style.display = 'block';
            offerPopup.style.animation = 'fadeIn 0.3s ease-in';
            const form = document.querySelector('#offerForm');
            form.style.animation = 'slideDown 0.5s ease-out';
        } else {
            console.error('Offer popup not found');
        }
    });
}

if (offerCloseBtn) {
    offerCloseBtn.addEventListener('click', () => closePopup('offer'));
}

if (offerForm) {
    offerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const productId = document.getElementById('offerProductId')?.value;
        const discount = parseInt(document.getElementById('offerDiscount')?.value);
        const startDate = document.getElementById('offerStartDate')?.value;
        const endDate = document.getElementById('offerEndDate')?.value;

        if (!productId || isNaN(discount) || discount < 0 || discount > 100 || !startDate || !endDate) {
            alert('Please select a product and enter a valid discount (0-100%) and dates.');
            return;
        }

        try {
            const { row } = await api('offer', { id: parseInt(productId), discount, startDate, endDate });
            const tr = document.querySelector(`#productTableBody tr[data-id="${productId}"]`);
            if (tr) {
                tr.cells[8].textContent = row.offer_text || 'No Offer';
                tr.setAttribute('data-offer', row.offer_text || 'No Offer');
            }
            showSuccessMessage('Offer saved successfully!');
            closePopup('offer');
        } catch (err) {
            alert('Failed to save offer: ' + err.message);
        }
    });
}

function suggestOffer(id) {
    const product = inventory.find(item => item.id === id);
    if (!product) return null;

    const today = new Date();
    const productDate = new Date(product.date_added);
    const daysOld = Math.floor((today - productDate) / (1000 * 60 * 60 * 24));
    const startDate = new Date(today);
    startDate.setDate(startDate.getDate() + 1);
    const endDate = new Date(startDate);

    if (product.quantity < 100) {
        endDate.setDate(startDate.getDate() + 15);
        return {
            discount: 15,
            startDate: startDate.toLocaleDateString('en-CA'),
            endDate: endDate.toLocaleDateString('en-CA')
        };
    }

    if (daysOld > 90) {
        endDate.setDate(startDate.getDate() + 30);
        return {
            discount: 25,
            startDate: startDate.toLocaleDateString('en-CA'),
            endDate: endDate.toLocaleDateString('en-CA')
        };
    }

    if (product.quantity >= 100 && product.quantity <= 300) {
        endDate.setDate(startDate.getDate() + 30);
        return {
            discount: 5,
            startDate: startDate.toLocaleDateString('en-CA'),
            endDate: endDate.toLocaleDateString('en-CA')
        };
    }

    if (product.status === 'Completed') {
        endDate.setDate(startDate.getDate() + 20);
        return {
            discount: 10,
            startDate: startDate.toLocaleDateString('en-CA'),
            endDate: endDate.toLocaleDateString('en-CA')
        };
    }

    return null;
}

function manageOffer(id) {
    const product = inventory.find(item => item.id === id);
    if (!product) {
        console.error('Product not found for ID:', id);
        return;
    }

    const existingOffer = offers.find(o => o.productId === id);
    const suggestedOffer = suggestOffer(id);

    if (offerPopup) {
        populateProductsSelect();
        document.getElementById('offerProductId').value = id;
        document.getElementById('offerProductId').disabled = true;
        document.getElementById('offerDiscount').value = existingOffer ? existingOffer.discount : (suggestedOffer ? suggestedOffer.discount : '');
        document.getElementById('offerStartDate').value = existingOffer ? existingOffer.startDate : (suggestedOffer ? suggestedOffer.startDate : '');
        document.getElementById('offerEndDate').value = existingOffer ? existingOffer.endDate : (suggestedOffer ? suggestedOffer.endDate : '');
        offerPopup.style.display = 'block';
        offerPopup.style.animation = 'fadeIn 0.3s ease-in';
        const form = document.querySelector('#offerForm');
        form.style.animation = 'slideDown 0.5s ease-out';
    } else {
        console.error('Offer popup not found');
    }
}

// Initialize inventory from DOM on load
document.addEventListener('DOMContentLoaded', () => {
    if (searchInput) searchInput.value = ''; // Clear search input
    // Load first page from server to ensure filters/pagination are consistent
    loadList(1);

    if (!successMessage || !successText) {
        console.error('Success message elements not found on load:', { successMessage, successText });
    }
});