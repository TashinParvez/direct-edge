<?php include '../Include/SidebarAgent.php'; ?>
<link rel="stylesheet" href="../Include/sidebar.css">
<?php


// Database connection
include '../include/connect-db.php';

$agent_id = $_SESSION['user_id'];

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query to fetch agent's inventory requests
$where_clauses = ["wp.agent_id = ?"];

if (!empty($search)) {
    $search_param = "%{$search}%";
    $where_clauses[] = "(p.name LIKE ? OR w.name LIKE ?)";
}

if ($status_filter !== '') {
    $where_clauses[] = "wp.request_status = ?";
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "SELECT wp.*, 
        p.name as product_name, 
        p.category as product_category,
        p.unit as product_unit,
        w.name as warehouse_name,
        w.location as warehouse_location
        FROM warehouse_products wp
        INNER JOIN products p ON wp.product_id = p.product_id
        INNER JOIN warehouses w ON wp.warehouse_id = w.warehouse_id
        WHERE {$where_sql}
        ORDER BY wp.last_updated DESC";

$stmt = $conn->prepare($sql);

// Bind parameters dynamically
if ($status_filter !== '' && $search) {
    $stmt->bind_param("issi", $agent_id, $search_param, $search_param, $status_filter);
} elseif ($status_filter !== '') {
    $stmt->bind_param("ii", $agent_id, $status_filter);
} elseif ($search) {
    $stmt->bind_param("iss", $agent_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $agent_id);
}

$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN request_status = 0 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN request_status = 1 THEN 1 ELSE 0 END) as approved,
    SUM(quantity) as total_quantity
FROM warehouse_products WHERE agent_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $agent_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Requests - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>

