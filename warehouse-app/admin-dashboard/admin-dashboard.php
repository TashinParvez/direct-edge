<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php


include '../../include/connect-db.php';
// include '../../include/navbar.php';
$admin_id = isset($user_id) ? $user_id : 65;

//-------------------------  Segment 1 ------------------------- 


$sql = "SELECT COUNT(DISTINCT product_id) AS distinct_product_count
        FROM warehouse_products;";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_products = $row['distinct_product_count'];


// ====================================================================

$sql = "SELECT 
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_warehouses,
            SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) AS inactive_warehouses,
            COUNT(*) AS total_warehouses
        FROM warehouses";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$total_warehouses_active = $row['active_warehouses'];
$total_warehouses_inactive = $row['inactive_warehouses'];
$total_warehouses = $row['total_warehouses'];



// ====================================================================


$sql = "SELECT 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_requests,
            SUM(CASE WHEN status = 'Working' THEN 1 ELSE 0 END) AS working_requests,
            COUNT(*) AS total_requests
        FROM stock_requests";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$stock_pending_requests = $row['pending_requests']; // have to write in the cart                tashin
$stock_working_requests = $row['working_requests']; // have to write in the cart                tashin


$pending_stock_requests = $row['total_requests'];


// ====================================================================

// have to write in the cart                tashin

$sql = "SELECT COUNT(DISTINCT product_id) AS distinct_product_count
        FROM stock_requests;";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$pending_Products_requests = $row['distinct_product_count'];

// print($pending_Products_requests);

// ====================================================================


$sql = "SELECT COUNT(DISTINCT `user_id`) AS shop_owner
        FROM users
        WHERE `role` = 'Shop-Owner'";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_shop_owners = $row['shop_owner'];

// print($total_shop_owners);


// ====================================================================


$total_agents = 48;


$sql = "SELECT COUNT(DISTINCT `user_id`) AS total_agents
        FROM users
        WHERE `role` = 'Agent'";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_agents = $row['total_agents'];

// print($total_agents);


// ====================================================================


$current_month_sales = 254000.50; // currency     tashin  have to work here

// ====================================================================

$sql = "SELECT 
            wp.product_id,
            p.name AS product_name,
            SUM(wp.quantity) AS qty
        FROM warehouse_products wp
        JOIN products p ON wp.product_id = p.product_id
        GROUP BY wp.product_id, p.name
        HAVING SUM(wp.quantity) < 100
        LIMIT 3;";

$result = mysqli_query($conn, $sql);

$low_stock_alerts = [];

while ($row = mysqli_fetch_assoc($result)) {
    $low_stock_alerts[] = [
        'product_id' => $row['product_id'],
        'name' => $row['product_name'],
        'qty' => $row['qty']
    ];
}

// print_r($low_stock_alerts);


// ====================================================================


$sql = "SELECT warehouse_id, name, `capacity_total`-`capacity_used` as free_space
        FROM `warehouses` 
        WHERE `status` ='Active' && (`capacity_total`-`capacity_used`) <= 300000
        ORDER BY  free_space ASC
        LIMIT 3;";


$result = mysqli_query($conn, $sql);

$low_space_alerts = [];

while ($row = mysqli_fetch_assoc($result)) {
    $low_space_alerts[] = [
        'warehouse_id' => $row['warehouse_id'],
        'name' => $row['name'],
        'free_space' => $row['free_space']
    ];
}


// print_r($low_space_alerts);

// ====================================================================
// ====================================================================
// ====================================================================



// ------------------------- Segment 2-------------------------


//  ------------------------- Stocks Status    -------------------------

$sql = "SELECT 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_requests,
            SUM(CASE WHEN status = 'Done' THEN 1 ELSE 0 END) AS Done_requests,
            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS Rejected_requests,
            SUM(CASE WHEN status = 'Working' THEN 1 ELSE 0 END) AS working_requests,
            COUNT(*) AS total_requests
        FROM stock_requests";


$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$stock_requests_counts = [
    'Pending' => $row['pending_requests'],
    'Approved' => $row['Done_requests'],
    'Working' => $row['working_requests'],
    'Cancelled' => $row['Rejected_requests']
];

// Example output
// print_r($stock_requests_counts);



// ====================================================================


//  ------------------------- Orders Status (Graph)    -------------------------


$sql = "SELECT 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS Pending,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS Approved,
            SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) AS Shipped,
            SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) AS Delivered,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS Cancelled
        FROM orders;";


$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$order_counts = [
    'Pending' => $row['Pending'],
    'Approved' => $row['Approved'],
    'Shipped' => $row['Shipped'],
    'Completed' => $row['Delivered'],
    'Rejected' => $row['Cancelled']
];


// ====================================================================

