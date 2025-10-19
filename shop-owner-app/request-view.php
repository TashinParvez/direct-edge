<?php
ob_start();
session_start();
include '../include/connect-db.php';

$user_id = $_SESSION['user_id'] ?? 2;

// Fetch all stock requests for this user
$sql = "SELECT 
            sr.request_id,
            sr.requester_id,
            sr.product_id,
            sr.quantity as requested_quantity,
            sr.note,
            sr.status,
            sr.requested_at,
            sr.updated_at,
            p.name as product_name,
            p.category,
            p.price,
            p.unit,
            p.img_url,
            COALESCE(SUM(wp.quantity), 0) as available_quantity
        FROM stock_requests sr
        JOIN products p ON sr.product_id = p.product_id
        LEFT JOIN warehouse_products wp ON p.product_id = wp.product_id
        WHERE sr.requester_id = ?
        GROUP BY sr.request_id, sr.requester_id, sr.product_id, sr.quantity, sr.note, sr.status, 
                 sr.requested_at, sr.updated_at, p.name, p.category, p.price, p.unit, p.img_url
        ORDER BY sr.requested_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stock Requests</title>
    <link rel="icon" type="image/x-icon" href="../assets/Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .request-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-done {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-working {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Main content area adjustment for sidebar */
        .main-content {
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include '../Include/SidebarShop.php'; ?>

    <div class="main-content flex-1 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">My Stock Requests</h1>
                <button onclick="window.location.href='buy-products-from-warehouse.php'"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class='bx bx-arrow-back'></i>
                    Back to Products
                </button>
            </div>
            <p class="text-gray-600">Track all your stock requests and their current status</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <?php
            $total_requests = count($requests);
            $pending = count(array_filter($requests, fn($r) => $r['status'] === 'Pending'));
            $done = count(array_filter($requests, fn($r) => $r['status'] === 'Done'));
            $rejected = count(array_filter($requests, fn($r) => $r['status'] === 'Rejected'));
            ?>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_requests; ?></p>
                    </div>
                    <i class='bx bx-list-ul text-4xl text-blue-500'></i>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $pending; ?></p>
                    </div>
                    <i class='bx bx-time-five text-4xl text-yellow-500'></i>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Done</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $done; ?></p>
                    </div>
                    <i class='bx bx-check-circle text-4xl text-green-500'></i>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Rejected</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $rejected; ?></p>
                    </div>
                    <i class='bx bx-x-circle text-4xl text-red-500'></i>
                </div>
            </div>
        </div>

        <!-- Requests List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <?php if (empty($requests)): ?>
                <div class="p-12 text-center">
                    <i class='bx bx-package text-6xl text-gray-300 mb-4'></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Stock Requests Yet</h3>
                    <p class="text-gray-600 mb-6">You haven't made any stock requests. Browse products and request stock when needed.</p>
                    <button onclick="window.location.href='buy-products-from-warehouse.php'"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Browse Products
                    </button>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Request ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Requested On</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Available Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm text-gray-900">#<?php echo str_pad($request['request_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img src="../<?php echo htmlspecialchars($request['img_url']); ?>"
                                                alt="<?php echo htmlspecialchars($request['product_name']); ?>"
                                                class="w-12 h-12 object-cover rounded-lg">
                                            <div>
                                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($request['product_name']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($request['category']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-gray-900 font-medium"><?php echo $request['requested_quantity']; ?> <?php echo htmlspecialchars($request['unit']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($request['requested_at'])); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($request['available_quantity'] > 0): ?>
                                            <span class="text-green-600 font-medium"><?php echo $request['available_quantity']; ?> <?php echo htmlspecialchars($request['unit']); ?> available</span>
                                        <?php else: ?>
                                            <span class="text-red-600 font-medium">Out of stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds to get updated status
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>

</html>