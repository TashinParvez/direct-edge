<?php
// orders.php
include '../../include/connect-db.php'; // database connection

// Get all status options for the dropdown
$statusOptions = ['Pending', 'Approved', 'Shipped', 'Delivered', 'Cancelled'];

// Handle status update from modal (support AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateOrderStatus'])) {
    $orderId = $_POST['order_id'] ?? null;
    $newStatus = $_POST['order_status'] ?? null;

    if ($orderId && in_array($newStatus, $statusOptions)) {
        $stmtUp = $conn->prepare("UPDATE orders SET status = ?, updated_at=NOW() WHERE order_id = ?");
        $stmtUp->bind_param("si", $newStatus, $orderId);
        if ($stmtUp->execute()) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => true, 'new_status' => $newStatus]);
                exit;
            }
        } else {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false]);
                exit;
            }
        }
        header("Location: orders.php");
        exit;
    }
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$perPage = 10; // Orders per page
$offset = ($page - 1) * $perPage;

// Build dynamic SQL for all orders
$sql = "SELECT 
            o.order_id,
            u.full_name AS shopowner_name,
            o.placed_at,
            o.updated_at,
            o.status, 
            total_order.total_quantity,
            total_order.total_amount
        FROM orders o
        JOIN users u ON o.shopowner_id = u.user_id
        JOIN (
            SELECT order_id, SUM(quantity) AS total_quantity, SUM(total_price) AS total_amount
            FROM order_items
            GROUP BY order_id
        ) AS total_order ON total_order.order_id = o.order_id
        WHERE 1=1";

$params = [];
$types = "";

// Search by product name (joins with order_items and products)
if ($search) {
    $sql .= " AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = o.order_id AND p.name LIKE ?)";
    $params[] = "%$search%";
    $types .= "s";
}

// Filter by status
if ($filterStatus && in_array($filterStatus, $statusOptions)) {
    $sql .= " AND o.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

// Filter by date
if ($filterDate) {
    switch ($filterDate) {
        case 'today':
            $sql .= " AND DATE(o.placed_at) = CURDATE()";
            break;
        case 'this_week':
            $sql .= " AND YEARWEEK(o.placed_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'this_month':
            $sql .= " AND MONTH(o.placed_at) = MONTH(CURDATE()) AND YEAR(o.placed_at) = YEAR(CURDATE())";
            break;
    }
}

$sql .= " ORDER BY o.placed_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

// Prepare and execute
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all orders as array
$ordersArr = [];
while ($row = $result->fetch_assoc()) {
    $ordersArr[] = $row;
}

// Total orders for pagination
$totalSql = "SELECT COUNT(*) as total FROM orders";
$totalRes = $conn->query($totalSql);
$totalOrders = $totalRes->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalOrders / $perPage);

