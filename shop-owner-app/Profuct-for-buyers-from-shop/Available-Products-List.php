<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarShop.php'; ?>

<?php
// session_start();
include '../../include/connect-db.php';

// // Check if user is logged in
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Shop-Owner') {
//     header("Location: ../../Login-Signup/login.php");
//     exit();
// }

$shop_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2;

// Fetch shop owner details
$shop_sql = "SELECT full_name, email, phone, image_url FROM users WHERE user_id = ? AND role = 'Shop-Owner'";
$shop_stmt = $conn->prepare($shop_sql);
$shop_stmt->bind_param("i", $shop_id);
$shop_stmt->execute();
$shop_result = $shop_stmt->get_result();
$shop_owner = $shop_result->fetch_assoc();
$shop_stmt->close();

if (!$shop_owner) {
    die("Shop not found");
}

// Fetch available products in the shop using actual shop_products table structure
$sql = "SELECT p.product_id, p.name AS product_name, p.category, 
        COALESCE(sp.selling_price, p.price) as price, p.unit, p.img_url, 
        sp.quantity,
        sp.bought_price
        FROM shop_products sp
        INNER JOIN products p ON p.product_id = sp.product_id
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($shop_owner['full_name']) ?> - Shop Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Shop Header -->
    <div class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-4">
                <?php if ($shop_owner['image_url']): ?>
                    <img src="../../<?= htmlspecialchars($shop_owner['image_url']) ?>"
                        alt="<?= htmlspecialchars($shop_owner['full_name']) ?>"
                        class="w-16 h-16 rounded-full object-cover border-2 border-green-500">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-green-500 flex items-center justify-center text-white text-2xl font-bold">
                        <?= strtoupper(substr($shop_owner['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($shop_owner['full_name']) ?></h1>
                    <!-- <p class="text-gray-600">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                            <?= htmlspecialchars($shop_owner['email']) ?>
                        </span>
                        <span class="ml-4 inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                            </svg>
                            <?= htmlspecialchars($shop_owner['phone']) ?>
                        </span>
                    </p> -->
                </div>
            </div>
        </div>
    </div>

    <!-- Products Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Available Products</h2>
            <input type="text" id="searchInput"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="Search products...">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="productList">
            <?php foreach ($available_products as $product): ?>
                <div class="product-item bg-white rounded-lg shadow-md overflow-hidden product-card">
                    <img src="../../<?= htmlspecialchars($product['img_url']) ?>"
                        class="product-img"
                        alt="<?= htmlspecialchars($product['product_name']) ?>"
                        onerror="this.src='../../assets/products-image/default-product.png'">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= htmlspecialchars($product['product_name']) ?></h3>
                        <div class="space-y-1 text-sm">
                            <p class="text-gray-600">
                                <span class="font-medium">Category:</span>
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                    <?= htmlspecialchars($product['category']) ?>
                                </span>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Price:</span>
                                <span class="text-green-600 font-bold text-lg">৳<?= number_format($product['price'], 2) ?></span>
                                <span class="text-gray-500">/ <?= htmlspecialchars($product['unit']) ?></span>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Available:</span>
                                <span class="font-semibold <?= $product['quantity'] < 10 ? 'text-red-600' : 'text-green-600' ?>">
                                    <?= number_format($product['quantity']) ?> <?= htmlspecialchars($product['unit']) ?>
                                </span>
                            </p>
                        </div>
                        <button class="mt-4 w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition-colors font-medium">
                            Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($available_products)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="mt-4 text-xl font-medium text-gray-900">No products available</h3>
                <p class="mt-2 text-gray-500">This shop doesn't have any products in stock right now.</p>
            </div>
        <?php endif; ?>
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