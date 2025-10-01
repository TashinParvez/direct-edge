<?php
function renderProducts($user_name, $products)
{
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Select Products</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-100 p-4">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded shadow-md">
            <h1 class="text-2xl font-bold mb-4">Select Your Products, <?php echo htmlspecialchars($user_name); ?></h1>
            <form action="/submit-order" method="POST">
                <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($user_name); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($products as $product): ?>
                        <div class="border p-4 rounded">
                            <h2 class="font-bold"><?php echo htmlspecialchars($product['name']); ?> - $<?php echo $product['price']; ?></h2>
                            <input type="number" name="products[<?php echo $product['id']; ?>]" min="0" value="0" class="w-20 p-1 border rounded">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="bg-green-500 text-white p-2 rounded mt-4 w-full">Submit Order</button>
            </form>
        </div>
    </body>

    </html>
<?php
}