// Get all products per order for detail modal (for current page orders only to optimize)
$orderProducts = [];
if (count($ordersArr) > 0) {
    $orderIn = implode(',', array_map('intval', array_column($ordersArr, 'order_id')));
    $prodQ = "
        SELECT 
            oi.order_id,
            oi.product_id,
            p.name,
            oi.quantity,
            oi.unit_price,
            oi.total_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id IN ($orderIn)
        ORDER BY oi.order_id, oi.product_id
    ";
    $prodRes = $conn->query($prodQ);
    while ($row = $prodRes->fetch_assoc()) {
        $orderProducts[$row['order_id']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 p-8">

    <div class="max-w-7xl mx-auto bg-white shadow rounded-lg p-6">
        <h1 class="text-3xl font-bold text-blue-700 mb-6">All Orders</h1>

        <!-- Search & Filters -->
        <form method="GET" id="filterForm" class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by product, warehouse or status..."
                class="border p-2 rounded w-full md:w-full focus:ring focus:ring-blue-200">
            <div class="flex gap-2">
                <select name="status" class="border p-2 rounded w-full md:w-auto">
                    <option value="">Filter by Status</option>
                    <?php foreach ($statusOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($filterStatus == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="date" class="border p-2 rounded w-full md:w-auto">
                    <option value="">Filter by Date</option>
                    <option value="today" <?= ($filterDate == 'today') ? 'selected' : '' ?>>Today</option>
                    <option value="this_week" <?= ($filterDate == 'this_week') ? 'selected' : '' ?>>This Week</option>
                    <option value="this_month" <?= ($filterDate == 'this_month') ? 'selected' : '' ?>>This Month</option>
                </select>
            </div>
        </form>

        <!-- Orders Table -->
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3 text-left">Order ID</th>
                        <th class="p-3 text-left">Placed At</th>
                        <th class="p-3 text-left">Updated At</th>
                        <th class="p-3 text-left">Products</th>
                        <th class="p-3 text-left">Total Qty</th>
                        <th class="p-3 text-left">Total Amount</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($ordersArr)): ?>
                        <tr>
                            <td colspan="8" class="p-3 text-center">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ordersArr as $order): ?>
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="p-3 font-medium"><?= $order['order_id'] ?></td>
                                <td class="p-3"><?= date('d M, Y', strtotime($order['placed_at'])) ?></td>
                                <td class="p-3"><?= date('d M, Y', strtotime($order['updated_at'])) ?></td>
                                <td class="p-3 text-sm leading-relaxed">
                                    <table>
                                        <?php if (isset($orderProducts[$order['order_id']])) : ?>
                                            <?php foreach ($orderProducts[$order['order_id']] as $item) : ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </table>
                                </td>
                                <td class="p-3 align-top">
                                    <table>
                                        <?php if (isset($orderProducts[$order['order_id']])) : ?>
                                            <?php foreach ($orderProducts[$order['order_id']] as $item) : ?>
                                                <tr>
                                                    <td><?= $item['quantity'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </table>
                                </td>
                                <td class="p-3">$<?= number_format($order['total_amount'], 2) ?></td>
                                <td class="p-3">
                                    <?php
                                    $statusColor = match ($order['status']) {
                                        'Pending' => 'text-yellow-600',
                                        'Approved' => 'text-green-600',
                                        'Shipped' => 'text-blue-600',
                                        'Delivered' => 'text-gray-600',
                                        'Cancelled' => 'text-red-600',
                                        default => 'text-gray-600'
                                    };
                                    ?>
                                    <span class="<?= $statusColor ?> font-semibold"><?= $order['status'] ?></span>
                                </td>
                                <td class="p-3">
                                    <a href="javascript:void(0);" onclick="openOrderModal(<?= $order['order_id'] ?>)"
                                        class="text-blue-600 font-semibold underline">See Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&date=<?= urlencode($filterDate) ?>"
                    class="px-3 py-1 border rounded hover:bg-gray-100 <?= ($page == $i) ? 'bg-blue-100' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>

        <!-- Modals: One per order -->
        <?php foreach ($ordersArr as $order): ?>
            <div id="order-modal-<?= $order['order_id'] ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white w-full max-w-lg rounded-lg shadow-lg p-6 relative">
                    <!-- Close -->
                    <button class="absolute top-2 right-2 text-2xl" onclick="closeOrderModal(<?= $order['order_id'] ?>)">&times;</button>
                    <h2 class="text-xl font-bold mb-4">Order #<?= $order['order_id'] ?> Details</h2>
                    <div class="mb-2"><strong>Placed At:</strong> <?= date('d M, Y H:i', strtotime($order['placed_at'])) ?></div>
                    <div class="mb-2"><strong>Updated At:</strong> <?= date('d M, Y H:i', strtotime($order['updated_at'])) ?></div>
                    <div class="mb-2"><strong>Shop Owner:</strong> <?= htmlspecialchars($order['shopowner_name']) ?></div>
                    <div class="mb-2"><strong>Status:</strong>
                        <select id="status-select-<?= $order['order_id'] ?>" class="border rounded py-1 px-3">
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($order['status'] == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="updateStatus(<?= $order['order_id'] ?>)" class="ml-2 bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">Save</button>
                    </div>
                    <div class="my-2">
                        <strong>Products:</strong>
                        <div>
                            <table class="min-w-full text-sm border border-gray-200 mt-2">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-1 border">Product</th>
                                        <th class="px-2 py-1 border">Qty</th>
                                        <th class="px-2 py-1 border">Unit Price</th>
                                        <th class="px-2 py-1 border">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $orderTotal = 0;
                                    if (isset($orderProducts[$order['order_id']])):
                                        foreach ($orderProducts[$order['order_id']] as $item):
                                            $orderTotal += $item['total_price'];
                                    ?>
                                            <tr>
                                                <td class="px-2 py-1 border"><?= htmlspecialchars($item['name']) ?></td>
                                                <td class="px-2 py-1 border"><?= $item['quantity'] ?></td>
                                                <td class="px-2 py-1 border">$<?= number_format($item['unit_price'], 2) ?></td>
                                                <td class="px-2 py-1 border">$<?= number_format($item['total_price'], 2) ?></td>
                                            </tr>
                                    <?php endforeach;
                                    endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-bold bg-gray-100">
                                        <td class="px-2 py-1 border" colspan="3">Total</td>
                                        <td class="px-2 py-1 border">$<?= number_format($order['total_amount'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="button" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700" onclick="closeOrderModal(<?= $order['order_id'] ?>)">Close</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <script>
            // Auto-submit filter on change
            document.querySelectorAll('select[name="status"], select[name="date"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    this.form.submit();
                });
            });
            document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });

            function openOrderModal(orderId) {
                document.getElementById('order-modal-' + orderId).classList.remove('hidden');
            }

            function closeOrderModal(orderId) {
                document.getElementById('order-modal-' + orderId).classList.add('hidden');
            }

            function updateStatus(orderId) {
                const select = document.getElementById('status-select-' + orderId);
                const newStatus = select.value;

                fetch('orders.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `updateOrderStatus=1&order_id=${orderId}&order_status=${newStatus}&ajax=1`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Status updated to ' + data.new_status);
                            location.reload();
                        } else {
                            alert('Failed to update status');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        </script>

    </div>
</body>

</html>