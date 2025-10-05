<?php
include '../include/connect-db.php'; // database connection

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $capacity_total = $_POST['capacity_total'];
    $type = $_POST['type'];
    $status = $_POST['status'];

    // Prepare statement with placeholders
    $stmt = $conn->prepare("INSERT INTO warehouses (name, location, capacity_total, capacity_used, type, status) 
                            VALUES (?, ?, ?, 0, ?, ?)");

    // Bind parameters: s = string, i = integer
    $stmt->bind_param("sisss", $name, $location, $capacity_total, $type, $status);

    // Execute
    if ($stmt->execute()) {
        $success = "Warehouse added successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// =================== for warehouse stats =============
$sql = "SELECT
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS Active_Warehouses,
            SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) AS Inactive_Warehouses,
            SUM(CASE WHEN status = 'Under Maintenance' THEN 1 ELSE 0 END) AS Under_Maintenance,
            COUNT(*) AS Total_Warehouses,
            SUM(capacity_total) AS Total_Capacity,
            SUM(capacity_used) AS Used_Capacity
        FROM warehouses;";

$result = mysqli_query($conn, $sql);
$warehouses_stat = mysqli_fetch_assoc($result);

// Calculate additional metrics
$available_capacity = $warehouses_stat['Total_Capacity'] - $warehouses_stat['Used_Capacity'];
$utilization_rate = $warehouses_stat['Total_Capacity'] > 0 ? 
    ($warehouses_stat['Used_Capacity'] / $warehouses_stat['Total_Capacity']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New Warehouse - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-container {
            transition: all 0.3s ease;
        }
        .form-container:hover {
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
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            background-color: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .page-enter {
            animation: pageEnter 0.6s ease-out;
        }
        @keyframes pageEnter {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-animate {
            animation: slideInLeft 0.5s ease-out;
        }
        .stats-animate {
            animation: slideInRight 0.5s ease-out 0.2s both;
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="bg-gray-100 page-enter">
    <?php include '../include/Sidebar.php'; ?>
    
    <section class="home-section p-0">
        <div class="flex justify-between items-center p-6 bg-white shadow-sm border-b">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Add New Warehouse</h1>
                <p class="text-gray-600 mt-1">Create a new warehouse location for inventory management</p>
            </div>
            <div class="flex space-x-3 no-print">
                <a href="warehouse-status.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                    <i class='bx bx-list-ul mr-2'></i>View All Warehouses
                </a>
                <button onclick="clearForm()" class="bg-gray-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-600 transition-colors">
                    <i class='bx bx-refresh mr-2'></i>Reset Form
                </button>
            </div>
        </div>

        <div class="container mx-auto px-6 py-6">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Left: Add Warehouse Form -->
                <div class="flex-1 max-w-2xl form-animate">
                    <!-- Success/Error Messages -->
                    <?php if (isset($success)): ?>
                        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center">
                            <i class='bx bx-check-circle text-2xl mr-3'></i>
                            <div>
                                <div class="font-semibold">Success!</div>
                                <div><?= $success ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center">
                            <i class='bx bx-error-circle text-2xl mr-3'></i>
                            <div>
                                <div class="font-semibold">Error!</div>
                                <div><?= $error ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Card -->
                    <div class="bg-white shadow-xl rounded-xl p-8 form-container border border-gray-100">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <i class='bx bx-buildings text-2xl text-green-600'></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Warehouse Information</h2>
                                <p class="text-gray-600">Fill in the details for the new warehouse</p>
                            </div>
                        </div>

                        <form method="POST" id="warehouseForm" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class='bx bx-building mr-2'></i>Warehouse Name
                                </label>
                                <input type="text" name="name" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                    placeholder="Enter warehouse name">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class='bx bx-map-pin mr-2'></i>Location
                                </label>
                                <input type="text" name="location" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                    placeholder="Enter warehouse location">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class='bx bx-cube mr-2'></i>Total Capacity (sq ft)
                                </label>
                                <input type="number" name="capacity_total" required min="1" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                    placeholder="Enter capacity in square feet">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-category mr-2'></i>Warehouse Type
                                    </label>
                                    <select name="type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                                        <option value="Normal">Normal Storage</option>
                                        <option value="Cold Storage">Cold Storage</option>
                                        <option value="Hazardous">Hazardous Materials</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-check-circle mr-2'></i>Initial Status
                                    </label>
                                    <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                        <option value="Under Maintenance">Under Maintenance</option>
                                    </select>
                                </div>
                            </div>

                            <div class="pt-6">
                                <button type="submit" class="w-full bg-green-500 text-white py-4 px-6 rounded-lg hover:bg-green-600 transition-colors font-semibold text-lg">
                                    <i class='bx bx-plus-circle mr-2'></i>Add Warehouse
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Statistics Dashboard -->
                <div class="w-full lg:w-80 stats-animate">
                    <div class="space-y-6">
                        <!-- Header -->
                        <div class="bg-white shadow-md rounded-lg p-4 border border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class='bx bx-chart-bar mr-2 text-blue-600'></i>
                                Warehouse Analytics
                            </h3>
                            <p class="text-sm text-gray-600">Current system overview</p>
                        </div>

                        <!-- Total Warehouses -->
                        <div class="bg-white shadow-lg rounded-lg p-6 stat-card border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Warehouses</h3>
                                    <p class="text-3xl font-bold text-blue-600 mt-2"><?= $warehouses_stat['Total_Warehouses'] ?></p>
                                </div>
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class='bx bx-buildings text-2xl text-blue-600'></i>
                                </div>
                            </div>
                        </div>

                        <!-- Active Warehouses -->
                        <div class="bg-white shadow-lg rounded-lg p-6 stat-card border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Active Warehouses</h3>
                                    <p class="text-3xl font-bold text-green-600 mt-2"><?= $warehouses_stat['Active_Warehouses'] ?></p>
                                </div>
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class='bx bx-check-circle text-2xl text-green-600'></i>
                                </div>
                            </div>
                        </div>

                        <!-- Inactive Warehouses -->
                        <div class="bg-white shadow-lg rounded-lg p-6 stat-card border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Inactive Warehouses</h3>
                                    <p class="text-3xl font-bold text-red-600 mt-2"><?= $warehouses_stat['Inactive_Warehouses'] ?></p>
                                </div>
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class='bx bx-x-circle text-2xl text-red-600'></i>
                                </div>
                            </div>
                        </div>

                        <!-- Under Maintenance -->
                        <div class="bg-white shadow-lg rounded-lg p-6 stat-card border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Under Maintenance</h3>
                                    <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $warehouses_stat['Under_Maintenance'] ?></p>
                                </div>
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class='bx bx-wrench text-2xl text-yellow-600'></i>
                                </div>
                            </div>
                        </div>

                        <!-- Capacity Overview -->
                        <div class="bg-white shadow-lg rounded-lg p-6 stat-card border border-gray-100">
                            <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide mb-4">Capacity Overview</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Total Capacity:</span>
                                    <span class="font-semibold"><?= number_format($warehouses_stat['Total_Capacity']) ?> sq ft</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Used Capacity:</span>
                                    <span class="font-semibold text-red-600"><?= number_format($warehouses_stat['Used_Capacity']) ?> sq ft</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Available:</span>
                                    <span class="font-semibold text-green-600"><?= number_format($available_capacity) ?> sq ft</span>
                                </div>
                                <div class="pt-2">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600">Utilization Rate:</span>
                                        <span class="font-semibold"><?= number_format($utilization_rate, 1) ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-gradient-to-r from-green-500 to-blue-600 h-3 rounded-full transition-all duration-500" 
                                             style="width: <?= min(100, $utilization_rate) ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function clearForm() {
            document.getElementById('warehouseForm').reset();
            
            // Show confirmation
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="bx bx-check mr-2"></i>Form Cleared!';
            button.classList.remove('bg-gray-500', 'hover:bg-gray-600');
            button.classList.add('bg-green-500');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('bg-green-500');
                button.classList.add('bg-gray-500', 'hover:bg-gray-600');
            }, 1500);
        }

        // Form validation enhancements
        document.getElementById('warehouseForm').addEventListener('submit', function(e) {
            const capacity = document.querySelector('input[name="capacity_total"]').value;
            if (capacity <= 0) {
                e.preventDefault();
                alert('Please enter a valid capacity greater than 0');
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-2"></i>Adding Warehouse...';
            submitButton.disabled = true;
        });

        // Auto-hide success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            const successMsg = document.querySelector('.bg-green-100');
            const errorMsg = document.querySelector('.bg-red-100');
            
            if (successMsg) {
                setTimeout(() => {
                    successMsg.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => successMsg.remove(), 500);
                }, 5000);
            }
            
            if (errorMsg) {
                setTimeout(() => {
                    errorMsg.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => errorMsg.remove(), 500);
                }, 8000);
            }
        });

        // Add fade out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>