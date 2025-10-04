<?php
function renderOrderConfirmation($code, $selected_products, $product_map)
{
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Confirmation</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
            <h1 class="text-2xl font-bold mb-4">Order Confirmed!</h1>
            <p class="text-lg mb-4">Your Code: <span class="font-bold text-4xl"><?php echo htmlspecialchars($code); ?></span></p>
            <p>Listen for your code to be called.</p>
            <h2 class="font-bold mt-4">Your Items:</h2>
            <ul>
                <?php foreach ($selected_products as $item): ?>
                    <li><?php echo htmlspecialchars($product_map[$item['id']]); ?> x <?php echo $item['quantity']; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </body>

    </html>
<?php
}
