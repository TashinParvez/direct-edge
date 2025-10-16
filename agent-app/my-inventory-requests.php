<?php
include '../include/navbar.php';

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
    <title>My Inventory Requests - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>

<body class="bg-gray-50">

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">My Inventory Requests</h2>
                    <p class="text-sm text-gray-600 mt-1">Track and manage your warehouse inventory requests</p>
                </div>
                <a href="inventory-request.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm shadow transition">
                    <i class="fas fa-plus mr-1"></i>New Request
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-600 mb-1">Total Requests</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                <p class="text-xs text-gray-600 mb-1">Pending</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending'] ?? 0; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                <p class="text-xs text-gray-600 mb-1">Approved</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['approved'] ?? 0; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
                <p class="text-xs text-gray-600 mb-1">Total Quantity</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_quantity'] ?? 0; ?></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search by product or warehouse..."
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                <select name="status" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All Status</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Pending</option>
                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Approved</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                    <i class="fas fa-search mr-1"></i>Filter
                </button>
                <a href="my-inventory-requests.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium text-center">
                    <i class="fas fa-redo mr-1"></i>Clear
                </a>
            </form>
        </div>

        <!-- Requests Table -->
        <?php if (empty($requests)): ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <div class="text-5xl mb-3">📦</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No Inventory Requests Found</h3>
                <p class="text-sm text-gray-600 mb-4">Start by submitting your first inventory request</p>
                <a href="inventory-request.php" class="inline-block px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                    <i class="fas fa-plus mr-1"></i>Create Request
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Warehouse</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Volume</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Dates</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <!-- Product -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($request['product_name']); ?></p>
                                            <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($request['product_category']); ?></p>
                                        </div>
                                    </td>

                                    <!-- Warehouse -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($request['warehouse_name']); ?></p>
                                            <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($request['warehouse_location']); ?></p>
                                        </div>
                                    </td>

                                    <!-- Quantity -->
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-semibold text-gray-900"><?php echo $request['quantity']; ?> <?php echo $request['product_unit']; ?></span>
                                    </td>

                                    <!-- Volume -->
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-700"><?php echo number_format($request['unit_volume'], 2); ?> m³</span>
                                    </td>

                                    <!-- Dates -->
                                    <td class="px-6 py-4">
                                        <div class="text-xs">
                                            <?php if ($request['inbound_stock_date']): ?>
                                                <p class="text-gray-600"><i class="fas fa-arrow-down text-blue-500 mr-1"></i><?php echo date('M d, Y', strtotime($request['inbound_stock_date'])); ?></p>
                                            <?php endif; ?>
                                            <?php if ($request['expiry_date']): ?>
                                                <p class="text-gray-600"><i class="fas fa-calendar-times text-red-500 mr-1"></i><?php echo date('M d, Y', strtotime($request['expiry_date'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4">
                                        <?php if ($request['request_status'] == 1): ?>
                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                                <i class="fas fa-check-circle mr-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                                <i class="fas fa-clock mr-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4">
                                        <button onclick="viewDetails(<?php echo htmlspecialchars(json_encode($request), ENT_QUOTES); ?>)"
                                            class="text-blue-600 hover:text-blue-800 transition" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Request Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-3xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Request Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="modalContent" class="space-y-4">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function viewDetails(request) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');

            const statusBadge = request.request_status == 1 ?
                '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold"><i class="fas fa-check-circle mr-1"></i>Approved</span>' :
                '<span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold"><i class="fas fa-clock mr-1"></i>Pending</span>';

            content.innerHTML = `
                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg mb-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-lg font-bold text-gray-800">${request.product_name}</h4>
                            <p class="text-sm text-gray-600">${request.product_category || 'N/A'}</p>
                        </div>
                        ${statusBadge}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h5 class="font-semibold text-gray-900 mb-3 border-b pb-2">Product Information</h5>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-600">Quantity:</dt><dd class="font-medium">${request.quantity} ${request.product_unit}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Unit Volume:</dt><dd class="font-medium">${parseFloat(request.unit_volume).toFixed(2)} m³</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Total Volume:</dt><dd class="font-medium">${(request.quantity * parseFloat(request.unit_volume)).toFixed(2)} m³</dd></div>
                            ${request.offer_percentage ? `<div class="flex justify-between"><dt class="text-gray-600">Offer:</dt><dd class="font-medium text-green-600">${request.offer_percentage}% OFF</dd></div>` : ''}
                        </dl>
                    </div>

                    <div>
                        <h5 class="font-semibold text-gray-900 mb-3 border-b pb-2">Warehouse Details</h5>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-600">Warehouse:</dt><dd class="font-medium">${request.warehouse_name}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Location:</dt><dd class="font-medium">${request.warehouse_location}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t mt-4">
                    <div>
                        <h5 class="font-semibold text-gray-900 mb-3">Important Dates</h5>
                        <dl class="space-y-2 text-sm">
                            ${request.inbound_stock_date ? `<div class="flex justify-between"><dt class="text-gray-600">Inbound Date:</dt><dd class="font-medium">${new Date(request.inbound_stock_date).toLocaleDateString()}</dd></div>` : ''}
                            ${request.expiry_date ? `<div class="flex justify-between"><dt class="text-gray-600">Expiry Date:</dt><dd class="font-medium text-red-600">${new Date(request.expiry_date).toLocaleDateString()}</dd></div>` : ''}
                            <div class="flex justify-between"><dt class="text-gray-600">Last Updated:</dt><dd class="font-medium">${new Date(request.last_updated).toLocaleString()}</dd></div>
                        </dl>
                    </div>

                    ${request.offer_start || request.offer_end ? `
                    <div>
                        <h5 class="font-semibold text-gray-900 mb-3">Offer Period</h5>
                        <dl class="space-y-2 text-sm">
                            ${request.offer_start ? `<div class="flex justify-between"><dt class="text-gray-600">Start:</dt><dd class="font-medium">${new Date(request.offer_start).toLocaleDateString()}</dd></div>` : ''}
                            ${request.offer_end ? `<div class="flex justify-between"><dt class="text-gray-600">End:</dt><dd class="font-medium">${new Date(request.offer_end).toLocaleDateString()}</dd></div>` : ''}
                        </dl>
                    </div>
                    ` : ''}
                </div>

                <div class="pt-4 border-t mt-4 flex gap-3">
                    <button onclick="closeModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">
                        Close
                    </button>
                </div>
            `;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <?php include '../include/footer.php'; ?>

</body>

</html>