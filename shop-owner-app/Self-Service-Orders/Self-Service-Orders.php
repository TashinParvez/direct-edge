<?php
ob_start(); // Start output buffering to handle session_start() in sidebar
?>
<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarShop.php'; ?>

<?php

include '../../include/connect-db.php';

// $shop_id = isset($user_id) ? $user_id : 6;

$shop_id =   6;

// Query to extract products from JSON and join with products table
$sql = "SELECT 
            combined.order_id,
            combined.order_code,
            combined.user_name,
            combined.total_amount,
            combined.status,
            combined.product_id,
            p.name AS product_name,
            p.img_url,
            combined.quantity AS quantity,
            combined.price AS unit_price,
            combined.shop_id
        FROM (
            SELECT
                o.order_id,
                o.order_code,
                o.user_name,
                o.total_amount,
                o.status,
                JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[0].product_id'))) AS product_id,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[0].quantity'))) AS UNSIGNED) AS quantity,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[0].price'))) AS DECIMAL(10,2)) AS price,
                o.shop_id
            FROM self_service_orders o
            WHERE o.shop_id = ?

            UNION ALL

            SELECT
                o.order_id,
                o.order_code,
                o.user_name,
                o.total_amount,
                o.status,
                JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[1].product_id'))) AS product_id,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[1].quantity'))) AS UNSIGNED) AS quantity,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[1].price'))) AS DECIMAL(10,2)) AS price,
                o.shop_id
            FROM self_service_orders o
            WHERE o.shop_id = ?

            UNION ALL

            SELECT
                o.order_id,
                o.order_code,
                o.user_name,
                o.total_amount,
                o.status,
                JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[2].product_id'))) AS product_id,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[2].quantity'))) AS UNSIGNED) AS quantity,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(o.products, CONCAT('$[2].price'))) AS DECIMAL(10,2)) AS price,
                o.shop_id
            FROM self_service_orders o
            WHERE o.shop_id = ?
        ) AS combined
        LEFT JOIN products p ON p.product_id = combined.product_id       
        WHERE combined.product_id IS NOT NULL
        ORDER BY combined.order_id, combined.product_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $shop_id, $shop_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . $conn->error);
}

$orders = [];

while ($row = $result->fetch_assoc()) {
    $orderId = $row['order_id'];

    if (!isset($orders[$orderId])) {
        // Normalize empty status to 'In queue'
        $status = (!empty($row['status']) && $row['status'] != '') ? $row['status'] : 'In queue';

        $orders[$orderId] = [
            'id'         => (int)$orderId,
            'order_code' => $row['order_code'],
            'buyer'      => $row['user_name'],
            'amount'     => 0.0, // Will calculate from products
            'status'     => $status,
            'shop_id'    => (int)$row['shop_id'],
            'products'   => []
        ];
    }

    $qty = (int)$row['quantity'];
    $unitPrice = (float)$row['unit_price'];

    $orders[$orderId]['products'][] = [
        'product_id' => (int)$row['product_id'],
        'name'       => $row['product_name'],
        'img_url'    => $row['img_url'],
        'qty'        => $qty,
        'unit_price' => $unitPrice
    ];

    // Calculate correct total amount
    $orders[$orderId]['amount'] += ($qty * $unitPrice);
}

// Reset array keys to 0, 1, 2...
$orders = array_values($orders);

