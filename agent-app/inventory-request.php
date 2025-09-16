<?php
include '../include/connect-db.php'; // database connection


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $productName = $_POST['product'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $unitVolume = $_POST['unitVolume'];
    $expiry = $_POST['expiry'];
    $warehouseId = $_POST['warehouse']; // warehouse_id directly from dropdown

    // Handle product image upload
    $imagePath = null;
    if (isset($_FILES['productFile']) && $_FILES['productFile']['error'] == 0) {
        $uploadDir = '../assets/products-image/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Clean filename and add product name + date
        $ext = pathinfo($_FILES['productFile']['name'], PATHINFO_EXTENSION);
        $safeProductName = preg_replace("/[^a-zA-Z0-9_-]/", "_", $productName);
        $filename = $safeProductName . '_' . date('YmdHis') . '.' . $ext;
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['productFile']['tmp_name'], $targetFile)) {
            // Save relative path or full URL
            $imagePath = 'assets/products-image/' . $filename; // relative path
        }
    }

    // Insert into warehouse_products table
    $stmt = $conn->prepare("
        INSERT INTO warehouse_products 
            (warehouse_id, product_id, quantity, expiry_date, unit_volume, image_url, request_status)
        VALUES (
            ?, 
            (SELECT product_id FROM products WHERE name = ?), 
            ?, 
            ?, 
            ?, 
            ?, 
            0
        )
    ");
    $stmt->bind_param("iisdss", $warehouseId, $productName, $quantity, $expiry, $unitVolume, $imagePath);

    if ($stmt->execute()) {
        $success = "Request submitted successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}


//============================ all producs name =======================
$sql = "SELECT name FROM `products` WHERE 1";

$result = mysqli_query($conn, $sql);
$all_producs = mysqli_fetch_all($result, MYSQLI_NUM);



//============================ all warehouse name =======================
$sql = "SELECT `warehouse_id`, `name`, `capacity_total` - `capacity_used` as free_capacity
        FROM `warehouses` WHERE 1";

$result = mysqli_query($conn, $sql);
$all_free_warehouse = mysqli_fetch_all($result, MYSQLI_ASSOC);











?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Inventory Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex">

    <!-- Sidebar -->
    <?php
    // include '../include/Sidebar.php';  
    ?>

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-64 flex justify-center">
        <div class="w-full max-w-3xl">

            <h2 class="text-2xl font-bold mb-6 text-center">Inventory Request</h2>

            <?php if (isset($success)): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= $success ?></div>
            <?php endif; ?>

            <!-- Request Form -->
            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow-md space-y-4">

                <!-- Product Selection -->
                <div>
                    <label class="block mb-1 font-medium">Select Product</label>
                    <input type="text" name="product" id="productSearch" placeholder="Search product..."
                        class="w-full p-2 border border-gray-300 rounded">
                    <ul id="productList" class="border border-gray-300 rounded mt-1 max-h-40 overflow-y-auto hidden bg-white"></ul>
                </div>

                <!-- Quantity & Unit -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Quantity</label>
                        <input type="number" name="quantity" id="quantity" min="1" value="1"
                            class="w-full p-2 border border-gray-300 rounded">
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Unit</label>
                        <select name="unit" id="unit" class="w-full p-2 border border-gray-300 rounded">
                            <option value="kg">Kg</option>
                            <option value="litre">Litre</option>
                            <option value="pcs">Pcs</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Unit Volume (m³)</label>
                        <input type="number" step="0.01" name="unitVolume" id="unitVolume" value="0.1"
                            class="w-full p-2 border border-gray-300 rounded">
                    </div>
                </div>

                <!-- Expiry Date -->
                <div>
                    <label class="block mb-1 font-medium">Expiry Date</label>
                    <input type="date" name="expiry" class="w-full p-2 border border-gray-300 rounded">
                </div>

                <!-- Product Image Upload -->
                <div>
                    <label class="block mb-1 font-medium">Upload Product Image</label>
                    <input type="file" name="productFile" id="productFile" accept="image/*"
                        class="w-full p-2 border border-gray-300 rounded">
                    <img id="productPreview" src="" class="mt-2 h-32 w-32 object-cover rounded hidden">
                </div>

                <!-- Required Space Calculation -->
                <div>
                    <p class="font-medium">Required Space (m³): <span id="requiredSpace">0</span></p>
                </div>

                <!-- Warehouse Selection -->
                <div>
                    <label class="block mb-1 font-medium">Select Warehouse</label>
                    <select id="warehouse" name="warehouse" class="w-full p-2 border border-gray-300 rounded">
                        <option value="">-- Choose Warehouse --</option>
                        <option value="1">Warehouse A (Available: 50 m³)</option>
                        <option value="2">Warehouse B (Available: 30 m³)</option>
                        <option value="3">Warehouse C (Available: 70 m³)</option>
                    </select>
                </div>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
        const productSearch = document.getElementById('productSearch');
        const productList = document.getElementById('productList');
        const quantityInput = document.getElementById('quantity');
        const unitVolumeInput = document.getElementById('unitVolume');
        const requiredSpace = document.getElementById('requiredSpace');
        const warehouseDropdown = document.getElementById('warehouse');

        // Products from PHP
        const products = <?php echo json_encode(array_column($all_producs, 0)); ?>;

        // Warehouses from PHP
        const warehouses = <?php echo json_encode($all_free_warehouse); ?>;

        //================ Product Search ===================
        productSearch.addEventListener('input', function() {
            const val = this.value.toLowerCase();
            productList.innerHTML = '';
            if (val) {
                const filtered = products.filter(p => p.toLowerCase().includes(val));
                filtered.forEach(p => {
                    const li = document.createElement('li');
                    li.textContent = p;
                    li.className = 'p-2 cursor-pointer hover:bg-gray-100';
                    li.addEventListener('click', () => {
                        productSearch.value = p;
                        productList.classList.add('hidden');
                        updateWarehouses();
                    });
                    productList.appendChild(li);
                });
                productList.classList.remove('hidden');
            } else {
                productList.classList.add('hidden');
            }
        });

        //================ Required Space Calculation ===================
        function calcSpace() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const unitVol = parseFloat(unitVolumeInput.value) || 0;
            const space = quantity * unitVol;
            requiredSpace.textContent = space.toFixed(2);
            updateWarehouses(space);
        }

        quantityInput.addEventListener('input', calcSpace);
        unitVolumeInput.addEventListener('input', calcSpace);
        calcSpace();

        //================ Update Warehouse Dropdown ===================
        function updateWarehouses(space = parseFloat(requiredSpace.textContent) || 0) {
            warehouseDropdown.innerHTML = '<option value="">-- Choose Warehouse --</option>';
            warehouses.forEach(wh => {
                if (wh.free_capacity >= space) {
                    const option = document.createElement('option');
                    option.value = wh.warehouse_id;
                    option.textContent = `${wh.name} (Free: ${wh.free_capacity} m³)`;
                    warehouseDropdown.appendChild(option);
                }
            });
        }
    </script>



</body>

</html>