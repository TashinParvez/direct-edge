<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php
include '../../include/connect-db.php';
// include '../../include/navbar.php';
$admin_id = isset($user_id) ? $user_id : 65;
// ----------- Placeholder sample data -----------
$search = $_GET['search'] ?? '';
$area_filter = $_GET['area'] ?? '';
$warehouse_filter = $_GET['warehouse'] ?? '';
$farmer_count_filter = $_GET['farmer_count'] ?? '';

// Get all distinct areas (cities) from database
$areas_sql = "SELECT DISTINCT name FROM cities ORDER BY name";
$areas_result = mysqli_query($conn, $areas_sql);
$areas = [];
while ($area_row = mysqli_fetch_assoc($areas_result)) {
    $areas[] = $area_row['name'];
}

// Get all warehouses from database
$warehouses_sql = "SELECT DISTINCT name FROM warehouses WHERE status = 'Active' ORDER BY name";
$warehouses_result = mysqli_query($conn, $warehouses_sql);
$warehouses = [];
while ($wh_row = mysqli_fetch_assoc($warehouses_result)) {
    $warehouses[] = $wh_row['name'];
}

// ===============  agents data ======================
// Build WHERE conditions
$where_conditions = ["ai.status = 'Approved'"];

if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(u.full_name LIKE '%$search_escaped%' OR u.email LIKE '%$search_escaped%')";
}

if (!empty($area_filter)) {
    $area_escaped = mysqli_real_escape_string($conn, $area_filter);
    $where_conditions[] = "c.name = '$area_escaped'";
}

$where_sql = implode(' AND ', $where_conditions);

$sql = "SELECT 
            u.user_id AS id,
            u.full_name AS name,
            u.email,
            u.phone,
            c.name AS area,
            GROUP_CONCAT(DISTINCT w.name ORDER BY w.name SEPARATOR ', ') AS warehouse,
            u.image_url AS image,
            COUNT(DISTINCT f.id) AS farmer_count,
            COUNT(DISTINCT wp.id) AS product_count
        FROM users u
        INNER JOIN agent_info ai ON u.user_id = ai.agent_id
        LEFT JOIN agent_assigned_cities aac ON u.user_id = aac.agent_id
        LEFT JOIN cities c ON aac.city_id = c.city_id
        LEFT JOIN warehouses w ON c.city_id = w.warehouse_id
        LEFT JOIN farmers f ON u.user_id = f.agent_id
        LEFT JOIN warehouse_products wp ON u.user_id = wp.agent_id
        WHERE $where_sql
        GROUP BY u.user_id, u.full_name, u.email, u.phone, c.name, u.image_url
        ORDER BY u.full_name ASC";

$result = mysqli_query($conn, $sql);
$agents = [];

while ($row = mysqli_fetch_assoc($result)) {
    $agents[] = [
        'id'            => $row['id'],
        'name'          => $row['name'],
        'email'         => $row['email'],
        'phone'         => $row['phone'],
        'area'          => $row['area'] ?? 'N/A',
        'warehouse'     => $row['warehouse'] ?? 'N/A',
        'image'         => $row['image'],
        'farmer_count'  => (int)$row['farmer_count'],
        'product_count' => (int)$row['product_count']
    ];
}

// ----------- Filter & Search Logic (frontend) -----------

