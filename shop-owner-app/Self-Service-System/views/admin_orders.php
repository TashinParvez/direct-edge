<?php
function renderAdminOrders($orders)
{
    // Decode products JSON
    foreach ($orders as &$order) {
        $order['products'] = json_decode($order['products'], true);
    }

    // Fetch product names
    global $db;
    $productModel = new Product($db);
    $all_products = $productModel->getAll();
    $product_map = [];
    foreach ($all_products as $p) {
        $product_map[$p['id']] = $p['name'];
    }
    
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shopkeeper Orders</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-100 p-4">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded shadow-md">
            <h1 class="text-2xl font-bold mb-4">Pending Orders</h1>
            <?php if (empty($orders)): ?>
                <p>No pending orders.</p>
            <?php else: ?>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">Code</th>
                            <th class="p-2">User</th>
                            <th class="p-2">Items</th>
                            <th class="p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr class="border-b">
                                <td class="p-2"><?php echo htmlspecialchars($order['code']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($order['user_name']); ?></td>
                                <td class="p-2">
                                    <ul>
                                        <?php foreach ($order['products'] as $item): ?>
                                            <li><?php echo htmlspecialchars($product_map[$item['id']]); ?> x <?php echo $item['quantity']; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td class="p-2">
                                    <form method="POST">
                                        <input type="hidden" name="deliver" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="bg-red-500 text-white p-1 rounded">Mark Delivered</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </body>

    </html>
<?php
}
