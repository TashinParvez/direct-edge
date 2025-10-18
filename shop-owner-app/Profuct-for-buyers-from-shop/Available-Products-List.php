<?php
session_start();
include '../../include/connect-db.php';

// Assume buyer is logged in and shop_id is provided via GET
if (!isset($_SESSION['user_id']) || !isset($_GET['shop_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$shop_id = (int)$_GET['shop_id'];
$user_id = 24;
$shop_id = 24;

// Fetch available products in the shop
$sql = "SELECT p.product_id, p.name AS product_name, p.category, p.price, p.unit, p.img_url, sp.quantity
        FROM shop_products sp
        JOIN products p ON sp.product_id = p.product_id
        WHERE sp.shop_id = ? AND sp.quantity > 0
        ORDER BY p.name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$available_products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Buy Products from Shop</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
    .product-card {
        min-height: 340px;
    }

    .product-img {
        max-width: 100%;
        max-height: 120px;
        object-fit: contain;
    }

    .search-bar {
        max-width: 400px;
    }
    </style>
</head>

<body>
    <div class="container py-4">
        <h2 class="mb-4">Available Products in Shop</h2>
        <input type="text" id="searchInput" class="form-control mb-3 search-bar" placeholder="Search products...">
        <div class="row" id="productList">
            <?php foreach ($available_products as $product): ?>
            <div class="col-md-4 mb-4 product-item">
                <div class="card product-card">
                    <img src="<?= htmlspecialchars($product['img_url']) ?>" class="card-img-top product-img"
                        alt="<?= htmlspecialchars($product['product_name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                        <p class="card-text">
                            Category: <?= htmlspecialchars($product['category']) ?><br>
                            Price: ৳<?= htmlspecialchars($product['price']) ?> /
                            <?= htmlspecialchars($product['unit']) ?><br>
                            Available: <?= (int)$product['quantity'] ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($available_products)): ?>
            <div class="col-12">
                <div class="alert alert-warning">No products available in this shop.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    // Real-time search filtering
    document.getElementById('searchInput').addEventListener('input', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(function(item) {
            let text = item.innerText.toLowerCase();
            item.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    </script>
</body>

</html>