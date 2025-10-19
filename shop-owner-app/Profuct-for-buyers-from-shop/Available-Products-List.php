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
        sp.selling_price, sp.bought_price, p.unit, p.img_url, 
        sp.quantity
        FROM shop_products sp
        INNER JOIN products p ON p.product_id = sp.product_id
        WHERE sp.shop_id = ?
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #000;
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
                                <span class="font-medium">Bought Price:</span>
                                <span class="text-gray-700 font-semibold">৳<?= number_format($product['bought_price'], 2) ?></span>
                                <span class="text-gray-500">/ <?= htmlspecialchars($product['unit']) ?></span>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Selling Price:</span>
                                <span class="text-green-600 font-bold text-lg">৳<?= number_format($product['selling_price'], 2) ?></span>
                                <span class="text-gray-500">/ <?= htmlspecialchars($product['unit']) ?></span>
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Available:</span>
                                <span class="font-semibold <?= $product['quantity'] < 10 ? 'text-red-600' : 'text-green-600' ?>">
                                    <?= number_format($product['quantity']) ?> <?= htmlspecialchars($product['unit']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button onclick="openUpdateModal(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_name']) ?>', <?= $product['selling_price'] ?>, <?= $product['quantity'] ?>, '<?= htmlspecialchars($product['unit']) ?>')"
                                class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Update
                            </button>
                            <button onclick="deleteProduct(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_name']) ?>')"
                                class="bg-red-600 text-white p-2 rounded-lg hover:bg-red-700 transition-colors" title="Delete Product">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
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

    <!-- Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUpdateModal()">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Update Product</h2>
            <form id="updateForm" onsubmit="updateProduct(event)">
                <input type="hidden" id="update_product_id">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Product Name</label>
                    <input type="text" id="update_product_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Selling Price (৳)</label>
                    <input type="number" step="0.01" id="update_selling_price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Quantity</label>
                    <input type="number" step="0.01" id="update_quantity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    <span id="unit_display" class="text-sm text-gray-500"></span>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-medium">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeUpdateModal()" class="flex-1 bg-gray-400 text-white py-2 rounded-lg hover:bg-gray-500 font-medium">
                        Cancel
                    </button>
                </div>
            </form>
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

        // Open update modal
        function openUpdateModal(productId, productName, sellingPrice, quantity, unit) {
            document.getElementById('update_product_id').value = productId;
            document.getElementById('update_product_name').value = productName;
            document.getElementById('update_selling_price').value = sellingPrice;
            document.getElementById('update_quantity').value = quantity;
            document.getElementById('unit_display').textContent = unit;
            document.getElementById('updateModal').style.display = 'block';
        }

        // Close update modal
        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('updateModal');
            if (event.target == modal) {
                closeUpdateModal();
            }
        }

        // Update product
        function updateProduct(event) {
            event.preventDefault();

            const productId = document.getElementById('update_product_id').value;
            const sellingPrice = document.getElementById('update_selling_price').value;
            const quantity = document.getElementById('update_quantity').value;

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('selling_price', sellingPrice);
            formData.append('quantity', quantity);
            formData.append('shop_id', <?= $shop_id ?>);

            fetch('update_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error updating product: ' + error);
                });
        }

        // Delete product
        function deleteProduct(productId, productName) {
            if (!confirm(`Are you sure you want to delete "${productName}" from your shop?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('product_id', productId);
            formData.append('shop_id', <?= $shop_id ?>);

            fetch('update_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting product: ' + error);
                });
        }
    </script>
</body>

</html>