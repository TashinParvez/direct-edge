<?php

include '../../include/connect-db.php';


//  -----------     segment 1     ----------- 

$agent_id = $_GET['id'] ?? 5; // Get agent id

$sql = "SELECT 
            u.user_id AS id,
            u.full_name AS name,
            u.email,
            u.phone, 
            u.image_url AS image,
            c.name AS area
        FROM users u
        LEFT JOIN agent_assigned_cities aac ON u.user_id = aac.agent_id
        LEFT JOIN cities c ON aac.city_id = c.city_id
        WHERE u.role = 'Agent' AND u.user_id = ?
        GROUP BY u.user_id
        LIMIT 1;";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();

// Example output
// print_r($agent);

// ======================== product_stored_in_warehouse ====================

$sql = "SELECT COUNT(id) AS num
        FROM `warehouse_products`
        WHERE `agent_id` = '$agent_id'
        AND `request_status` = 1";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);
$product_stored_in_warehouse = $row['num']; // This will have the count
// echo "Products stored in warehouse: " . $product_stored_in_warehouse;



// ======================= total_farmar_added =====================

$sql = "SELECT COUNT(id) AS num
        FROM `farmers`
        WHERE  `agent_id` = '$agent_id'";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);
$total_farmar_added = $row['num']; // This will have the count


// ============================================




// ------------------------------ Segment 2: Warehouse Products ------------------------------

// SQL query
$sql = "SELECT wp.id, p.name, p.img_url, p.unit, wp.quantity, w.name AS warehouse_name
        FROM warehouse_products AS wp
        LEFT JOIN products AS p ON p.product_id = wp.product_id
        LEFT JOIN warehouses AS w ON w.warehouse_id = wp.warehouse_id
        WHERE wp.agent_id = '$agent_id' 
        AND wp.request_status = 1
        order by p.name ASC
        ";

$result = $conn->query($sql);

$warehouse_products = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $warehouse_products[] = [
            'warehouse' => $row['warehouse_name'],
            'product' => $row['name'],
            'quantity' => (int)$row['quantity'],
            'unit' => $row['unit'],
            'image' => $row['img_url'] ?: 'https://via.placeholder.com/150'
        ];
    }
}

// print_r($warehouse_products);




// ------------------------------ Segment 3: Farmers ------------------------------

// SQL query
$sql = "SELECT id, full_name, contact_number, profile_picture, farmer_type, land_size 
        FROM farmers 
        WHERE agent_id = '$agent_id'";

$result = $conn->query($sql);

$farmers = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $farmers[] = [
            'name' => $row['full_name'],
            'area' => $row['land_size'],        // assuming 'land_size' represents area
            'contact' => $row['contact_number'],
            'image' => $row['profile_picture'] ?: 'https://via.placeholder.com/150'
        ];
    }
}

// print_r($farmers);




// ======================================================================
// ======================================================================
// =========================  PHP END ===================================
// ======================================================================
// ======================================================================
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Info - <?= htmlspecialchars($agent['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 p-6">

    <div class="max-w-7xl mx-auto">

        <!-- Segment 1: Agent Info -->
        <div class="bg-white p-6 rounded-lg shadow mb-6 flex flex-col md:flex-row gap-6">
            <img src="<?= '../' . $agent['image'] ?>" alt="<?= htmlspecialchars($agent['name']) ?>" class="w-40 h-40 rounded-lg object-cover mx-auto md:mx-0">
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($agent['name']) ?></h2>
                <p class="text-gray-600 mb-1">Email: <?= htmlspecialchars($agent['email']) ?></p>
                <p class="text-gray-600 mb-1">Phone: <?= htmlspecialchars($agent['phone']) ?></p>
                <p class="text-gray-600 mb-1">Area: <?= htmlspecialchars($agent['area']) ?></p>
                <p class="text-gray-600 mb-1">Warehouse: <?= htmlspecialchars($product_stored_in_warehouse) ?></p>
                <p class="text-gray-600 mb-1">Farmers Added: <?= $total_farmar_added ?></p>
            </div>
        </div>

        <!--====================== Segment 2: Warehouse Products ======================-->
        <h3 class="text-xl font-semibold mb-4">Products Stored in Warehouses</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <?php foreach ($warehouse_products as $wp): ?>

                <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center">

                    <img src="<?= '../../' . $wp['image'] ?>" alt="<?= htmlspecialchars($wp['product']) ?>" class="w-32 h-32 object-cover rounded mb-2">

                    <h4 class="font-bold"><?= htmlspecialchars($wp['product']) ?></h4>
                    <p class="text-gray-500"><?= htmlspecialchars($wp['quantity']) ?> <?= htmlspecialchars($wp['unit']) ?></p>
                    <p class="text-gray-500"><?= htmlspecialchars($wp['warehouse']) ?></p>
                </div>

            <?php endforeach; ?>
        </div>

        <!--====================== Segment 3: Farmers Added ======================-->
        <h3 class="text-xl font-semibold mb-4">Farmers Added</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($farmers as $f): ?>
                <div class="bg-white rounded-lg shadow p-4">
                    <h4 class="font-bold"><?= htmlspecialchars($f['name']) ?></h4>
                    <p class="text-gray-500">Area: <?= htmlspecialchars($f['area']) ?></p>
                    <p class="text-gray-500">Contact: <?= htmlspecialchars($f['contact']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</body>

</html>