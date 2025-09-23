<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Info</title>
    <link rel="stylesheet" href="../warehouse information/warehouse-info.css">
</head>
<body>
    <div class="container">
        <h1>Warehouse Information</h1>

        <!-- Metrics Section -->
        <?php
        $inventory = [
            ['id' => 10001, 'product_code' => 'VEG-001', 'product' => 'Carrot', 'special_instructions' => 'Store at 0-2°C', 'date_added' => '2025-01-10', 'quantity' => 500, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse A'],
            ['id' => 10002, 'product_code' => 'VEG-002', 'product' => 'Potato', 'special_instructions' => null, 'date_added' => '2025-02-15', 'quantity' => 100, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse B'],
            ['id' => 10003, 'product_code' => 'VEG-003', 'product' => 'Tomato', 'special_instructions' => 'Handle with care, avoid stacking', 'date_added' => '2025-03-01', 'quantity' => 300, 'status' => 'Completed', 'unit' => 'kg', 'warehouse' => 'Warehouse A'],
            ['id' => 10004, 'product_code' => 'VEG-004', 'product' => 'Cabbage', 'special_instructions' => null, 'date_added' => '2025-04-05', 'quantity' => 200, 'status' => 'In progress', 'unit' => 'units', 'warehouse' => 'Warehouse C'],
            ['id' => 10005, 'product_code' => 'VEG-005', 'product' => 'Broccoli', 'special_instructions' => 'Keep refrigerated', 'date_added' => '2025-05-12', 'quantity' => 150, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse B'],
            ['id' => 10006, 'product_code' => 'RCE-001', 'product' => 'Basmati Rice', 'special_instructions' => 'Store in dry conditions', 'date_added' => '2025-06-20', 'quantity' => 200, 'status' => 'Completed', 'unit' => 'kg', 'warehouse' => 'Warehouse A'],
            ['id' => 10007, 'product_code' => 'RCE-002', 'product' => 'Jasmine Rice', 'special_instructions' => null, 'date_added' => '2025-07-10', 'quantity' => 150, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse C'],
            ['id' => 10008, 'product_code' => 'VEG-006', 'product' => 'Onion', 'special_instructions' => 'Store in ventilated area', 'date_added' => '2025-08-15', 'quantity' => 800, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse B'],
            ['id' => 10009, 'product_code' => 'VEG-007', 'product' => 'Cauliflower', 'special_instructions' => null, 'date_added' => '2025-09-01', 'quantity' => 180, 'status' => 'Completed', 'unit' => 'units', 'warehouse' => 'Warehouse A'],
            ['id' => 10010, 'product_code' => 'CRP-001', 'product' => 'Wheat Grain', 'special_instructions' => 'Protect from moisture', 'date_added' => '2025-09-10', 'quantity' => 250, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse C'],
            ['id' => 10011, 'product_code' => 'VEG-008', 'product' => 'Spinach', 'special_instructions' => 'Refrigerate at 0-4°C', 'date_added' => '2025-09-15', 'quantity' => 100, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse B'],
            ['id' => 10012, 'product_code' => 'VEG-009', 'product' => 'Green Beans', 'special_instructions' => null, 'date_added' => '2025-09-20', 'quantity' => 250, 'status' => 'Completed', 'unit' => 'kg', 'warehouse' => 'Warehouse A'],
            ['id' => 10013, 'product_code' => 'RCE-003', 'product' => 'Brown Rice', 'special_instructions' => 'Store in airtight containers', 'date_added' => '2025-09-25', 'quantity' => 120, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse C'],
            ['id' => 10014, 'product_code' => 'VEG-010', 'product' => 'Bell Pepper', 'special_instructions' => null, 'date_added' => '2025-09-30', 'quantity' => 200, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse B'],
            ['id' => 10015, 'product_code' => 'CRP-002', 'product' => 'Corn', 'special_instructions' => 'Store in cool, dry place', 'date_added' => '2025-10-05', 'quantity' => 150, 'status' => 'Completed', 'unit' => 'kg', 'warehouse' => 'Warehouse A'],
            ['id' => 10016, 'product_code' => 'VEG-011', 'product' => 'Cucumber', 'special_instructions' => null, 'date_added' => '2025-10-10', 'quantity' => 300, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse C'],
            ['id' => 10017, 'product_code' => 'VEG-012', 'product' => 'Lettuce', 'special_instructions' => 'Keep refrigerated', 'date_added' => '2025-10-15', 'quantity' => 150, 'status' => 'In progress', 'unit' => 'units', 'warehouse' => 'Warehouse B'],
            ['id' => 10018, 'product_code' => 'CRP-003', 'product' => 'Barley', 'special_instructions' => null, 'date_added' => '2025-10-20', 'quantity' => 100, 'status' => 'Completed', 'unit' => 'kg', 'warehouse' => 'Warehouse A'],
            ['id' => 10019, 'product_code' => 'VEG-013', 'product' => 'Zucchini', 'special_instructions' => null, 'date_added' => '2025-10-25', 'quantity' => 200, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse C'],
            ['id' => 10020, 'product_code' => 'VEG-014', 'product' => 'Eggplant', 'special_instructions' => 'Avoid direct sunlight', 'date_added' => '2025-10-30', 'quantity' => 250, 'status' => 'In progress', 'unit' => 'kg', 'warehouse' => 'Warehouse B'],
        ];

        $totalCapacity = 100000;
        $usedCapacity = array_sum(array_column($inventory, 'quantity'));
        $freeCapacity = $totalCapacity - $usedCapacity;
        $itemCount = count($inventory);
        $currentDate = new DateTime('2025-09-23'); // Today's date as per system
        ?>
        <div class="metrics-grid">
            <div class="metric-card">
                <h3>Total Capacity</h3>
                <p id="totalCapacity"><?php echo $totalCapacity; ?> units</p>
            </div>
            <div class="metric-card">
                <h3>Free Capacity</h3>
                <p id="freeCapacity"><?php echo $freeCapacity; ?> units</p>
            </div>
            <div class="metric-card">
                <h3>Number of Items</h3>
                <p id="itemCount"><?php echo $itemCount; ?></p>
            </div>
        </div>

        <div class="controls">
            <input type="text" id="searchInput" placeholder="Search Products..." />
            <div class="filter-container">
                <label for="filterDropdown">Filter by:</label>
                <div class="dropdown">
                    <button class="dropdown-toggle" onclick="toggleDropdown()">All Products ▾</button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <div class="group">
                            <span class="group-title">Status</span>
                            <label><input type="checkbox" class="filter-option" value="all"> all</label>
                            <label><input type="checkbox" class="filter-option" value="In progress"> In progress</label>
                            <label><input type="checkbox" class="filter-option" value="Completed"> Completed</label>
                        </div>
                        <div class="group">
                            <span class="group-title">Capacity</span>
                            <label><input type="checkbox" class="filter-option" value="all"> all</label>
                            <label><input type="checkbox" class="filter-option" value="low"> low</label>
                            <label><input type="checkbox" class="filter-option" value="medium"> medium</label>
                            <label><input type="checkbox" class="filter-option" value="high"> high</label>
                        </div>
                        <div class="group">
                            <span class="group-title">Warehouse</span>
                            <label><input type="checkbox" class="filter-option" value="all"> all</label>
                            <label><input type="checkbox" class="filter-option" value="Warehouse A"> Warehouse A</label>
                            <label><input type="checkbox" class="filter-option" value="Warehouse B"> Warehouse B</label>
                            <label><input type="checkbox" class="filter-option" value="Warehouse C"> Warehouse C</label>
                        </div>
                        <div class="group">
                            <span class="group-title">Unit</span>
                            <label><input type="checkbox" class="filter-option" value="all"> all</label>
                            <label><input type="checkbox" class="filter-option" value="kg"> kg</label>
                            <label><input type="checkbox" class="filter-option" value="units"> units</label>
                        </div>
                        <div class="dropdown-footer">
                            <button type="button" onclick="selectAll()">Select all</button>
                            <button class="apply-btn" onclick="applyFilters()">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            <button id="addProductBtn">Add Product</button>
            <button id="createOfferBtn">Create Offer</button>
        </div>

        <div id="successMessage" class="success-message" style="display: none;">
            <span id="successText"></span>
        </div>

        <div id="lowStockAlert" class="alert alert-warning" style="display: none;">
            Low stock detected for some products!
        </div>

        <div id="productContainer" class="list-view">
            <table id="productTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Special Instructions</th>
                        <th>Date</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Warehouse</th>
                        <th>Offer Suggestion</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php foreach ($inventory as $item) {
                        $statusClass = strtolower($item['status']) === 'completed' ? 'completed' : 'in-progress';
                        $lowStock = $item['quantity'] < 10 ? 'low-stock' : '';
                        $itemDate = new DateTime($item['date_added']);
                        $monthsDiff = $currentDate->diff($itemDate)->m + ($currentDate->diff($itemDate)->y * 12);
                        $offerSuggestion = ($item['quantity'] < 50 || $monthsDiff >= 6) ? '10% Discount' : 'No Offer';
                        echo "
                        <tr data-id='{$item['id']}' data-offer='$offerSuggestion'>
                            <td>{$item['id']}</td>
                            <td>{$item['product_code']}</td>
                            <td>{$item['product']}</td>
                            <td>" . ($item['special_instructions'] ?? '—') . "</td>
                            <td>{$item['date_added']}</td>
                            <td class='$lowStock'>{$item['quantity']}</td>
                            <td>{$item['unit']}</td>
                            <td class='$statusClass'>{$item['status']}</td>
                            <td>{$item['warehouse']}</td>
                            <td>$offerSuggestion</td>
                            <td>
                                <button class='action-btn edit-btn' onclick='editProduct({$item['id']})'>✎</button>
                                <button class='action-btn delete-btn' onclick='deleteProduct({$item['id']})'>🗑</button>
                                <button class='action-btn offer-btn' onclick='manageOffer({$item['id']})'>🏷️</button>
                            </td>
                        </tr>";
                    } ?>
                </tbody>
                <tfoot id="totalRow" style="display: none;">
                    <tr>
                        <td colspan="2">Totals:</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td colspan="3">
                            <span class="label">Items:</span>
                            <span class="value" id="totalItems">0</span>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pagination">
            <button id="prevBtn" onclick="changePage(-1)">Previous</button>
            <div id="pageNumbers"></div>
            <button id="nextBtn" onclick="changePage(1)">Next</button>
            <select id="rowsPerPage" onchange="changeRowsPerPage()">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            <span>Rows per page</span>
        </div>

        <div id="addProductPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup()">&times;</span>
                <h2>Add New Product</h2>
                <form id="addProductForm">
                    <label for="productCode">Product Code:</label>
                    <input type="text" id="productCode" name="productCode" required>

                    <label for="productName">Product Name:</label>
                    <input type="text" id="productName" name="productName" required>

                    <label for="specialInstructions">Special Instructions:</label>
                    <input type="text" id="specialInstructions" name="specialInstructions">

                    <label for="dateAdded">Date:</label>
                    <input type="date" id="dateAdded" name="dateAdded" required>

                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>

                    <label for="unit">Unit:</label>
                    <select id="unit" name="unit" required>
                        <option value="kg">kg</option>
                        <option value="units">units</option>
                    </select>

                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="In progress">In progress</option>
                        <option value="Completed">Completed</option>
                    </select>

                    <label for="warehouse">Warehouse:</label>
                    <select id="warehouse" name="warehouse" required>
                        <option value="Warehouse A">Warehouse A</option>
                        <option value="Warehouse B">Warehouse B</option>
                        <option value="Warehouse C">Warehouse C</option>
                    </select>

                    <button type="submit">Add Product</button>
                </form>
            </div>
        </div>

        <div id="editProductPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup('edit')">&times;</span>
                <h2>Edit Product</h2>
                <form id="editProductForm">
                    <input type="hidden" id="editProductId">
                    <label for="editProductCode">Product Code:</label>
                    <input type="text" id="editProductCode" name="editProductCode" required>

                    <label for="editProductName">Product Name:</label>
                    <input type="text" id="editProductName" name="editProductName" required>

                    <label for="editSpecialInstructions">Special Instructions:</label>
                    <input type="text" id="editSpecialInstructions" name="editSpecialInstructions">

                    <label for="editDateAdded">Date:</label>
                    <input type="date" id="editDateAdded" name="editDateAdded" required>

                    <label for="editQuantity">Quantity:</label>
                    <input type="number" id="editQuantity" name="editQuantity" min="1" required>

                    <label for="editUnit">Unit:</label>
                    <select id="editUnit" name="editUnit" required>
                        <option value="kg">kg</option>
                        <option value="units">units</option>
                    </select>

                    <label for="editStatus">Status:</label>
                    <select id="editStatus" name="editStatus" required>
                        <option value="In progress">In progress</option>
                        <option value="Completed">Completed</option>
                    </select>

                    <label for="editWarehouse">Warehouse:</label>
                    <select id="editWarehouse" name="editWarehouse" required>
                        <option value="Warehouse A">Warehouse A</option>
                        <option value="Warehouse B">Warehouse B</option>
                        <option value="Warehouse C">Warehouse C</option>
                    </select>

                    <button type="submit">Update Product</button>
                </form>
            </div>
        </div>

        <div id="offerPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup('offer')">&times;</span>
                <h2>Manage Offer</h2>
                <form id="offerForm">
                    <label for="offerProductId">Product:</label>
                    <select id="offerProductId" name="offerProductId" required></select>
                    <label for="offerDiscount">Discount (%):</label>
                    <input type="number" id="offerDiscount" name="offerDiscount" min="0" max="100" required>

                    <label for="offerStartDate">Start Date:</label>
                    <input type="date" id="offerStartDate" name="offerStartDate" required>

                    <label for="offerEndDate">End Date:</label>
                    <input type="date" id="offerEndDate" name="offerEndDate" required>

                    <button type="submit">Save Offer</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../warehouse information/warehouse-info.js"></script>
</body>
</html>