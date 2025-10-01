<?php
// self_service_orders.php


include '../../include/connect-db.php';

$orders = [
    ['id' => 101, 'buyer' => 'Alice', 'amount' => 500, 'status' => 'Not Handled', 'products' => [['name' => 'Rice', 'qty' => 5, 'unit_price' => 50]]],
    ['id' => 102, 'buyer' => 'Bob', 'amount' => 1200, 'status' => 'Running', 'products' => [['name' => 'Wheat', 'qty' => 10, 'unit_price' => 120]]],
    ['id' => 103, 'buyer' => 'Charlie', 'amount' => 800, 'status' => 'Done', 'products' => [['name' => 'Corn', 'qty' => 8, 'unit_price' => 100]]],
    ['id' => 104, 'buyer' => 'David', 'amount' => 450, 'status' => 'Not Handled', 'products' => [['name' => 'Sugar', 'qty' => 3, 'unit_price' => 150]]],
    ['id' => 105, 'buyer' => 'Eva', 'amount' => 950, 'status' => 'Running', 'products' => [['name' => 'Oil', 'qty' => 5, 'unit_price' => 190]]],
];

$notHandled = array_filter($orders, fn($o) => $o['status'] == 'Not Handled');
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
    </style>
</head>

<body class="bg-theme p-6">

    <h1 class="text-3xl font-bold text-black-custom mb-8 text-center">Self Service Orders</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Not Handled -->
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-xl font-semibold mb-4 text-black-custom border-b pb-2">Not Handled</h2>
            <div class="space-y-4">
                <?php foreach ($notHandled as $order): ?>
                    <div class="bg-gray-50 shadow rounded-lg p-4 cursor-pointer hover:ring hover:ring-green-300 transition" onclick="openModal(<?= $order['id'] ?>)">
                        <p class="font-bold text-black-custom">Order #<?= $order['id'] ?></p>
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
                        <p class="font-bold text-black-custom">Order #<?= $order['id'] ?></p>
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
                        <p class="font-bold text-black-custom">Order #<?= $order['id'] ?> <span class="tick-mark">✔</span></p>
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
        const orders = <?= json_encode($orders) ?>;

        const modal = document.getElementById('orderModal');
        const modalContent = document.getElementById('modalContent');
        const closeBtn = document.getElementById('closeModalBtn');

        function openModal(orderId) {
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
        <h3 class="text-2xl font-bold text-black-custom mb-4">Order #${order.id}</h3>
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
            } else { // Not Handled
                document.getElementById('btnRunning').disabled = false;
                document.getElementById('btnDone').disabled = false;
                document.getElementById('btnCancel').disabled = false;
            }

            modal.classList.remove('hidden'); // remove hidden
            modal.classList.add('flex', 'show'); // add flex + fade-in

        }


        function printOrder(orderId) {
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
        <h2>Order #${order.id}</h2>
        <p>Buyer: ${order.buyer}</p>
        <p>Total: $${order.amount}</p>
        <table border="1" cellspacing="0" cellpadding="5">
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
            ${productsHtml}
        </table>
    `;

            const newWin = window.open('', '', 'width=800,height=600');
            newWin.document.write(printContent);
            newWin.document.close();
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

        // Dummy update function
        function updateStatus(orderId, status) {
            alert(`Order #${orderId} marked as ${status}`);
            closeModal();
        }
    </script>
</body>

</html>