<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "direct-edge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Current date and month for seasonality
$current_date = '2025-09-24';  // Use date('Y-m-d') in production
$current_month = (int)date('m', strtotime($current_date));

// Fetch products with stock and lead time for warehouseid 15
$sql = "SELECT p.*, wp.quantity AS total_stock, wp.lead_time_days, wp.stock_change_timestamp, 
               wp.historical_stock_level, wp.restock_frequency, wp.demand_velocity, 
               wp.image_url AS wp_image_url, wp.forecasted_demand
        FROM products p
        JOIN warehouse_products wp ON p.product_id = wp.product_id
        WHERE wp.warehouse_id = 15
        ORDER BY p.name";
$result = $conn->query($sql);

$products = [];
while ($row = $result->fetch_assoc()) {
    $product_id = $row['product_id'];

    // Fetch sales history for the last 30 days
    $start_date = date('Y-m-d', strtotime($current_date . ' -30 days'));
    $sql2 = "SELECT SUM(quantity_sold) AS total_sold FROM sales_history WHERE product_id = ? AND sale_date >= ? AND sale_date <= ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("iss", $product_id, $start_date, $current_date);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $total_sold = (float)($res2->fetch_assoc()['total_sold'] ?? 0);
    $stmt2->close();

    // Fetch seasonality factor for the current month
    $sql3 = "SELECT factor FROM seasonality_factors WHERE product_id = ? AND month = ?";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("ii", $product_id, $current_month);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    $season_factor = $res3->fetch_assoc()['factor'] ?? 1.00;
    $stmt3->close();

    // Demand forecasting: Simple exponential smoothing (alpha = 0.3 for smoothing)
    $daily_demand_sales = $total_sold > 0 ? $total_sold / 30 : 0;
    $daily_demand_velocity = (float)($row['demand_velocity'] ?? 0);
    $daily_demand = max($daily_demand_sales, $daily_demand_velocity);

    // Exponential smoothing: Forecast = alpha * actual + (1 - alpha) * previous_forecast
    $alpha = 0.3;  // Adjust as needed
    $previous_forecast = (float)($row['forecasted_demand'] ?? $daily_demand * 30);  // Use existing or initial
    $predicted_demand = round($alpha * ($daily_demand * 30) + (1 - $alpha) * $previous_forecast * $season_factor);

    // Update DB with new forecast
    $sql_update = "UPDATE warehouse_products SET forecasted_demand = ?, last_forecast_date = ? 
                   WHERE warehouse_id = 15 AND product_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dsi", $predicted_demand, $current_date, $product_id);
    $stmt_update->execute();
    $stmt_update->close();

    $row['predicted_demand'] = $predicted_demand;
    $row['total_sold'] = $total_sold;
    $products[] = $row;
}
$conn->close();

// Separate into in-stock (stock > 0) and restock (predicted_demand > total_stock)
$in_stock = [];
$restock = [];
foreach ($products as $product) {
    if ($product['total_stock'] > 0) {
        $in_stock[] = $product;
    }
    if ($product['predicted_demand'] > $product['total_stock']) {
        $restock[] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Management</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product h3, .product p { color: #11101D; }
        .bg-custom { background-color: #f9fafb; }
    </style>
</head>
<body class="bg-custom">
    <?php include "../Include/Sidebar.php"; ?>
    <section class="home-section p-0">
        <div class="text-2xl font-bold text-gray-800 text-center my-4">Your Stock in House (Warehouse 15)</div>
        <div class="container mx-3">
            <div class="flex flex-wrap justify-start">
                <?php foreach ($in_stock as $product): ?>
                <div class="product p-4 bg-white rounded-lg shadow-md m-2 flex-shrink-0 w-48">
                    <img src="<?php echo htmlspecialchars($product['wp_image_url'] ?? '../assets/products-image/default.jpg'); ?>" class="w-48 h-48 object-cover rounded mb-2" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3 class="text-lg font-semibold mb-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-gray-600 mb-1">Remaining: <?php echo $product['total_stock']; ?> <?php echo htmlspecialchars($product['unit'] ?? ''); ?></p>
                    <p class="text-gray-600 mb-1">Predicted Demand (30 days): <?php echo $product['predicted_demand']; ?></p>
                    <p class="text-gray-600 text-sm">History Sold: <?php echo $product['total_sold']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="text-2xl font-bold text-gray-800 text-center my-4">Restock Products (Warehouse 15)</div>
        <div class="container mx-3">
            <div class="flex flex-wrap justify-start">
                <?php foreach ($restock as $product): ?>
                <div class="product p-4 bg-white rounded-lg shadow-md m-2 flex-shrink-0 w-48">
                    <img src="<?php echo htmlspecialchars($product['wp_image_url'] ?? '../assets/products-image/default.jpg'); ?>" class="w-48 h-48 object-cover rounded mb-2" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3 class="text-lg font-semibold mb-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-gray-600 mb-1">To Restock: <?php echo $product['predicted_demand'] - $product['total_stock']; ?> <?php echo htmlspecialchars($product['unit'] ?? ''); ?></p>
                    <p class="text-gray-600 mb-1">Delivery Time: <?php echo $product['lead_time_days']; ?> (rounded <?php echo round($product['lead_time_days'] / 5); ?> days)</p>
                    <p class="text-gray-600 mb-1">Predicted Demand (30 days): <?php echo $product['predicted_demand']; ?></p>
                    <p class="text-gray-600 text-sm">History Sold: <?php echo $product['total_sold']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</body>
</html>
