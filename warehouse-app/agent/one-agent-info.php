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

// ======================== product_stored_in_warehouse ====================

$sql = "SELECT COUNT(id) AS num
        FROM `warehouse_products`
        WHERE `agent_id` = '$agent_id'
        AND `request_status` = 1";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);
$product_stored_in_warehouse = $row['num']; // This will have the count

// ======================= total_farmar_added =====================

$sql = "SELECT COUNT(id) AS num
        FROM `farmers`
        WHERE  `agent_id` = '$agent_id'";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);
$total_farmar_added = $row['num']; // This will have the count

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Agent Info - <?= htmlspecialchars($agent['name']) ?> - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .agent-profile-card {
            transition: all 0.3s ease;
        }
        .agent-profile-card:hover {
            background-color: #f9fafb;
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            background-color: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .farmer-card {
            transition: all 0.3s ease;
        }
        .farmer-card:hover {
            background-color: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .profile-image {
            transition: all 0.3s ease;
        }
        .profile-image:hover {
            transform: scale(1.05);
        }
        .product-image {
            transition: all 0.3s ease;
        }
        .product-card:hover .product-image {
            transform: scale(1.1);
        }
        .section-grid {
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include '../../Include/Sidebar.php'; ?>
    
    <section class="home-section p-0">
        <div class="flex justify-between items-center p-4">
            <h1 class="text-2xl font-bold">Agent Information</h1>
        </div>

        <div class="container mx-auto px-4">

            <!-- Segment 1: Agent Info -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6 agent-profile-card">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="flex-shrink-0">
                        <?php if (!empty($agent['image']) && file_exists('../' . $agent['image'])): ?>
                            <img src="<?= '../' . $agent['image'] ?>" alt="<?= htmlspecialchars($agent['name']) ?>" 
                                class="w-40 h-40 rounded-lg object-cover mx-auto md:mx-0 profile-image">
                        <?php else: ?>
                            <div class="w-40 h-40 bg-green-100 rounded-lg flex items-center justify-center mx-auto md:mx-0 profile-image">
                                <i class='bx bx-user text-6xl text-green-600'></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold mb-4 text-gray-900"><?= htmlspecialchars($agent['name']) ?></h2>
                        <div class="space-y-2">
                            <p class="text-gray-600 flex items-center">
                                <i class='bx bx-envelope mr-2 text-gray-400'></i>
                                Email: <?= htmlspecialchars($agent['email']) ?>
                            </p>
                            <p class="text-gray-600 flex items-center">
                                <i class='bx bx-phone mr-2 text-gray-400'></i>
                                Phone: <?= htmlspecialchars($agent['phone']) ?>
                            </p>
                            <p class="text-gray-600 flex items-center">
                                <i class='bx bx-map mr-2 text-gray-400'></i>
                                Area: <?= htmlspecialchars($agent['area']) ?>
                            </p>
                            <p class="text-gray-600 flex items-center">
                                <i class='bx bx-package mr-2 text-gray-400'></i>
                                Products in Warehouse: <?= htmlspecialchars($product_stored_in_warehouse) ?>
                            </p>
                            <p class="text-gray-600 flex items-center">
                                <i class='bx bx-group mr-2 text-gray-400'></i>
                                Farmers Added: <?= $total_farmar_added ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!--====================== Segment 2: Warehouse Products ======================-->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">Products Stored in Warehouses</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 section-grid">
                    <?php foreach ($warehouse_products as $wp): ?>
                        <div class="bg-white rounded-lg shadow-md p-6 product-card">
                            <div class="text-center">
                                <div class="mb-4 overflow-hidden rounded-lg">
                                    <img src="<?= '../../' . $wp['image'] ?>" alt="<?= htmlspecialchars($wp['product']) ?>" 
                                        class="w-32 h-32 object-cover rounded-lg mx-auto product-image">
                                </div>
                                <h4 class="font-bold text-lg text-gray-900 mb-2"><?= htmlspecialchars($wp['product']) ?></h4>
                                <p class="text-gray-600 mb-1">
                                    <i class='bx bx-package mr-1'></i>
                                    <?= htmlspecialchars($wp['quantity']) ?> <?= htmlspecialchars($wp['unit']) ?>
                                </p>
                                <p class="text-gray-500">
                                    <i class='bx bx-building mr-1'></i>
                                    <?= htmlspecialchars($wp['warehouse']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!--====================== Segment 3: Farmers Added ======================-->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">Farmers Added</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 section-grid">
                    <?php foreach ($farmers as $f): ?>
                        <div class="bg-white rounded-lg shadow-md p-6 farmer-card">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class='bx bx-user text-2xl text-green-600'></i>
                                </div>
                                <h4 class="font-bold text-lg text-gray-900 mb-2"><?= htmlspecialchars($f['name']) ?></h4>
                                <p class="text-gray-600 mb-1">
                                    <i class='bx bx-map mr-1'></i>
                                    Area: <?= htmlspecialchars($f['area']) ?>
                                </p>
                                <p class="text-gray-600">
                                    <i class='bx bx-phone mr-1'></i>
                                    <?= htmlspecialchars($f['contact']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </section>
</body>

</html>