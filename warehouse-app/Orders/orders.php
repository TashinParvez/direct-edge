<?php
// orders.php
include '../../include/connect-db.php'; // database connection

$shop_owner_id = 1; // replace with session user_id later

// Fetch orders with all products grouped per order
$sql = "SELECT 
            o.order_id,
            u.full_name AS shopowner_name,
            o.placed_at,
            o.updated_at,
            o.status, 
            GROUP_CONCAT(CONCAT(p.name,' (Qty: ',oi.quantity,', Unit: ',oi.unit_price,', Total: ',oi.total_price,')') SEPARATOR '<br>') AS products_info,
            total_order.total_quantity,
            total_order.total_amount
        FROM orders o
        JOIN users u ON o.shopowner_id = u.user_id
        JOIN order_items oi ON oi.order_id = o.order_id
        JOIN products p ON oi.product_id = p.product_id
        JOIN (
            SELECT order_id, SUM(quantity) AS total_quantity, SUM(total_price) AS total_amount
            FROM order_items
            GROUP BY order_id
        ) AS total_order ON total_order.order_id = o.order_id
        WHERE u.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.placed_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shop_owner_id);
$stmt->execute();
$orders = $stmt->get_result();
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
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
            <input type="text" placeholder="Search by product, warehouse or status..."
                class="border p-2 rounded w-full md:w-full focus:ring focus:ring-blue-200">
            <div class="flex gap-2">
                <select class="border p-2 rounded w-full md:w-auto">
                    <option value="">Filter by Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <select class="border p-2 rounded w-full md:w-auto">
                    <option value="">Filter by Date</option>
                    <option value="today">Today</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                </select>
            </div>
        </div>

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
                    <?php while ($row = $orders->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="p-3 font-medium"><?= $row['order_id'] ?></td>
                            <td class="p-3"><?= date('d M, Y', strtotime($row['placed_at'])) ?></td>
                            <td class="p-3"><?= date('d M, Y', strtotime($row['updated_at'])) ?></td>
                            <td class="p-3 text-sm leading-relaxed">
                                <?= $row['products_info'] ?>
                            </td>
                            <td class="p-3"><?= $row['total_quantity'] ?></td>
                            <td class="p-3">$<?= number_format($row['total_amount'], 2) ?></td>
                            <td class="p-3">
                                <?php
                                $statusColor = match ($row['status']) {
                                    'Pending' => 'text-yellow-600',
                                    'Approved' => 'text-green-600',
                                    'Shipped' => 'text-blue-600',
                                    'Delivered' => 'text-gray-600',
                                    'Cancelled' => 'text-red-600',
                                    default => 'text-gray-600'
                                };
                                ?>
                                <span class="<?= $statusColor ?> font-semibold"><?= $row['status'] ?></span>
                            </td>
                            <td class="p-3">
                                <?php if ($row['status'] == 'Pending'): ?>
                                    <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Cancel</button>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">No Action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (dummy) -->
        <div class="mt-6 flex justify-center space-x-2">
            <button class="px-3 py-1 border rounded hover:bg-gray-100">1</button>
            <button class="px-3 py-1 border rounded hover:bg-gray-100">2</button>
            <button class="px-3 py-1 border rounded hover:bg-gray-100">3</button>
            <button class="px-3 py-1 border rounded hover:bg-gray-100">Next</button>
        </div>
    </div>

</body>

</html>