//  ------------------------- Recent Orders   -------------------------


$sql = "SELECT 
            o.order_id,
            u.full_name AS shop_name,
            o.placed_at,
            o.total_amount AS total,
            o.status
        FROM orders o
        JOIN users u ON o.shopowner_id = u.user_id
        ORDER BY o.placed_at DESC;";

$result = mysqli_query($conn, $sql);

$recent_orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    $recent_orders[] = [
        'order_id'   => $row['order_id'],
        'shop_name'  => $row['shop_name'],
        'placed_at'  => $row['placed_at'],
        'total'      => $row['total'],
        'status'     => $row['status']
    ];
}

// print_r($recent_orders);


//  ------------------------------- Segment 3: Warehouses stats (capacity in cubic units) ------------------------------- 

// =============================== Warehouse Capacity stat (used vs free) =====================================


$sql = "SELECT 
            warehouse_id,
            name,
            capacity_total,
            capacity_used
        FROM warehouses
        WHERE status = 'Active'
        ORDER BY name ASC;";


$result = mysqli_query($conn, $sql);

$warehouses_stats = [];

while ($row = mysqli_fetch_assoc($result)) {
    $warehouses_stats[] = [
        'warehouse_id'   => $row['warehouse_id'],
        'name'           => $row['name'],
        'capacity_total' => $row['capacity_total'],
        'capacity_used'  => $row['capacity_used']
    ];
}

// Example output
// print_r($warehouses_stats);



// ============================= Stock In/Out History =======================================



// Stock history sample (recent)
$stock_history = [
    ['date' => '2025-09-20', 'type' => 'IN', 'product' => 'Rice (25kg)', 'qty' => 100],
    ['date' => '2025-09-19', 'type' => 'OUT', 'product' => 'Sugar (1kg)', 'qty' => 50],
    ['date' => '2025-09-18', 'type' => 'IN', 'product' => 'Oil 1L', 'qty' => 200],
];



// ====================================================================


// ------------------------------- Segment 4: Revenue (last 3 months) -------------------------------


$revenue_last_3_months = [
    ['label' => 'Jul 2025', 'amount' => 120000],
    ['label' => 'Aug 2025', 'amount' => 210000],
    ['label' => 'Sep 2025', 'amount' => 254000],
];

// Segment 5: Activity logs
$activity_logs = [
    ['user' => 'admin', 'action' => 'Approved stock request #45', 'time' => '2025-09-20 09:22'],
    ['user' => 'agent_12', 'action' => 'Uploaded product image for Mango', 'time' => '2025-09-19 17:05'],
    ['user' => 'shop_34', 'action' => 'Placed order #1201', 'time' => '2025-09-18 10:33'],
];

