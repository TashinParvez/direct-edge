<?php
include '../include/connect-db.php'; // Database connection

$sql = "SELECT 
            p.product_id,
            p.name AS product_name,
            p.category,
            p.price,
            p.unit,
            wp.quantity,
            wp.unit_volume,
            wp.expiry_date,
            wp.image_url,
            w.warehouse_id,
            w.name AS warehouse_name,
            w.location AS warehouse_city
        FROM warehouse_products wp
        JOIN products p ON wp.product_id = p.product_id
        JOIN warehouses w ON wp.warehouse_id = w.warehouse_id
        WHERE wp.quantity > 0
        ORDER BY p.name, w.name;";

$result = mysqli_query($conn, $sql);

// Fetch all rows as numeric arrays
$available_products = mysqli_fetch_all($result);

// print_r($available_products);




// =================================================================

// print_r($available_products[0]);

// =============


// p.product_id,
// p.name AS product_name,
// p.category, 2
// p.price,
// p.unit, 4
// wp.quantity,
// wp.unit_volume,
// wp.expiry_date,
// wp.image_url, 8
// w.warehouse_id,
// w.name AS warehouse_name, 10 
// w.location AS warehouse_city   11     
// =================================================================



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex">

    <!-- Sidebar -->
    <?php
    // include '../include/Sidebar.php'; 
    ?>

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-64">

        <?php
        // print_r($available_products[0]);
        ?>

        <h2 class="text-2xl font-bold mb-6 text-center">Buy Products</h2>
        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" placeholder="Search products..." class="w-full p-3 border border-gray-300 rounded shadow-sm">
        </div>

        <!--============== Filters ==============-->
        <div class="flex flex-wrap gap-4 mb-6">
            <select class="p-2 border border-gray-300 rounded shadow-sm">
                <option value="">All Categories</option>
                <option value="Vegetable">Vegetable</option>
                <option value="Fruit">Fruit</option>
                <option value="Grain">Grain</option>
                <option value="Dairy">Dairy</option>
            </select>

            <select class="p-2 border border-gray-300 rounded shadow-sm">
                <option value="">All Warehouses</option>
                <option value="Dhaka Warehouse">Dhaka Warehouse</option>
                <option value="Chittagong Warehouse">Chittagong Warehouse</option>
            </select>

            <select class="p-2 border border-gray-300 rounded shadow-sm">
                <option value="">Availability</option>
                <option value="in_stock">In Stock</option>
                <option value="out_of_stock">Out of Stock</option>
            </select>
        </div>

        <!--============================= Products Grid =============================-->

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php foreach ($available_products as $product): ?>
                <div class="bg-white rounded shadow-md overflow-hidden flex flex-col justify-between">
                    <img src="<?= $product[8] ?>" alt="<?= $product[8] ?>" class="w-full h-48 object-cover">


                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-lg mb-1"><?= $product[1] ?></h3>
                            <p class="text-gray-600 mb-1">Category: <?= $product[2] ?></p>
                            <p class="text-gray-800 font-semibold mb-1">Price: <?= $product[3] ?> BDT/unit</p>
                            <p class="text-gray-600 mb-1">Available: <?= $product[4] ?></p>
                            <p class="text-gray-500 text-sm">Warehouse: <?= $product[10] ?></p>
                        </div>
                        <button class="mt-3 bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 w-full">Add to Cart</button>
                    </div>


                </div>
            <?php endforeach; ?>

        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center">
            <nav class="inline-flex -space-x-px">
                <a href="#" class="px-3 py-2 bg-white border border-gray-300 rounded-l hover:bg-gray-100">Prev</a>
                <a href="#" class="px-3 py-2 bg-white border-t border-b border-gray-300 hover:bg-gray-100">1</a>
                <a href="#" class="px-3 py-2 bg-white border-t border-b border-gray-300 hover:bg-gray-100">2</a>
                <a href="#" class="px-3 py-2 bg-white border-t border-b border-gray-300 hover:bg-gray-100">3</a>
                <a href="#" class="px-3 py-2 bg-white border border-gray-300 rounded-r hover:bg-gray-100">Next</a>
            </nav>
        </div>
    </div>
</body>

</html>