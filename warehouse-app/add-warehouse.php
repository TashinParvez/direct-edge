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



// =================== for coll  stat =============

$sql = "SELECT
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS Active_Warehouses,
            SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) AS Inactive_Warehouses,
            COUNT(*) AS Total_Warehouses
        FROM warehouses;";

$result = mysqli_query($conn, $sql);

// Fetch all rows as numeric arrays
$warehouses_stat = mysqli_fetch_all($result);



// echo $warehouses_stat[0][0];
// echo $warehouses_stat[0][1];
// echo $warehouses_stat[0][2];





?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Warehouse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex">

    <!-- Sidebar -->
    <?php
    // include '../include/Sidebar.php';
    ?>

    <!-- Main Content -->
    <!-- Main Content -->
    <div class="flex-1 p-6 ml-64 flex justify-center gap-6">
        <!-- Left: Add Warehouse Form -->
        <div class="w-full max-w-xl">
            <h2 class="text-2xl font-bold mb-6 text-center">Add New Warehouse</h2>

            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= $error ?></div>
            <?php endif; ?>

            <!-- Add Warehouse Form -->
            <form method="POST" class="bg-white p-6 rounded shadow-md">
                <div class="mb-4">
                    <label class="block mb-1 font-medium">Warehouse Name</label>
                    <input type="text" name="name" required class="w-full p-2 border border-gray-300 rounded">
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Location</label>
                    <input type="text" name="location" required class="w-full p-2 border border-gray-300 rounded">
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Total Capacity (sq ft)</label>
                    <input type="number" name="capacity_total" required class="w-full p-2 border border-gray-300 rounded">
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Type</label>
                    <select name="type" class="w-full p-2 border border-gray-300 rounded">
                        <option value="Normal">Normal</option>
                        <option value="Cold Storage">Cold Storage</option>
                        <option value="Hazardous">Hazardous</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Status</label>
                    <select name="status" class="w-full p-2 border border-gray-300 rounded">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                    </select>
                </div>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">Add Warehouse</button>
            </form>
        </div>

        <!-- Right: Stats Blocks -->
        <div class="flex flex-col gap-6 w-64">
            <!-- Total Warehouses -->
            <div class="bg-white p-4 rounded shadow-md flex flex-col items-center">
                <h3 class="text-lg font-semibold">Total Warehouses</h3>
                <p class="text-2xl font-bold text-blue-600"><?= $warehouses_stat[0][2] ?></p>

            </div>
            <!-- Active Warehouses -->
            <div class="bg-white p-4 rounded shadow-md flex flex-col items-center">
                <h3 class="text-lg font-semibold">Active Warehouses</h3>
                <p class="text-2xl font-bold text-blue-600"><?= $warehouses_stat[0][0] ?></p>
            </div>
            <!-- Inactive Warehouses -->
            <div class="bg-white p-4 rounded shadow-md flex flex-col items-center">
                <h3 class="text-lg font-semibold">Inactive Warehouses</h3>
                <p class="text-2xl font-bold text-blue-600"><?= $warehouses_stat[0][1] ?></p>
            </div>
        </div>
    </div>

</body>

</html>