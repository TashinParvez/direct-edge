(function () {
  "use strict";
  console.log("warehouse-info.js loaded successfully");

  // Global variables
  let searchTerm = "";
  let filters = ["all"];
  let inventory = [];
  let offers = [];
  const API_URL = "../warehouse information/warehouse-info-api.php";

  // DOM element references (initialized in DOMContentLoaded)
  let searchInput, addProductBtn, popup, warehouseCloseBtn, addProductForm;
  let addProductCodeSelect, addProductNameSelect, addSpecialInstructions;
  let editProductPopup,
    editCloseBtn,
    editProductForm,
    successMessage,
    successText;
  let totalCapacityElement, freeCapacityElement, itemCountElement;
  let tbody, totalRow, totalItemsElement, totalQuantityElement;
  let createOfferBtn, offerPopup, offerForm, offerCloseBtn;
  let offerSuggestionPopup,
    offerSuggestionDetails,
    offerSuggestionApplyBtn,
    offerSuggestionEditBtn;

  let currentPage = 1;
  let rowsPerPage = 10;
  let lastPagination = {
    page: 1,
    pageSize: rowsPerPage,
    total: 0,
    totalPages: 1,
  };
  let lastTotals = { totalItems: 0, totalQuantity: 0 };
  let lastSuggestedForId = null;

  window.toggleView = function (view) {
    const table = document.getElementById("productTable");
    if (view === "list") {
      table.style.display = "table";
      updatePagination();
    }
  };

  const API_URL_CONST = API_URL;

  async function api(action, payload) {
    const res = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: payload ? JSON.stringify(payload) : undefined,
    });
    const data = await res
      .json()
      .catch(() => ({ ok: false, error: "Invalid JSON" }));
    if (!data.ok) throw new Error(data.error || "Request failed");
    return data;
  }

  async function refreshMetrics() {
    try {
      const { metrics } = await api("metrics");
      if (metrics) {
        if (totalCapacityElement)
          totalCapacityElement.textContent = `${metrics.totalCapacity} units`;
        if (freeCapacityElement)
          freeCapacityElement.textContent = `${metrics.freeCapacity} units`;
        if (itemCountElement) itemCountElement.textContent = metrics.itemCount;
      }
    } catch (e) {
      console.error("Failed to refresh metrics:", e.message);
    }
  }

  // Filter and Search Elements (initialized in DOMContentLoaded)

  // Reusable function to show success message with error handling
  function showSuccessMessage(message) {
    if (!successMessage || !successText) {
      console.error("Success message elements not found:", {
        successMessage,
        successText,
      });
      return;
    }
    console.log("Showing success message:", message);
    successText.textContent = message;
    successMessage.classList.add("show");
    // start Also show a blocking alert as explicitly requested
    try {
      alert(message);
    } catch (e) {
      console.warn("Alert failed:", e);
    }
    //end
    setTimeout(() => {
      console.log("Hiding success message");
      successMessage.classList.remove("show");
    }, 2000);
  }

  // Function to close popups with animation
  function closePopup(popupType = "add") {
    const popupElement =
      popupType === "edit"
        ? editProductPopup
        : popupType === "offer"
        ? offerPopup
        : popupType === "offer-suggestion"
        ? offerSuggestionPopup
        : popup;
    const formElement =
      popupType === "edit"
        ? editProductForm
        : popupType === "offer"
        ? offerForm
        : addProductForm;
    if (popupElement) {
      popupElement.style.animation = "fadeOut 0.3s ease-out";
      setTimeout(() => {
        popupElement.style.display = "none";
        if (formElement && popupType !== "offer-suggestion")
          formElement.reset();
        popupElement.style.animation = "";
      }, 300);
    } else {
      console.error("Popup element not found:", popupType);
    }
  }
  window.closePopup = closePopup;

  // Dropdown Functions
  function toggleDropdown() {
    const menu = document.getElementById("dropdownMenu");
    if (menu) {
      menu.classList.toggle("show");
    } else {
      console.error("Dropdown menu not found");
    }
  }
  window.toggleDropdown = toggleDropdown;

  function selectAll() {
    const checkboxes = document.querySelectorAll(".filter-option");
    checkboxes.forEach((cb) => (cb.checked = true));
    currentPage = 1;
    loadList(1);
    document.getElementById("dropdownMenu")?.classList.remove("show");
  }
  window.selectAll = selectAll;

  function applyFilters() {
    currentPage = 1;
    loadList(1);
    document.getElementById("dropdownMenu")?.classList.remove("show");
  }
  window.applyFilters = applyFilters;

  // Optional: close dropdown when clicking outside
  document.addEventListener("click", function (e) {
    const dropdown = document.querySelector(".dropdown");
    if (dropdown && !dropdown.contains(e.target)) {
      document.getElementById("dropdownMenu")?.classList.remove("show");
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
    searchInput.addEventListener("input", applyFiltersDebounced);
  } else {
    console.error("Search input not found");
  }

  // Client-side filtering replaced by server-side list API
  function filterTable() {
    /* no-op (server-side) */
  }

  // Helpers to collect filters by group title
  function getGroupCheckedValues(groupTitle) {
    const groups = document.querySelectorAll("#dropdownMenu .group");
    for (const g of groups) {
      const title = g
        .querySelector(".group-title")
        ?.textContent?.trim()
        .toLowerCase();
      if (title === groupTitle.toLowerCase()) {
        const checked = Array.from(
          g.querySelectorAll("input.filter-option:checked")
        )
          .map((cb) => cb.value)
          .filter((v) => v.toLowerCase() !== "all");
        // If all options (excluding 'all') are selected, treat as no filter
        const totalOptions = Array.from(
          g.querySelectorAll("input.filter-option")
        )
          .map((cb) => cb.value)
          .filter((v) => v.toLowerCase() !== "all");
        if (checked.length === 0 || checked.length === totalOptions.length)
          return [];
        return checked;
      }
    }
    return [];
  }

  function collectFiltersFromUI() {
    return {
      statuses: getGroupCheckedValues("Status"),
      capacities: getGroupCheckedValues("Capacity"),
      warehouses: getGroupCheckedValues("Warehouse"),
      units: getGroupCheckedValues("Unit"),
    };
  }

  function buildRowHtml(row) {
    const lowCls = row.quantity < 10 ? "low-stock" : "";
    const statusCls =
      row.status.toLowerCase() === "completed" ? "completed" : "in-progress";
    return `
        <tr data-id="${row.id}" data-warehouse-id="${
      row.warehouse_id
    }" data-product-id="${row.product_id}" data-unit="${
      row.product_unit || ""
    }" data-offer="${row.offer_text || "No Offer"}">
            <td>${row.product_code}</td>
            <td>${row.product_name}</td>
            <td>${row.special_instructions || "—"}</td>
            <td class="${lowCls}">${row.quantity}</td>
            <td>${row.unit_volume ?? ""}</td>
            <td class="${statusCls}">${row.status}</td>
            <td>${row.warehouse_name}</td>
            <td>${row.free_space}</td>
            <td>${row.agent_id || "—"}</td>
            <td><button class="offer-suggestion-link" title="View offer suggestion">${
              row.offer_text || "No Offer"
            }</button></td>
            <td>${row.inbound_stock_date || "—"}</td>
            <td>${row.expiry_date || "—"}</td>
            <td>${row.last_updated || ""}</td>
            <td>
                <button class="action-btn edit-btn" onclick="editProduct(${
                  row.id
                })">✎</button>
                <button class="action-btn delete-btn" onclick="deleteProduct(${
                  row.id
                })">🗑</button>
                <button class="action-btn offer-btn" onclick="manageOffer(${
                  row.id
                })">🏷️</button>
            </td>
        </tr>`;
  }

  function updateTotalsFromApi(totals) {
    if (!totalRow || !totalItemsElement || !totalQuantityElement) return;
    totalItemsElement.textContent = totals.totalItems ?? 0;
    totalQuantityElement.textContent = totals.totalQuantity ?? 0;
    totalRow.style.display =
      (totals.totalItems ?? 0) > 0 ? "table-row" : "none";
  }

  function updatePaginationControls(pagination) {
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const pageNumbers = document.getElementById("pageNumbers");
    pageNumbers.innerHTML = "";
    const totalPages = Math.max(1, pagination.totalPages || 1);
    const page = Math.min(Math.max(1, pagination.page || 1), totalPages);
    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      if (i === page) btn.classList.add("active");
      btn.addEventListener("click", () => {
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
      const rppSelect = document.getElementById("rowsPerPage");
      if (rppSelect) rowsPerPage = parseInt(rppSelect.value) || rowsPerPage;
      const { statuses, capacities, warehouses, units } =
        collectFiltersFromUI();
      const payload = {
        search: searchTerm || (searchInput ? searchInput.value : ""),
        statuses,
        capacities,
        warehouses,
        units,
        page,
        // If rowsPerPage is 0, request 'all' by sending a large pageSize
        pageSize: rowsPerPage === 0 ? 1000000 : rowsPerPage,
      };
      const { rows, pagination, totals } = await api("list", payload);
      // Render rows
      tbody.innerHTML = "";
      (rows || []).forEach((row) => {
        tbody.insertAdjacentHTML("beforeend", buildRowHtml(row));
      });
      // Update inventory cache
      inventory = (rows || []).map((row) => ({
        id: row.id,
        product_code: row.product_code,
        product: row.product_name,
        special_instructions: row.special_instructions || null,
        date_added: row.inbound_stock_date || "",
        quantity: row.quantity,
        unit: row.product_unit || "",
        status: row.status,
        warehouse: row.warehouse_name,
        warehouse_id: row.warehouse_id,
        agent_id: row.agent_id || null,
        expiry_date: row.expiry_date || null,
        last_updated: row.last_updated || "",
      }));
      // Totals & pagination
      lastPagination = pagination || lastPagination;
      lastTotals = totals || lastTotals;
      updateTotalsFromApi(lastTotals);
      updatePaginationControls(lastPagination);
      // Low stock alert
      const hasLow = !!document.querySelector("#productTableBody .low-stock");
      const alertEl = document.getElementById("lowStockAlert");
      if (alertEl) alertEl.style.display = hasLow ? "block" : "none";
    } catch (e) {
      console.error("Failed to load list:", e.message);
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
  window.changePage = changePage;

  function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
    currentPage = 1;
    loadList(1);
  }
  window.changeRowsPerPage = changeRowsPerPage;

  // Totals are provided by the API now
  function updateTotals() {
    /* replaced by updateTotalsFromApi */
  }

  // Sync product code/name selects and display special instructions
  function syncProductSelects(fromSelect, toSelect) {
    const val = fromSelect.value;
    if (val) toSelect.value = val;
    const opt = fromSelect.options[fromSelect.selectedIndex];
    if (opt) {
      const instr = opt.getAttribute("data-instructions") || "";
      if (addSpecialInstructions) addSpecialInstructions.value = instr;
    }
  }

  // Form submit handlers for adding products
  async function handleAddProductSubmit(e) {
    e.preventDefault();

    const productId = parseInt(
      addProductCodeSelect?.value || addProductNameSelect?.value || ""
    );
    const productCode = productId
      ? `PRD-${String(productId).padStart(3, "0")}`
      : "";
    const productName =
      addProductNameSelect?.options[addProductNameSelect.selectedIndex]?.text ||
      "";
    const dateAdded = document.getElementById("dateAdded")?.value; // inbound stock date
    const quantity = parseInt(document.getElementById("quantity")?.value);
    const unitVolume = parseFloat(document.getElementById("unitVolume")?.value);
    const warehouse = document.getElementById("warehouse")?.value;
    const agentId = document.getElementById("agentId")?.value || null;
    const expiryDate = document.getElementById("expiryDate")?.value || null;

    if (
      !productId ||
      !dateAdded ||
      isNaN(quantity) ||
      quantity <= 0 ||
      isNaN(unitVolume) ||
      unitVolume < 0 ||
      !warehouse
    ) {
      alert(
        "Please fill all required fields. Quantity > 0 and Unit Volume >= 0."
      );
      return;
    }

    try {
      const payload = {
        productId,
        dateAdded,
        quantity,
        unitVolume,
        status: document.getElementById("status")?.value,
        warehouseId: parseInt(warehouse),
        agentId: agentId ? parseInt(agentId) : null,
        expiryDate,
        specialInstructions: addSpecialInstructions?.value || "",
      };
      const { row, metrics } = await api("add", payload);
      // Update DOM with returned row
      tbody.insertAdjacentHTML("afterbegin", buildRowHtml(row));

      // Update local inventory for edit/delete UX
      inventory.push({
        id: row.id,
        product_code: row.product_code,
        product: row.product_name,
        special_instructions: row.special_instructions || null,
        date_added: row.inbound_stock_date || "",
        quantity: row.quantity,
        unit: row.product_unit || "",
        status: row.status,
        warehouse: row.warehouse_name,
        agent_id: row.agent_id || null,
        expiry_date: row.expiry_date || null,
        last_updated: row.last_updated || "",
      });

      if (metrics) {
        totalCapacityElement.textContent = `${metrics.totalCapacity} units`;
        freeCapacityElement.textContent = `${metrics.freeCapacity} units`;
        itemCountElement.textContent = metrics.itemCount;
      } else {
        await refreshMetrics();
      }

      showSuccessMessage("Product added successfully!");
      closePopup("add");
      // Reload current page to reflect server-side list and totals
      loadList(currentPage);
      const quantities = document.querySelectorAll(
        "#productTableBody .low-stock"
      );
      if (
        quantities.length > 0 &&
        document.getElementById("lowStockAlert").style.display === "none"
      ) {
        document.getElementById("lowStockAlert").style.display = "block";
      }
    } catch (err) {
      alert("Failed to add product: " + err.message);
    }
  }

  function editProduct(id) {
    const product = inventory.find((item) => item.id === id);
    if (!product) {
      console.error("Product not found for ID:", id);
      return;
    }

    document.getElementById("editProductId").value = product.id;
    document.getElementById("editProductCode").value = product.product_code;
    document.getElementById("editProductName").value = product.product;
    document.getElementById("editSpecialInstructions").value =
      product.special_instructions || "";
    document.getElementById("editDateAdded").value = product.date_added;
    document.getElementById("editQuantity").value = product.quantity;
    document.getElementById("editUnitVolume").value = (function () {
      const rowEl = document.querySelector(
        `#productTableBody tr[data-id="${id}"]`
      );
      return rowEl ? rowEl.cells[4].textContent : "";
    })();
    document.getElementById("editStatus").value = product.status;
    // If row has data-warehouse-id, prefer it; else keep as is
    const row = document.querySelector(`#productTableBody tr[data-id="${id}"]`);
    const whId = row ? row.getAttribute("data-warehouse-id") : null;
    document.getElementById("editWarehouse").value =
      whId || document.getElementById("editWarehouse").value;
    document.getElementById("editAgentId").value = product.agent_id || "";
    document.getElementById("editExpiryDate").value = product.expiry_date || "";

    if (editProductPopup) {
      editProductPopup.style.display = "block";
      editProductPopup.style.animation = "fadeIn 0.3s ease-in";
      const form = document.querySelector("#editProductForm");
      form.style.animation = "slideDown 0.5s ease-out";
    } else {
      console.error("Edit product popup not found");
    }
  }
  window.editProduct = editProduct;

  // Form submit handler for editing products
  async function handleEditProductSubmit(e) {
    e.preventDefault();

    const productCode = document
      .getElementById("editProductCode")
      ?.value.trim();
    const productName = document
      .getElementById("editProductName")
      ?.value.trim();
    const dateAdded = document.getElementById("editDateAdded")?.value;
    const quantity = parseInt(document.getElementById("editQuantity")?.value);
    const unitVolume = parseFloat(
      document.getElementById("editUnitVolume")?.value
    );
    const warehouse = document.getElementById("editWarehouse")?.value;
    const agentId = document.getElementById("editAgentId")?.value || null;
    const expiryDate = document.getElementById("editExpiryDate")?.value || null;

    if (
      !productCode ||
      !productName ||
      !dateAdded ||
      isNaN(quantity) ||
      quantity <= 0 ||
      isNaN(unitVolume) ||
      unitVolume < 0 ||
      !warehouse
    ) {
      alert(
        "Please fill all required fields. Quantity > 0 and Unit Volume >= 0."
      );
      return;
    }

    try {
      const id = parseInt(document.getElementById("editProductId")?.value);
      // productId stays the same; retrieve from row data attribute
      const rowEl = document.querySelector(
        `#productTableBody tr[data-id="${id}"]`
      );
      const productId = rowEl
        ? parseInt(rowEl.getAttribute("data-product-id"))
        : null;
      const payload = {
        id,
        productId,
        dateAdded,
        quantity,
        unitVolume,
        status: document.getElementById("editStatus")?.value,
        warehouseId: parseInt(warehouse),
        agentId: agentId ? parseInt(agentId) : null,
        expiryDate,
        specialInstructions:
          document.getElementById("editSpecialInstructions")?.value || "",
      };
      const { row, metrics } = await api("edit", payload);

      const idx = inventory.findIndex((i) => i.id === id);
      if (idx !== -1) {
        inventory[idx] = {
          id,
          product_code: row.product_code,
          product: row.product_name,
          special_instructions: row.special_instructions || null,
          date_added: row.inbound_stock_date || "",
          quantity: row.quantity,
          unit: row.product_unit || "",
          status: row.status,
          warehouse: row.warehouse_name,
          agent_id: row.agent_id || null,
          expiry_date: row.expiry_date || null,
          last_updated: row.last_updated || "",
        };
      }

      const trEl = document.querySelector(
        `#productTableBody tr[data-id="${id}"]`
      );
      if (trEl) {
        trEl.setAttribute("data-warehouse-id", row.warehouse_id);
        trEl.setAttribute("data-product-id", row.product_id);
        trEl.setAttribute("data-unit", row.product_unit || "");
        trEl.cells[0].textContent = row.product_code;
        trEl.cells[1].textContent = row.product_name;
        trEl.cells[2].textContent = row.special_instructions || "—";
        trEl.cells[3].textContent = row.quantity;
        trEl.cells[3].className = row.quantity < 10 ? "low-stock" : "";
        trEl.cells[4].textContent = row.unit_volume ?? "";
        trEl.cells[5].textContent = row.status;
        trEl.cells[5].className =
          row.status.toLowerCase() === "completed"
            ? "completed"
            : "in-progress";
        trEl.cells[6].textContent = row.warehouse_name;
        trEl.cells[7].textContent = row.free_space;
        trEl.cells[8].textContent = row.agent_id || "—";
        const offerBtn = trEl.cells[9].querySelector(
          "button.offer-suggestion-link"
        );
        if (offerBtn) {
          offerBtn.textContent = row.offer_text || "No Offer";
        } else {
          trEl.cells[9].innerHTML = `<button class="offer-suggestion-link" title="View offer suggestion">${
            row.offer_text || "No Offer"
          }</button>`;
        }
        trEl.setAttribute("data-offer", row.offer_text || "No Offer");
        trEl.cells[10].textContent = row.inbound_stock_date || "—";
        trEl.cells[11].textContent = row.expiry_date || "—";
        trEl.cells[12].textContent = row.last_updated || "";
      }

      if (metrics) {
        totalCapacityElement.textContent = `${metrics.totalCapacity} units`;
        freeCapacityElement.textContent = `${metrics.freeCapacity} units`;
        itemCountElement.textContent = metrics.itemCount;
      } else {
        await refreshMetrics();
      }

      showSuccessMessage("Product updated successfully!");
      closePopup("edit");
      // Reload current list and totals
      loadList(currentPage);
      const quantities = document.querySelectorAll(
        "#productTableBody .low-stock"
      );
      if (
        quantities.length > 0 &&
        document.getElementById("lowStockAlert").style.display === "none"
      ) {
        document.getElementById("lowStockAlert").style.display = "block";
      } else if (quantities.length === 0) {
        document.getElementById("lowStockAlert").style.display = "none";
      }
    } catch (err) {
      alert("Failed to update product: " + err.message);
    }
  }

  // Form submit handler for offers
  async function handleOfferFormSubmit(e) {
    e.preventDefault();

    const productId = parseInt(
      document.getElementById("offerProductId")?.value
    );
    const discount = parseFloat(
      document.getElementById("offerDiscount")?.value
    );
    const startDate = document.getElementById("offerStartDate")?.value;
    const endDate = document.getElementById("offerEndDate")?.value;

    if (
      isNaN(productId) ||
      isNaN(discount) ||
      discount < 0 ||
      discount > 100 ||
      !startDate ||
      !endDate
    ) {
      alert("Please fill all fields. Discount must be between 0-100.");
      return;
    }

    try {
      const { row } = await api("offer", {
        id: productId,
        discount,
        startDate,
        endDate,
      });

      const tr = document.querySelector(
        `#productTableBody tr[data-id="${productId}"]`
      );
      if (tr) {
        const offerBtn = tr.cells[9]?.querySelector(
          "button.offer-suggestion-link"
        );
        if (offerBtn) {
          offerBtn.textContent = row.offer_text || "No Offer";
        } else if (tr.cells[9]) {
          tr.cells[9].innerHTML = `<button class="offer-suggestion-link" title="View offer suggestion">${
            row.offer_text || "No Offer"
          }</button>`;
        }
        tr.setAttribute("data-offer", row.offer_text || "No Offer");
      }

      showSuccessMessage("Offer saved successfully!");
      closePopup("offer");
    } catch (err) {
      alert("Failed to save offer: " + err.message);
    }
  }

  async function deleteProduct(id) {
    if (confirm("Are you sure you want to delete this product?")) {
      try {
        const productIndex = inventory.findIndex((item) => item.id === id);
        if (productIndex === -1) {
          console.error("Product not found for ID:", id);
        }
        await api("delete", { id });
        if (productIndex !== -1) inventory.splice(productIndex, 1);
        const row = document.querySelector(
          `#productTableBody tr[data-id="${id}"]`
        );
        if (row) row.remove();

        await refreshMetrics();

        showSuccessMessage("Product deleted successfully!");
        loadList(currentPage);
        const quantities = document.querySelectorAll(
          "#productTableBody .low-stock"
        );
        if (
          quantities.length > 0 &&
          document.getElementById("lowStockAlert").style.display === "none"
        ) {
          document.getElementById("lowStockAlert").style.display = "block";
        } else if (quantities.length === 0) {
          document.getElementById("lowStockAlert").style.display = "none";
        }
      } catch (err) {
        alert("Failed to delete product: " + err.message);
      }
    }
  }
  window.deleteProduct = deleteProduct;

  // Offer Management Functions
  function populateProductsSelect() {
    const select = document.getElementById("offerProductId");
    if (!select) return;
    select.innerHTML = "";
    inventory.forEach((item) => {
      const option = document.createElement("option");
      option.value = item.id;
      option.textContent = `${item.product} (${item.product_code})`;
      select.appendChild(option);
    });
  }

  function suggestOffer(id) {
    const product = inventory.find((item) => item.id === id);
    if (!product) return null;
    // Helper to format date as YYYY-MM-DD
    const fmt = (d) => {
      try {
        return d.toLocaleDateString("en-CA");
      } catch (e) {
        return new Date(d).toISOString().slice(0, 10);
      }
    };

    const today = new Date();
    const productDate = product.date_added
      ? new Date(product.date_added)
      : null;
    const daysOld =
      productDate && !isNaN(productDate.getTime())
        ? Math.floor((today - productDate) / (1000 * 60 * 60 * 24))
        : 0;
    const startDate = new Date(today);
    startDate.setDate(startDate.getDate() + 1);

    // expiry handling (highest priority) - urgent clearance if expiry within 14 days
    if (product.expiry_date) {
      const expiry = new Date(product.expiry_date);
      if (!isNaN(expiry.getTime())) {
        const daysToExpiry = Math.ceil(
          (expiry - today) / (1000 * 60 * 60 * 24)
        );
        if (daysToExpiry <= 14 && daysToExpiry >= 0) {
          // Offer until expiry (urgent)
          return {
            discount: 40,
            startDate: fmt(startDate),
            endDate: fmt(expiry),
          };
        }
      }
    }

    // Very old stock (long ageing) - deeper discount (higher priority than the 90-day rule)
    if (daysOld > 180) {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 45);
      return {
        discount: 35,
        startDate: fmt(startDate),
        endDate: fmt(endDate),
      };
    }

    // Extremely high stock - bulk clearance
    if (product.quantity >= 1000) {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 30);
      return {
        discount: 30,
        startDate: fmt(startDate),
        endDate: fmt(endDate),
      };
    }

    // Small stock: promote quick sale
    if (product.quantity < 100) {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 15);
      return {
        discount: 15,
        startDate: fmt(startDate),
        endDate: fmt(endDate),
      };
    }

    // Mid-high stock tiers
    if (product.quantity >= 301 && product.quantity <= 999) {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 20);
      return {
        discount: 8,
        startDate: fmt(startDate),
        endDate: fmt(endDate),
      };
    }

    // Original medium-stock rule
    if (product.quantity >= 100 && product.quantity <= 300) {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 30);
      return {
        discount: 5,
        startDate: fmt(startDate),
        endDate: fmt(endDate),
      };
    }

    // In-progress items with high quantity (prefer a modest discount to move stock)
    if (product.status === "In progress" && product.quantity > 500) {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 20);
      return {
        discount: 12,
        startDate: fmt(startDate),
        endDate: fmt(endDate),
      };
    }

    // Completed status fallback
    if (product.status === "Completed") {
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + 20);
      return {
        discount: 10,
        startDate: fmt(startDate),
        endDate: fmt(endDate),
      };
    }

    return null;
  }

  function manageOffer(id) {
    const product = inventory.find((item) => item.id === id);
    if (!product) {
      console.error("Product not found for ID:", id);
      return;
    }

    // Try to locate an existing offer from in-memory offers array
    let existingOffer = offers.find((o) => o.productId === id);
    const suggestedOffer = suggestOffer(id);

    // If not present in memory, attempt to parse the current offer text from the DOM row
    if (!existingOffer) {
      const tr = document.querySelector(
        `#productTableBody tr[data-id="${id}"]`
      );
      if (tr) {
        // data-offer attribute contains the display text like "15% (2025-10-07 to 2025-10-22)" or "No Offer"
        const offerText =
          tr.getAttribute("data-offer") ||
          (tr.cells[8] ? tr.cells[8].textContent.trim() : "");
        const m = offerText.match(
          /(\d+(?:\.\d+)?)%(?: \((\d{4}-\d{2}-\d{2}) to (\d{4}-\d{2}-\d{2})\))?/
        );
        if (m) {
          existingOffer = {
            productId: id,
            discount: parseFloat(m[1]),
            startDate: m[2] || "",
            endDate: m[3] || "",
          };
        }
      }
    }

    if (offerPopup) {
      populateProductsSelect();
      document.getElementById("offerProductId").value = id;
      document.getElementById("offerProductId").disabled = true;
      document.getElementById("offerDiscount").value = existingOffer
        ? existingOffer.discount
        : suggestedOffer
        ? suggestedOffer.discount
        : "";
      document.getElementById("offerStartDate").value = existingOffer
        ? existingOffer.startDate
        : suggestedOffer
        ? suggestedOffer.startDate
        : "";
      document.getElementById("offerEndDate").value = existingOffer
        ? existingOffer.endDate
        : suggestedOffer
        ? suggestedOffer.endDate
        : "";
      offerPopup.style.display = "block";
      offerPopup.style.animation = "fadeIn 0.3s ease-in";
      const form = document.querySelector("#offerForm");
      form.style.animation = "slideDown 0.5s ease-out";
    } else {
      console.error("Offer popup not found");
    }
  }
  window.manageOffer = manageOffer;

  // Offer Suggestion Modal Handlers
  function showOfferSuggestion(id) {
    const product = inventory.find((item) => item.id === id);
    if (!product) {
      console.error("Product not found for ID:", id);
      return;
    }
    lastSuggestedForId = id;
    const suggested = suggestOffer(id);
    const currentOfferText =
      document
        .querySelector(`#productTableBody tr[data-id="${id}"]`)
        ?.getAttribute("data-offer") || "No Offer";
    const html = `
        <div class="suggestion-body">
            <p><strong>Product:</strong> ${product.product} (${
      product.product_code
    })</p>
            <p><strong>Current Offer:</strong> ${currentOfferText}</p>
            ${
              suggested
                ? `
                <p><strong>Suggested Discount:</strong> ${suggested.discount}%</p>
                <p><strong>Suggested Start:</strong> ${suggested.startDate}</p>
                <p><strong>Suggested End:</strong> ${suggested.endDate}</p>
            `
                : "<p>No suggestion available for this product.</p>"
            }
        </div>
    `;
    if (offerSuggestionDetails) offerSuggestionDetails.innerHTML = html;
    if (offerSuggestionPopup) {
      offerSuggestionPopup.style.display = "block";
      offerSuggestionPopup.style.animation = "fadeIn 0.3s ease-in";
    }
    // Wire buttons
    if (offerSuggestionApplyBtn) {
      offerSuggestionApplyBtn.disabled = !suggested;
      offerSuggestionApplyBtn.onclick = async () => {
        if (!suggested) return;
        try {
          const { row } = await api("offer", {
            id,
            discount: suggested.discount,
            startDate: suggested.startDate,
            endDate: suggested.endDate,
          });
          const tr = document.querySelector(
            `#productTableBody tr[data-id="${id}"]`
          );
          if (tr) {
            tr.cells[8].textContent = row.offer_text || "No Offer";
            tr.setAttribute("data-offer", row.offer_text || "No Offer");
          }
          showSuccessMessage("Offer suggestion applied!");
          closePopup("offer-suggestion");
        } catch (err) {
          alert("Failed to apply suggestion: " + err.message);
        }
      };
    }
    if (offerSuggestionEditBtn) {
      offerSuggestionEditBtn.onclick = () => {
        // open the regular offer form pre-filled
        const suggestedOffer = suggestOffer(id);
        if (offerPopup) {
          populateProductsSelect();
          document.getElementById("offerProductId").value = id;
          document.getElementById("offerProductId").disabled = true;
          document.getElementById("offerDiscount").value = suggestedOffer
            ? suggestedOffer.discount
            : "";
          document.getElementById("offerStartDate").value = suggestedOffer
            ? suggestedOffer.startDate
            : "";
          document.getElementById("offerEndDate").value = suggestedOffer
            ? suggestedOffer.endDate
            : "";
          offerPopup.style.display = "block";
          offerPopup.style.animation = "fadeIn 0.3s ease-in";
        }
        closePopup("offer-suggestion");
      };
    }
  }

  // Make offer suggestion cell clickable even if buttons are re-rendered by server
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("button.offer-suggestion-link");
    if (btn) {
      const tr = btn.closest("tr");
      const id = tr ? parseInt(tr.getAttribute("data-id")) : null;
      if (id) showOfferSuggestion(id);
    }
  });

  // Initialize on DOM ready
  document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM Content Loaded - Initializing warehouse-info...");

    // Initialize all DOM element references
    searchInput = document.getElementById("searchInput");
    addProductBtn = document.getElementById("addProductBtn");
    popup = document.getElementById("addProductPopup");
    warehouseCloseBtn = document.querySelector(".close-btn");
    addProductForm = document.getElementById("addProductForm");
    addProductCodeSelect = document.getElementById("productCodeSelect");
    addProductNameSelect = document.getElementById("productNameSelect");
    addSpecialInstructions = document.getElementById("specialInstructions");
    editProductPopup = document.getElementById("editProductPopup");
    editCloseBtn = document.querySelector("#editProductPopup .close-btn");
    editProductForm = document.getElementById("editProductForm");
    successMessage = document.getElementById("successMessage");
    successText = document.getElementById("successText");
    totalCapacityElement = document.getElementById("totalCapacity");
    freeCapacityElement = document.getElementById("freeCapacity");
    itemCountElement = document.getElementById("itemCount");
    tbody = document.getElementById("productTableBody");
    totalRow = document.getElementById("totalRow");
    totalItemsElement = document.getElementById("totalItems");
    totalQuantityElement = document.getElementById("totalQuantity");
    createOfferBtn = document.getElementById("createOfferBtn");
    offerPopup = document.getElementById("offerPopup");
    offerForm = document.getElementById("offerForm");
    offerCloseBtn = document.querySelector("#offerPopup .close-btn");
    offerSuggestionPopup = document.getElementById("offerSuggestionPopup");
    offerSuggestionDetails = document.getElementById("offerSuggestionDetails");
    offerSuggestionApplyBtn = document.getElementById(
      "offerSuggestionApplyBtn"
    );
    offerSuggestionEditBtn = document.getElementById("offerSuggestionEditBtn");

    console.log("Elements initialized:", {
      searchInput: !!searchInput,
      addProductBtn: !!addProductBtn,
      tbody: !!tbody,
    });

    // Set up event listeners
    if (searchInput) searchInput.value = "";
    if (searchInput)
      searchInput.addEventListener("input", applyFiltersDebounced);

    if (addProductBtn && popup) {
      addProductBtn.addEventListener("click", () => {
        popup.style.display = "block";
        popup.style.animation = "fadeIn 0.3s ease-in";
        const form = document.querySelector("#addProductForm");
        if (form) form.style.animation = "slideDown 0.5s ease-out";
      });
    }

    if (warehouseCloseBtn)
      warehouseCloseBtn.addEventListener("click", () =>
        window.closePopup("add")
      );
    if (editCloseBtn)
      editCloseBtn.addEventListener("click", () => window.closePopup("edit"));
    if (offerCloseBtn)
      offerCloseBtn.addEventListener("click", () => window.closePopup("offer"));

    if (createOfferBtn && offerPopup) {
      createOfferBtn.addEventListener("click", () => {
        offerPopup.style.display = "block";
        offerPopup.style.animation = "fadeIn 0.3s ease-in";
      });
    }

    // Attach form submit handlers
    if (addProductForm)
      addProductForm.addEventListener("submit", handleAddProductSubmit);
    if (editProductForm)
      editProductForm.addEventListener("submit", handleEditProductSubmit);
    if (offerForm) offerForm.addEventListener("submit", handleOfferFormSubmit);

    // Sync product code/name selects
    if (
      addProductCodeSelect &&
      addProductNameSelect &&
      addSpecialInstructions
    ) {
      addProductCodeSelect.addEventListener("change", () =>
        syncProductSelects(addProductCodeSelect, addProductNameSelect)
      );
      addProductNameSelect.addEventListener("change", () =>
        syncProductSelects(addProductNameSelect, addProductCodeSelect)
      );
    }

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
      const dropdown = document.querySelector(".dropdown");
      if (dropdown && !dropdown.contains(e.target)) {
        const menu = document.getElementById("dropdownMenu");
        if (menu) menu.classList.remove("show");
      }
    });

    // Load first page from server to ensure filters/pagination are consistent
    if (tbody) {
      console.log("Table initialized successfully");
      loadList(1);
    } else {
      console.error("Table body not found");
    }

    if (!successMessage || !successText) {
      console.error("Success message elements not found on load:", {
        successMessage,
        successText,
      });
    }
  });
})();