// Filter orders by status
$notHandled = array_filter($orders, fn($o) => $o['status'] == 'In queue' || $o['status'] == '' || $o['status'] == null);
$running = array_filter($orders, fn($o) => $o['status'] == 'Running');
$done = array_filter($orders, fn($o) => $o['status'] == 'Done');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self Service Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn-green {
            background-color: #16a34a;
            color: white;
        }

        .btn-green:hover {
            background-color: #13803d;
        }

        .bg-theme {
            background-color: #f9fafb;
        }

        .text-black-custom {
            color: #111827;
        }

        .tick-mark {
            color: #22c55e;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Make columns equal height */
        .grid {
            align-items: start;
        }
    </style>
</head>

<body class="bg-theme p-6">

    <div class="bg-white rounded-lg shadow p-4 mb-8 mx-4 mt-4">
        <h1 class="text-3xl font-bold text-black-custom text-center">Self Service Orders</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 px-4">
        <!-- In queue -->
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-xl font-semibold mb-4 text-black-custom border-b pb-2">In queue</h2>
            <div class="space-y-4">
                <?php foreach ($notHandled as $order): ?>
                    <div class="bg-gray-50 shadow rounded-lg p-4 cursor-pointer hover:ring hover:ring-green-300 transition" onclick="openModal(<?= $order['id'] ?>)">
                        <p class="font-bold text-black-custom">Order Code #<?= $order['order_code'] ?></p>
                        <p class="text-gray-700">Buyer: <?= $order['buyer'] ?></p>
                        <p class="font-semibold text-black-custom">Total: $<?= $order['amount'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Running -->
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-xl font-semibold mb-4 text-black-custom border-b pb-2">Running</h2>
            <div class="space-y-4">
                <?php foreach ($running as $order): ?>
                    <div class="bg-gray-50 shadow rounded-lg p-4 cursor-pointer hover:ring hover:ring-green-300 transition" onclick="openModal(<?= $order['id'] ?>)">
                        <p class="font-bold text-black-custom">Order Code #<?= $order['order_code'] ?></p>
                        <p class="text-gray-700">Buyer: <?= $order['buyer'] ?></p>
                        <p class="font-semibold text-black-custom">Total: $<?= $order['amount'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Done -->
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-xl font-semibold mb-4 text-black-custom border-b pb-2">Done / Finished</h2>
            <div class="space-y-4">
                <?php foreach ($done as $order): ?>
                    <div class="bg-gray-50 shadow rounded-lg p-4 cursor-pointer hover:ring hover:ring-green-300 transition" onclick="openModal(<?= $order['id'] ?>)">
                        <p class="font-bold text-black-custom">Order Code #<?= $order['order_code'] ?> <span class="tick-mark">✔</span></p>
                        <p class="text-gray-700">Buyer: <?= $order['buyer'] ?></p>
                        <p class="font-semibold text-black-custom">Total: $<?= $order['amount'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-11/12 md:w-2/3 lg:w-1/2 p-6 relative shadow-lg">
            <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-2xl font-bold">&times;</button>
            <div id="modalContent"></div>
        </div>
    </div>



    <script>
        (function() {
            'use strict';

            const orders = <?= json_encode($orders) ?>;

            const modal = document.getElementById('orderModal');
            const modalContent = document.getElementById('modalContent');
            const closeBtn = document.getElementById('closeModalBtn');

            window.openModal = function(orderId) {
                const order = orders.find(o => o.id == orderId);
                if (!order) return;

                const productsHtml = order.products.map(p => `
        <tr class="border-b">
            <td class="p-2 text-black-custom">${p.name}</td>
            <td class="p-2 text-black-custom">${p.qty}</td>
            <td class="p-2 text-black-custom">$${p.unit_price}</td>
            <td class="p-2 text-black-custom">$${p.qty*p.unit_price}</td>
        </tr>
    `).join('');

                modalContent.innerHTML = `
        <h3 class="text-2xl font-bold text-black-custom mb-4">Order Code #${order.order_code}</h3>
        <p class="mb-2 text-black-custom">Buyer: ${order.buyer}</p>
        <p class="mb-4 font-semibold text-black-custom">Total: $${order.amount}</p>

        <table class="w-full border mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left">Product</th>
                    <th class="p-2 text-left">Qty</th>
                    <th class="p-2 text-left">Unit Price</th>
                    <th class="p-2 text-left">Total</th>
                </tr>
            </thead>
            <tbody>${productsHtml}</tbody>
        </table>

        <div class="flex gap-2 mt-4 flex-wrap">
            <button id="btnRunning" class="btn-green px-4 py-2 rounded" onclick="updateStatus(${order.id}, 'Running')">Mark Running</button>
            <button id="btnDone" class="btn-green px-4 py-2 rounded" onclick="updateStatus(${order.id}, 'Done')">Mark Done</button>
            <button id="btnCancel" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600" onclick="updateStatus(${order.id}, 'Cancelled')">Cancel</button>
            <button id="btnPrint" class="px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600" onclick="printOrder(${order.id})">Print</button>
        </div>
    `;

                // Disable buttons based on order status
                if (order.status === 'Running') {
                    document.getElementById('btnRunning').disabled = true;
                    document.getElementById('btnDone').disabled = false;
                    document.getElementById('btnCancel').disabled = false;
                } else if (order.status === 'Done') {
                    document.getElementById('btnRunning').disabled = true;
                    document.getElementById('btnDone').disabled = true;
                    document.getElementById('btnCancel').disabled = true;
                } else { // In queue
                    document.getElementById('btnRunning').disabled = false;
                    document.getElementById('btnDone').disabled = false;
                    document.getElementById('btnCancel').disabled = false;
                }

                modal.classList.remove('hidden'); // remove hidden
                modal.classList.add('flex', 'show'); // add flex + fade-in

            }


            window.printOrder = function(orderId) {
                const order = orders.find(o => o.id == orderId);
                if (!order) return;

                const productsHtml = order.products.map(p => `
        <tr>
            <td>${p.name}</td>
            <td>${p.qty}</td>
            <td>$${p.unit_price}</td>
            <td>$${p.qty * p.unit_price}</td>
        </tr>
    `).join('');

                const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${order.order_code}.pdf</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h2 { margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h2>Order Code #${order.order_code}</h2>
            <p><strong>Buyer:</strong> ${order.buyer}</p>
            <p><strong>Total:</strong> $${order.amount}</p>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${productsHtml}
                </tbody>
            </table>
        </body>
        </html>
    `;

                const newWin = window.open('', '', 'width=800,height=600');
                newWin.document.write(printContent);
                newWin.document.close();
                newWin.document.title = `${order.order_code}.pdf`;
                newWin.print();
            }


            function closeModal() {
                modal.classList.remove('show', 'flex');
                modal.classList.add('hidden');
            }

            // Close modal on button click
            closeBtn.addEventListener('click', closeModal);

            // Close modal on outside click
            window.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });



            window.updateStatus = function(orderId, status) {
                fetch('update_order_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            orderId,
                            status
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Order #${orderId} marked as ${status}`);

                            // Update the order status in JS to reflect changes in modal
                            const order = orders.find(o => o.id == orderId);
                            if (order) order.status = status;

                            closeModal();

                            // Optionally, refresh the page to update the columns
                            location.reload();
                        } else {
                            alert('Failed to update order: ' + data.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Something went wrong');
                    });
            };
        })();
    </script>
</body>

</html>
<?php
ob_end_flush(); // End output buffering
?>