$agents_filtered = array_filter($agents, function ($a) use ($warehouse_filter, $farmer_count_filter) {
    $ok = true;

    if ($warehouse_filter) {
        $ok = $ok && (stripos($a['warehouse'], $warehouse_filter) !== false);
    }

    if ($farmer_count_filter) {
        $ok = $ok && ($a['farmer_count'] >= (int)$farmer_count_filter);
    }

    return $ok;
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Agents - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../../assets/Logo/LogoBG.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .agent-card {
            transition: all 0.3s ease;
        }

        .agent-card:hover {
            background-color: #f9fafb;
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .filter-container {
            transition: all 0.3s ease;
        }

        .filter-container:hover {
            background-color: #f9fafb;
        }

        .search-field {
            transition: all 0.3s ease;
        }

        .search-field:hover {
            background-color: #f3f4f6;
        }

        .search-field:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .agent-image {
            transition: all 0.3s ease;
        }

        .agent-card:hover .agent-image {
            transform: scale(1.05);
        }

        .agents-grid {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-state {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100">

    <section class="home-section p-0">
        <div class="flex justify-between items-center p-4">
            <h1 class="text-2xl font-bold">All Agents</h1>
        </div>

        <div class="container mx-auto px-4">

            <!-- Search & Filters -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-6 filter-container">
                <form method="GET" class="flex flex-col md:flex-row gap-4">

                    <div class="flex-1">
                        <input type="text" name="search" placeholder="Search by name or email"
                            value="<?= htmlspecialchars($search) ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                    </div>

                    <select name="area"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                        <option value="">All Areas</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a ?>" <?= $area_filter === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="warehouse"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                        <option value="">All Warehouses</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w ?>" <?= $warehouse_filter === $w ? 'selected' : '' ?>><?= $w ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="farmer_count"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                        <option value="">Min Farmers Added</option>
                        <option value="5" <?= $farmer_count_filter === '5' ? 'selected' : '' ?>>5+</option>
                        <option value="10" <?= $farmer_count_filter === '10' ? 'selected' : '' ?>>10+</option>
                        <option value="15" <?= $farmer_count_filter === '15' ? 'selected' : '' ?>>15+</option>
                        <option value="20" <?= $farmer_count_filter === '20' ? 'selected' : '' ?>>20+</option>
                    </select>

                    <button type="submit"
                        class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors font-medium">
                        Filter
                    </button>
                </form>
            </div>

            <!-- Results Summary -->
            <?php if (!empty($search) || !empty($area_filter) || !empty($warehouse_filter) || !empty($farmer_count_filter)): ?>
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class='bx bx-info-circle text-blue-600 mr-2'></i>
                        <span class="text-blue-800 font-medium">
                            <?php echo count($agents_filtered); ?>
                            agent<?php echo count($agents_filtered) != 1 ? 's' : ''; ?> found
                            <?php if (!empty($search)): ?>for "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                        </span>
                        <a href="all-agents.php" class="ml-4 text-blue-600 hover:text-blue-800 underline text-sm">Clear
                            filters</a>
                    </div>
                </div>
            <?php endif; ?>

            <!--  ======================  Agents Cards Grid   ====================== -->
            <?php if (count($agents_filtered) === 0): ?>
                <div class="bg-white shadow-xl rounded-lg p-16 text-center empty-state">
                    <i class='bx bx-group text-gray-300 text-8xl mb-6'></i>
                    <h3 class="text-2xl font-semibold text-gray-600 mb-4">No agents found</h3>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">
                        <?php echo (!empty($search) || !empty($area_filter) || !empty($warehouse_filter) || !empty($farmer_count_filter)) ?
                            'Try adjusting your search criteria or browse all agents.' :
                            'No agents are currently registered in the system.'; ?>
                    </p>
                    <?php if (!empty($search) || !empty($area_filter) || !empty($warehouse_filter) || !empty($farmer_count_filter)): ?>
                        <a href="all-agents.php"
                            class="inline-flex items-center bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                            <i class='bx bx-list-ul mr-2'></i>View All Agents
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 agents-grid">
                    <?php foreach ($agents_filtered as $agent): ?>
                        <a href="one-agent-info.php?id=<?= $agent['id'] ?>"
                            class="bg-white rounded-xl shadow-md hover:shadow-xl overflow-hidden agent-card border border-gray-100">

                            <!-- Agent Image -->
                            <div class="h-48 bg-gray-200 overflow-hidden relative">
                                <div class="w-full h-full bg-green-100 flex items-center justify-center agent-image">
                                    <i class='bx bx-user text-5xl text-green-600'></i>
                                </div>

                                <!-- Status Badge -->
                                <div class="absolute top-4 right-4">
                                    <div class="w-3 h-3 bg-green-500 rounded-full border-2 border-white shadow-sm"></div>
                                </div>
                            </div>

                            <!-- Agent Information -->
                            <div class="p-6">
                                <h2 class="font-bold text-xl text-gray-900 mb-3"><?= htmlspecialchars($agent['name']) ?></h2>

                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class='bx bx-envelope mr-3 text-gray-400'></i>
                                        <span class="truncate"><?= htmlspecialchars($agent['email']) ?></span>
                                    </div>

                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class='bx bx-phone mr-3 text-gray-400'></i>
                                        <span><?= htmlspecialchars($agent['phone']) ?></span>
                                    </div>

                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class='bx bx-map mr-3 text-gray-400'></i>
                                        <span><?= htmlspecialchars($agent['area']) ?></span>
                                    </div>

                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class='bx bx-building mr-3 text-gray-400'></i>
                                        <span><?= htmlspecialchars($agent['warehouse']) ?></span>
                                    </div>
                                </div>

                                <!-- Agent Stats -->
                                <div class="mt-5 pt-4 border-t border-gray-100">
                                    <div class="flex justify-between text-sm">
                                        <div class="text-center">
                                            <div class="font-bold text-gray-900"><?= $agent['farmer_count'] ?></div>
                                            <div class="text-gray-500">Farmers</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-bold text-gray-900"><?= $agent['product_count'] ?></div>
                                            <div class="text-gray-500">Products</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-bold text-green-600">Active</div>
                                            <div class="text-gray-500">Status</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- View Profile -->
                                <div class="mt-5">
                                    <div
                                        class="bg-gray-50 hover:bg-gray-100 text-center py-3 rounded-lg text-sm font-medium text-gray-700 transition-colors">
                                        <i class='bx bx-show mr-2'></i>View Profile
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>
</body>

</html>