<body class="bg-gray-100">

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-green-600 via-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 mb-6 text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold mb-2">📦 Inventory Requests</h1>
                    <p class="text-green-100">Manage and track your warehouse product requests</p>
                </div>
                <a href="inventory-request.php" class="px-6 py-3 bg-white text-green-600 rounded-xl hover:bg-green-50 font-bold shadow-lg transition-all hover:scale-105">
                    <i class="fas fa-plus-circle mr-2"></i>Submit New Request
                </a>
            </div>
        </div>

        <!-- Statistics Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition border-t-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Total Requests</p>
                        <p class="text-3xl font-extrabold text-green-600"><?php echo $stats['total'] ?? 0; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-clipboard-list text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition border-t-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Pending</p>
                        <p class="text-3xl font-extrabold text-yellow-600"><?php echo $stats['pending'] ?? 0; ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-hourglass-half text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition border-t-4 border-emerald-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Approved</p>
                        <p class="text-3xl font-extrabold text-emerald-600"><?php echo $stats['approved'] ?? 0; ?></p>
                    </div>
                    <div class="bg-emerald-100 p-3 rounded-full">
                        <i class="fas fa-check-double text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition border-t-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Total Items</p>
                        <p class="text-3xl font-extrabold text-blue-600"><?php echo $stats['total_quantity'] ?? 0; ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-boxes text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search products, warehouses..."
                        class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none text-sm">
                    <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                </div>
                <div class="flex gap-3">
                    <select name="status" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 text-sm font-medium">
                        <option value="">All Status</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Pending</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Approved</option>
                    </select>
                    <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 font-bold transition">
                        Apply
                    </button>
                    <?php if ($search || $status_filter): ?>
                        <a href="my-inventory-requests.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-bold transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Requests List -->
        <?php if (empty($requests)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-16 text-center">
                <div class="text-8xl mb-6">📭</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No Requests Found</h3>
                <p class="text-gray-600 mb-6">You haven't submitted any inventory requests yet</p>
                <a href="inventory-request.php" class="inline-block px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 font-bold shadow-lg transition">
                    <i class="fas fa-plus mr-2"></i>Create Your First Request
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($requests as $index => $request): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="flex flex-col md:flex-row">
                            <!-- Left: Status Indicator -->
                            <div class="w-full md:w-2 <?php echo $request['request_status'] == 1 ? 'bg-gradient-to-b from-green-500 to-emerald-500' : 'bg-gradient-to-b from-yellow-500 to-orange-500'; ?>"></div>

                            <!-- Content -->
                            <div class="flex-1 p-4">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">

                                    <!-- Product Info -->
                                    <div class="flex items-start gap-3 flex-1">
                                        <div class="flex-shrink-0 w-12 h-12 rounded-lg <?php echo $request['request_status'] == 1 ? 'bg-green-100' : 'bg-yellow-100'; ?> flex items-center justify-center">
                                            <i class="fas fa-box-open text-xl <?php echo $request['request_status'] == 1 ? 'text-green-600' : 'text-yellow-600'; ?>"></i>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <h3 class="text-base font-bold text-gray-900 truncate"><?php echo htmlspecialchars($request['product_name']); ?></h3>
                                                <?php if ($request['request_status'] == 1): ?>
                                                    <span class="px-2 py-0.5 bg-green-500 text-white rounded text-xs font-bold">Approved</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 bg-yellow-500 text-white rounded text-xs font-bold">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-xs text-gray-500 mb-1"><?php echo htmlspecialchars($request['product_category']); ?></p>

                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="flex items-center text-gray-700">
                                                    <i class="fas fa-warehouse text-green-600 mr-1 text-xs"></i>
                                                    <strong><?php echo htmlspecialchars($request['warehouse_name']); ?></strong>
                                                </span>
                                                <span class="text-gray-300">•</span>
                                                <span class="text-gray-600 truncate max-w-xs">
                                                    <i class="fas fa-map-marker-alt text-red-500 mr-1 text-xs"></i>
                                                    <?php echo htmlspecialchars($request['warehouse_location']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Metrics -->
                                    <div class="flex items-center gap-3">
                                        <div class="text-center px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                                            <p class="text-xs text-green-600 font-semibold">Quantity</p>
                                            <p class="text-lg font-bold text-green-700"><?php echo $request['quantity']; ?></p>
                                            <p class="text-xs text-gray-600"><?php echo $request['product_unit']; ?></p>
                                        </div>

                                        <div class="text-center px-3 py-2 bg-blue-50 rounded-lg border border-blue-200">
                                            <p class="text-xs text-blue-600 font-semibold">Volume</p>
                                            <p class="text-lg font-bold text-blue-700"><?php echo number_format($request['unit_volume'], 1); ?></p>
                                            <p class="text-xs text-gray-600">m³</p>
                                        </div>

                                        <button onclick="toggleDetails('request-<?php echo $index; ?>')"
                                            class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold text-sm">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Expandable Details -->
                                <div id="request-<?php echo $index; ?>" class="hidden mt-3 pt-3 border-t border-gray-200">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-600 font-semibold mb-2">📅 Important Dates</p>
                                            <?php if ($request['inbound_stock_date']): ?>
                                                <p class="text-xs text-gray-700 mb-1">
                                                    <i class="fas fa-arrow-down text-blue-500 mr-1"></i>
                                                    Inbound: <strong><?php echo date('M d, Y', strtotime($request['inbound_stock_date'])); ?></strong>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($request['expiry_date']): ?>
                                                <p class="text-xs text-gray-700">
                                                    <i class="fas fa-calendar-times text-red-500 mr-1"></i>
                                                    Expires: <strong><?php echo date('M d, Y', strtotime($request['expiry_date'])); ?></strong>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-600 font-semibold mb-2">📊 Volume Details</p>
                                            <p class="text-xs text-gray-700 mb-1">Unit Volume: <strong><?php echo number_format($request['unit_volume'], 2); ?> m³</strong></p>
                                            <p class="text-xs text-gray-700">Total: <strong><?php echo number_format($request['quantity'] * $request['unit_volume'], 2); ?> m³</strong></p>
                                        </div>

                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-600 font-semibold mb-2">🕒 Last Updated</p>
                                            <p class="text-xs text-gray-700"><?php echo date('M d, Y g:i A', strtotime($request['last_updated'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleDetails(id) {
            const element = document.getElementById(id);
            const button = event.currentTarget.querySelector('i');

            if (element.classList.contains('hidden')) {
                element.classList.remove('hidden');
                button.classList.remove('fa-chevron-down');
                button.classList.add('fa-chevron-up');
            } else {
                element.classList.add('hidden');
                button.classList.remove('fa-chevron-up');
                button.classList.add('fa-chevron-down');
            }
        }
    </script>

    <?php include '../include/footer.php'; ?>

</body>

</html>