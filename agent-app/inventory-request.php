<?php include '../Include/SidebarAgent.php'; ?>
<link rel="stylesheet" href="../Include/sidebar.css">
<?php

$agent_id = isset($user_id) ? $user_id : 64;

include '../include/connect-db.php'; // database connection

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $productId = (int)$_POST['product']; // Now using product_id from dropdown
    $quantity = (int)$_POST['quantity'];
    $unit = $_POST['unit'];
    $unitVolume = (float)$_POST['unitVolume'];

    // Handle dates properly: only set if valid YYYY-MM-DD format, else NULL
    $expiry = !empty($_POST['expiry']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['expiry']) ? $_POST['expiry'] : null;
    $inbound = !empty($_POST['inbound']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['inbound']) ? $_POST['inbound'] : null;
    $warehouseId = (int)$_POST['warehouse']; // warehouse_id directly from dropdown

    // Handle product image upload
    $imagePath = null;
    if (isset($_FILES['productFile']) && $_FILES['productFile']['error'] == 0) {
        $uploadDir = '../assets/products-image/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Clean filename and add product name + date
        $ext = pathinfo($_FILES['productFile']['name'], PATHINFO_EXTENSION);
        $safeProductName = preg_replace("/[^a-zA-Z0-9_-]/", "_", $productId); // Use ID for safety
        $filename = $safeProductName . '_' . date('YmdHis') . '.' . $ext;
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['productFile']['tmp_name'], $targetFile)) {
            // Save relative path or full URL
            $imagePath = 'assets/products-image/' . $filename; // relative path
        }
    }

    // Insert into warehouse_products table - Fixed binding for dates as NULL-safe
    $stmt = $conn->prepare("
        INSERT INTO warehouse_products 
            (warehouse_id, product_id, quantity, inbound_stock_date, expiry_date, unit_volume, agent_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    // Use 's' for dates to bind as strings or NULL properly
    $stmt->bind_param("iiissii", $warehouseId, $productId, $quantity, $inbound, $expiry, $unitVolume, $agent_id);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;

        // --- NOTIFICATION ---
        include_once __DIR__ . '/../include/notification_helpers.php';
        $admin_ids = get_user_ids_by_role($conn, 'Admin'); // Assuming warehouse managers are Admins
        if (!empty($admin_ids)) {
            $agent_name = $_SESSION['username'] ?? 'An agent';
            $notification_message = "New inventory request from " . htmlspecialchars($agent_name) . ".";
            $notification_link = "/warehouse-app/All-Inventory-Requests/all-inventory-requests.php";
            create_notification($conn, $admin_ids, 'inventory_request_new', $notification_message, $notification_link);
        }
        // --- END NOTIFICATION ---

        // Redirect or show success message
        header("Location: my-inventory-requests.php?status=success");
        exit;
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}


//============================ all products with IDs =======================
$sql = "SELECT product_id, name FROM `products` WHERE 1";
$result = mysqli_query($conn, $sql);
$all_products = mysqli_fetch_all($result, MYSQLI_ASSOC);

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
    <title>Agent Inventory Request - Stock Integrated</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .request-form {
            transition: all 0.3s ease;
        }

        .request-form:hover {
            background-color: #f9fafb;
        }

        .form-field {
            transition: all 0.3s ease;
        }

        .form-field:hover {
            background-color: #f3f4f6;
        }

        .form-field:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .product-list {
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .product-item {
            transition: all 0.2s ease;
        }

        .product-item:hover {
            background-color: #f3f4f6;
            padding-left: 1rem;
        }

        .warehouse-option {
            transition: all 0.2s ease;
        }

        .preview-image {
            transition: all 0.3s ease;
        }

        .preview-image:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .space-display {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {

            0%,
            100% {
                box-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
            }

            50% {
                box-shadow: 0 0 20px rgba(16, 185, 129, 0.6);
            }
        }

        .success-message {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>

<body class="bg-gray-100">

    <section class="home-section max-w-6xl mx-auto px-6 md:px-12 pb-4">
        <div class="items-center p-4">
            <h1 class="text-2xl font-bold">Agent Inventory Request</h1>
            <p class="text-gray-600 text-sm max-w-xl whitespace-nowrap  text-ellipsis">
                This function processes requests related to inventory management, such as adding, updating, or retrieving inventory items.
            </p>
            <!-- <button onclick="window.history.back()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">
                <i class='bx bx-arrow-back'></i> Back
            </button> -->
        </div>

        <?php if (isset($success)): ?>
            <div class="mx-4 mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded success-message">
                <div class="flex items-center">
                    <i class='bx bx-check-circle mr-2'></i>
                    <?php echo $success; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="mx-4 mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <div class="flex items-center">
                    <i class='bx bx-error-circle mr-2'></i>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="container mx-auto px-4">
            <div class="bg-white shadow-lg rounded-lg p-6 request-form">

                <!-- Form Header -->
                <div class="mb-6 text-center">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center justify-center">
                        <i class='bx bx-package mr-2 text-green-600'></i>
                        Submit Inventory Request
                    </h2>
                    <p class="text-sm text-gray-600 mt-2">Fill out the form below to request inventory items</p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">

                    <!-- Product Selection - Changed to dropdown -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-search-alt mr-1'></i>Select Product *
                        </label>
                        <select name="product" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field" required>
                            <option value="">-- Choose Product --</option>
                            <?php foreach ($all_products as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quantity, Unit, and Unit Volume -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-calculator mr-1'></i>Quantity *
                            </label>
                            <input type="number" name="quantity" id="quantity" min="1" value="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-ruler mr-1'></i>Unit
                            </label>
                            <select name="unit" id="unit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                                <option value="kg">Kg</option>
                                <option value="litre">Litre</option>
                                <option value="pcs">Pcs</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-cube mr-1'></i>Unit Volume (m³) *
                            </label>
                            <input type="number" step="0.01" name="unitVolume" id="unitVolume" value="0.1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field" required>
                        </div>
                    </div>

                    <!-- Expiry Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-calendar mr-1'></i>Expiry Date (Last valid date of this item)
                        </label>
                        <input type="date" name="expiry"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <!-- Inbound Date -->
                    <div>
                        <label class="block mb-1 font-medium">Inbound Date (When goods will arrive at warehouse)</label>
                        <input type="date" name="inbound" class="w-full p-2 border border-gray-300 rounded">
                    </div>

                    <!-- Expiry Date -->
                    <!-- <div>
                        <label class="block mb-1 font-medium">Expiry Date</label>
                        <input type="date" name="expiry" class="w-full p-2 border border-gray-300 rounded">
                    </div> -->

                    <!-- Product Image Upload -->
                    <!-- <div>
                        <label class="block mb-1 font-medium">Upload Product Image</label>
                        <input type="file" name="productFile" id="productFile" accept="image/*"
                            class="w-full p-2 border border-gray-300 rounded">
                        <img id="productPreview" src="" class="mt-2 h-32 w-32 object-cover rounded hidden">
                    </div> -->

                    <!-- Required Space Display -->
                    <!-- Required Space Display -->
                    <div class="space-display p-4 rounded-md text-center text-white bg-gray-800">
                        <p class="font-semibold flex items-center justify-center text-white">
                            <i class='bx bx-box mr-2 text-white'></i>
                            Required Storage Space:
                            <span id="requiredSpace" class="ml-2 text-xl text-white">0.00</span> m³
                        </p>
                    </div>


                    <!-- Warehouse Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-building mr-1'></i>Select Warehouse *
                        </label>
                        <select id="warehouse" name="warehouse"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field" required>
                            <option value="">-- Choose Available Warehouse --</option>
                            <?php foreach ($all_free_warehouse as $warehouse): ?>
                                <option value="<?php echo $warehouse['warehouse_id']; ?>" data-capacity="<?php echo $warehouse['free_capacity']; ?>">
                                    <?php echo htmlspecialchars($warehouse['name']) . ' (Available: ' . $warehouse['free_capacity'] . ' m³)'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Only warehouses with sufficient space are shown</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-4">
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition-colors font-semibold text-sm">
                            <i class='bx bx-send mr-2'></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        const quantityInput = document.getElementById('quantity');
        const unitVolumeInput = document.getElementById('unitVolume');
        const requiredSpace = document.getElementById('requiredSpace');
        const warehouseDropdown = document.getElementById('warehouse');
        const productPreview = document.getElementById('productPreview');
        const productFile = document.getElementById('productFile');

        // Warehouses from PHP (with capacity data)
        const warehouses = <?php echo json_encode($all_free_warehouse); ?>;

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
            let availableWarehouses = 0;

            // Clear and repopulate based on space
            warehouseDropdown.innerHTML = '<option value="">-- Choose Available Warehouse --</option>';
            warehouses.forEach(wh => {
                if (wh.free_capacity >= space) {
                    const option = document.createElement('option');
                    option.value = wh.warehouse_id;
                    option.textContent = `${wh.name} (Available: ${wh.free_capacity} m³)`;
                    option.className = 'warehouse-option';
                    warehouseDropdown.appendChild(option);
                    availableWarehouses++;
                }
            });

            if (availableWarehouses === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No warehouses have sufficient space';
                option.disabled = true;
                warehouseDropdown.appendChild(option);
            }
        }

        //================ Image Preview ===================
        productFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    productPreview.src = e.target.result;
                    productPreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                productPreview.classList.add('hidden');
            }
        });

        // Initialize warehouses on page load
        updateWarehouses();
    </script>

    <?php include '../Include/footer.php'; ?>

</body>

</html>