// -------------------------
// End placeholder data
// -------------------------
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body class="bg-gray-50 text-gray-800">
    <div class="max-w-7xl mx-auto p-6">

        <!-- Header -->
        <header class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Admin Dashboard</h1>
            </div>
            <div class="flex items-center gap-3">

                <button class="bg-[#16a34a] border px-3 py-2 rounded shadow-sm hover:bg-green-700 text-white"
                    onclick="window.location.href='../add-warehouse.php'">
                    Add Warehouse
                </button>

                <button class="bg-[#16a34a] border px-3 py-2 rounded shadow-sm hover:bg-gray-100 text-white"
                    onclick="window.location.href='../Manage-Warehouse/warehouse-status.php'">
                    Warehouse Status
                </button>

                <button class="bg-[#16a34a] border px-3 py-2 rounded shadow-sm hover:bg-gray-100 text-white"
                    onclick="window.location.href='admin-agent-management.php'">
                    Agent Management
                </button>


            </div>
        </header>




        <!-- ================= Segment 1: Overview & Insights ================= -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Overview & Insights</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Total Products -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Total Products</div>
                    <div class="mt-2 text-2xl font-bold"><?= number_format($total_products) ?></div>
                </div>

                <!-- Warehouses -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Total Warehouses</div>
                    <div class="mt-2 flex items-baseline gap-3">
                        <div class="text-2xl font-bold"><?= $total_warehouses_active + $total_warehouses_inactive ?>
                        </div>
                        <div class="text-sm text-gray-600">Active: <span
                                class="font-semibold text-green-600"><?= $total_warehouses_active ?></span></div>
                        <div class="text-sm text-gray-600">Inactive: <span
                                class="font-semibold text-red-600"><?= $total_warehouses_inactive ?></span></div>
                    </div>
                </div>

                <!-- Pending Stock Requests -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Pending Stock Requests</div>
                    <div class="mt-2 text-2xl font-bold text-yellow-600"><?= $pending_stock_requests ?></div>
                </div>

                <!-- Total Shop Owners -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Total Shop Owners</div>
                    <div class="mt-2 text-2xl font-bold"><?= $total_shop_owners ?></div>
                </div>

                <!-- Total Agents -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Total Agents</div>
                    <div class="mt-2 text-2xl font-bold"><?= $total_agents ?></div>
                </div>

                <!-- Sales this month -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Sales (Current Month)</div>
                    <div class="mt-2 text-2xl font-bold"><?= number_format($current_month_sales, 2) ?></div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">Low Stock Alerts</div>
                        <a href="../warehouse information/product-list.php" class="text-sm text-blue-600">View all</a>
                    </div>
                    <div class="mt-2 space-y-1">
                        <?php foreach ($low_stock_alerts as $l): ?>
                            <div class="text-sm flex justify-between">
                                <div><?= htmlspecialchars($l['name']) ?></div>
                                <div class="font-semibold text-red-600"><?= $l['qty'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Low Space Alerts -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">Low Space Alerts</div>
                        <a href="../Manage-Warehouse/warehouse-status.php" class="text-sm text-blue-600">Manage</a>
                    </div>
                    <div class="mt-2 space-y-1 text-sm">
                        <?php foreach ($low_space_alerts as $s): ?>
                            <div class="flex justify-between">
                                <div><?= htmlspecialchars($s['name']) ?></div>
                                <div class="font-semibold text-red-600"><?= number_format($s['free_space']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </section>

        <!-- ================= Segment 2: Stock & Orders ================= -->
        <section class="mb-8">


            <h2 class="text-xl font-semibold mb-4">Stocks Status</h2>
            <!-- Stock request summary badges -->
            <div class="flex gap-3 mb-4 flex-wrap">
                <?php foreach ($stock_requests_counts as $k => $v): ?>
                    <div class="bg-white p-3 rounded shadow-sm min-w-[140px]">
                        <div class="text-sm text-gray-500"><?= $k ?></div>
                        <div class="text-xl font-bold"><?= $v ?></div>
                    </div>
                <?php endforeach; ?>
            </div>




            <h2 class="text-xl font-semibold mb-4">Orders Status</h2>
            <!-- =========== Two columns: Total Orders & Recent Orders =========== -->

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Total Orders summary -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <div class="text-sm text-gray-500">Total Orders</div>
                            <div class="text-2xl font-bold"><?= array_sum($order_counts) ?></div>
                        </div>
                        <div class="space-y-1 text-sm text-gray-600 text-right">
                            <div>Pending: <span
                                    class="font-semibold text-yellow-600"><?= $order_counts['Pending'] ?></span></div>
                            <div>Completed: <span
                                    class="font-semibold text-green-600"><?= $order_counts['Completed'] ?></span></div>
                            <div>Rejected: <span
                                    class="font-semibold text-red-600"><?= $order_counts['Rejected'] ?></span></div>
                        </div>
                    </div>
                    <div>
                        <canvas id="ordersDonut" height="120"></canvas>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-500">Recent Orders</div>
                        <a href="../orders/orders.php" class="text-sm text-blue-600">View all</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-gray-500">
                                <tr>
                                    <th class="py-2">Order</th>
                                    <th class="py-2">Shop</th>
                                    <th class="py-2">Date</th>
                                    <th class="py-2">Total</th>
                                    <th class="py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $ro): ?>
                                    <tr class="border-t">
                                        <td class="py-2 font-medium"><?= $ro['order_id'] ?></td>
                                        <td class="py-2"><?= htmlspecialchars($ro['shop_name']) ?></td>
                                        <td class="py-2"><?= date('d M, Y', strtotime($ro['placed_at'])) ?></td>
                                        <td class="py-2"><?= number_format($ro['total'], 2) ?></td>
                                        <td class="py-2">
                                            <span
                                                class="<?= $ro['status'] == 'Pending' ? 'text-yellow-600' : ($ro['status'] == 'Completed' ? 'text-green-600' : 'text-gray-600') ?> font-semibold"><?= $ro['status'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </section>


        <!-- ================= Segment 3: Inventory & Warehouse ================= -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Inventory & Warehouse</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Warehouse capacity chart -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-500">Warehouse Capacity (used vs free)</div>
                        <a href="../Manage-Warehouse/warehouse-status.php" class="text-sm text-blue-600">Manage warehouses</a>
                    </div>
                    <canvas id="warehouseBar" height="160"></canvas>
                    <div class="mt-3 text-sm text-gray-600">
                        <?php foreach ($warehouses_stats as $w):
                            $free = $w['capacity_total'] - $w['capacity_used'];
                        ?>
                            <div class="flex justify-between">
                                <div><?= htmlspecialchars($w['name']) ?></div>
                                <div>Used: <?= number_format($w['capacity_used']) ?> / Free: <?= number_format($free) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stock in/out history -->
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-500">Stock In/Out History</div>
                        <button onclick="exportStockHistory()" class="text-sm text-blue-600 cursor-pointer hover:underline">Export CSV</button>

                        <script>
                            function exportStockHistory() {
                                // Get table data
                                const stockHistory = <?php echo json_encode($stock_history); ?>;

                                // Create CSV content
                                let csv = 'Date,Type,Product,Quantity\n';

                                stockHistory.forEach(row => {
                                    csv += `${row.date},${row.type},${row.product},${row.qty}\n`;
                                });

                                // Create blob and download
                                const blob = new Blob([csv], {
                                    type: 'text/csv'
                                });
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'stock-history-' + new Date().toISOString().split('T')[0] + '.csv';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                window.URL.revokeObjectURL(url);
                            }
                        </script>
                    </div>

                    <div class="overflow-x-auto text-sm">
                        <table class="w-full">
                            <thead class="text-left text-gray-500">
                                <tr>
                                    <th class="py-2">Date</th>
                                    <th class="py-2">Type</th>
                                    <th class="py-2">Product</th>
                                    <th class="py-2">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock_history as $h): ?>
                                    <tr class="border-t">
                                        <td class="py-2"><?= htmlspecialchars($h['date']) ?></td>
                                        <td class="py-2"><?= htmlspecialchars($h['type']) ?></td>
                                        <td class="py-2"><?= htmlspecialchars($h['product']) ?></td>
                                        <td class="py-2"><?= htmlspecialchars($h['qty']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </section>

        <!-- ================= Segment 4: Reports ================= -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Reports & Analytics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($revenue_last_3_months as $idx => $m): ?>
                    <div class="bg-white p-4 rounded shadow-sm">
                        <div class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($m['label']) ?></div>
                        <div class="text-2xl font-bold"><?= number_format($m['amount'], 2) ?></div>
                        <div class="mt-2 text-sm text-gray-600">Monthly revenue</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 bg-white p-4 rounded shadow-sm">
                <div class="text-sm text-gray-500 mb-2">Revenue Trend (last 3 months)</div>
                <canvas id="revenueChart" height="120"></canvas>
            </div>
        </section>

        <!-- ================= Segment 5: System Management ================= -->
        <section class="mb-12">
            <h2 class="text-xl font-semibold mb-4">System Management</h2>
            <div class="bg-white p-4 rounded shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm text-gray-500">Activity Logs</div>
                    <!-- <a href="#" class="text-sm text-blue-600">View full log</a> -->
                </div>

                <ul class="space-y-2 text-sm">
                    <?php foreach ($activity_logs as $act): ?>
                        <li class="flex items-start justify-between border-t pt-2">
                            <div>
                                <div class="font-medium"><?= htmlspecialchars($act['user']) ?></div>
                                <div class="text-gray-600"><?= htmlspecialchars($act['action']) ?></div>
                            </div>
                            <div class="text-xs text-gray-400"><?= htmlspecialchars($act['time']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

    </div>

    <!-- Charts initialization -->
    <script>
        // ====== Orders donut
        const orderCounts = <?php echo json_encode(array_values($order_counts)); ?>;
        const ctxOrders = document.getElementById('ordersDonut').getContext('2d');
        new Chart(ctxOrders, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($order_counts)); ?>,
                datasets: [{
                    data: orderCounts,
                    backgroundColor: ['#F59E0B', '#10B981', '#EF4444']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // ====== Warehouse bar (used vs free) - stacked
        const warehouses = <?php
                            $labels = array_column($warehouses_stats, 'name');
                            $used = array_column($warehouses_stats, 'capacity_used');
                            $total = array_column($warehouses_stats, 'capacity_total');
                            $free = array_map(function ($t, $u) {
                                return $t - $u;
                            }, $total, $used);
                            echo json_encode([
                                'labels' => $labels,
                                'used' => $used,
                                'free' => $free
                            ]);
                            ?>;

        const ctxWh = document.getElementById('warehouseBar').getContext('2d');
        new Chart(ctxWh, {
            type: 'bar',
            data: {
                labels: warehouses.labels,
                datasets: [{
                        label: 'Used',
                        data: warehouses.used,
                        backgroundColor: '#2563EB'
                    },
                    {
                        label: 'Free',
                        data: warehouses.free,
                        backgroundColor: '#10B981'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // ====== Revenue chart
        const revenueData = <?php echo json_encode(array_column($revenue_last_3_months, 'amount')); ?>;
        const revenueLabels = <?php echo json_encode(array_column($revenue_last_3_months, 'label')); ?>;
        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRev, {
            type: 'bar',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Revenue',
                    data: revenueData,
                    backgroundColor: '#2563EB'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>

</html>