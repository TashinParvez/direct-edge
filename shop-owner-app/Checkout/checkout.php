<?php
session_start();
include '../../include/connect-db.php';

// Get cart items from session or database
$cartItems = $_SESSION['cart'] ?? [];
$cartTotal = 0;
$itemCount = 0;

// Calculate cart totals
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
    $itemCount += $item['quantity'];
}

// Apply promo code discount
$discount = 0;
$promoCode = '';
if (isset($_SESSION['promo_code'])) {
    $promoCode = $_SESSION['promo_code'];
    $discount = $cartTotal * 0.1; // 10% discount example
}

$finalTotal = $cartTotal - $discount;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .checkout-container {
            transition: all 0.3s ease;
        }
        .form-field {
            transition: all 0.3s ease;
        }
        .form-field:hover {
            background-color: #f3f4f6;
        }
        .form-field:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .cart-item {
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            background-color: #f9fafb;
        }
        .page-enter {
            animation: pageEnter 0.6s ease-out;
        }
        @keyframes pageEnter {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-in-left {
            animation: slideInLeft 0.5s ease-out;
        }
        .slide-in-right {
            animation: slideInRight 0.5s ease-out 0.2s both;
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .payment-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .payment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body class="bg-gray-100 page-enter">
    <?php include '../../include/Sidebar.php'; ?>
    
    <section class="home-section p-0">
        <div class="flex justify-between items-center p-6 bg-white shadow-sm border-b">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
                <p class="text-gray-600 mt-1">Complete your order and payment details</p>
            </div>
            <div class="flex items-center space-x-2 bg-blue-100 px-4 py-2 rounded-lg">
                <i class='bx bx-shield-check text-2xl text-blue-600'></i>
                <div>
                    <div class="text-sm font-semibold text-blue-900">Secure Checkout</div>
                    <div class="text-xs text-blue-600">SSL Protected</div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-6 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Order Summary - Right Side -->
                <div class="lg:w-1/3 slide-in-right">
                    <div class="bg-white shadow-xl rounded-xl p-6 border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <i class='bx bx-cart mr-2 text-blue-600'></i>
                                Order Summary
                            </h3>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                                <?= $itemCount ?> items
                            </span>
                        </div>

                        <!-- Cart Items -->
                        <div class="space-y-4 mb-6">
                            <?php if (!empty($cartItems)): ?>
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="cart-item flex justify-between items-center p-3 rounded-lg border border-gray-100">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-gray-900">৳<?= number_format($item['price'] * $item['quantity']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <i class='bx bx-cart-alt text-6xl text-gray-300 mb-4'></i>
                                    <div class="text-gray-500">Your cart is empty</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Promo Code -->
                        <div class="mb-6">
                            <form class="flex space-x-2" method="POST" action="apply_promo.php">
                                <input type="text" name="promo_code" value="<?= $promoCode ?>" 
                                    placeholder="Enter promo code" 
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 form-field">
                                <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                                    Apply
                                </button>
                            </form>
                        </div>

                        <!-- Order Totals -->
                        <div class="border-t border-gray-200 pt-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">৳<?= number_format($cartTotal) ?></span>
                            </div>
                            <?php if ($discount > 0): ?>
                                <div class="flex justify-between text-green-600">
                                    <span>Discount (<?= $promoCode ?>):</span>
                                    <span>-৳<?= number_format($discount) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping:</span>
                                <span class="font-medium">৳100</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-3">
                                <span>Total:</span>
                                <span class="text-blue-600">৳<?= number_format($finalTotal + 100) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Form - Left Side -->
                <div class="lg:w-2/3 slide-in-left">
                    <div class="bg-white shadow-xl rounded-xl p-8 border border-gray-100 checkout-container">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <i class='bx bx-user text-2xl text-green-600'></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Billing Information</h2>
                                <p class="text-gray-600">Please fill in your details for delivery</p>
                            </div>
                        </div>

                        <form id="paymentForm" class="space-y-6">
                            <!-- Personal Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-user mr-1'></i>First Name
                                    </label>
                                    <input type="text" id="firstName" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                        placeholder="Enter your first name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-user mr-1'></i>Last Name
                                    </label>
                                    <input type="text" id="lastName" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                        placeholder="Enter your last name">
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-phone mr-1'></i>Phone Number
                                    </label>
                                    <input type="tel" id="phone" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                        placeholder="+880 1X XXX XXXXX">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-envelope mr-1'></i>Email Address
                                    </label>
                                    <input type="email" id="email" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                        placeholder="your@email.com">
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class='bx bx-map-pin mr-1'></i>Street Address
                                </label>
                                <input type="text" id="address" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                    placeholder="House/Flat No, Street Name">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class='bx bx-building mr-1'></i>Area/Landmark (Optional)
                                </label>
                                <input type="text" id="address2"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                    placeholder="Nearby landmark or area">
                            </div>

                            <!-- Location Details -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-world mr-1'></i>Country
                                    </label>
                                    <select id="country" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                                        <option value="">Choose Country</option>
                                        <option value="BD" selected>Bangladesh</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-map mr-1'></i>Division
                                    </label>
                                    <select id="state" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                                        <option value="">Choose Division</option>
                                        <option value="dhaka">Dhaka</option>
                                        <option value="chittagong">Chittagong</option>
                                        <option value="sylhet">Sylhet</option>
                                        <option value="barisal">Barisal</option>
                                        <option value="khulna">Khulna</option>
                                        <option value="rajshahi">Rajshahi</option>
                                        <option value="rangpur">Rangpur</option>
                                        <option value="mymensingh">Mymensingh</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class='bx bx-location-plus mr-1'></i>Postal Code
                                    </label>
                                    <input type="text" id="zip" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 form-field"
                                        placeholder="1000">
                                </div>
                            </div>

                            <!-- Preferences -->
                            <div class="border-t border-gray-200 pt-6">
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="same-address" class="mr-3 rounded">
                                        <span class="text-sm text-gray-700">Shipping address is the same as billing address</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="save-info" class="mr-3 rounded">
                                        <span class="text-sm text-gray-700">Save this information for future orders</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class='bx bx-credit-card mr-2 text-green-600'></i>
                                    Payment Method
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="border border-gray-300 rounded-lg p-4 text-center hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition-colors">
                                        <i class='bx bx-credit-card text-2xl text-blue-600 mb-2'></i>
                                        <div class="text-sm font-medium">Credit Card</div>
                                    </div>
                                    <div class="border border-gray-300 rounded-lg p-4 text-center hover:border-green-500 hover:bg-green-50 cursor-pointer transition-colors">
                                        <i class='bx bx-mobile text-2xl text-green-600 mb-2'></i>
                                        <div class="text-sm font-medium">bKash</div>
                                    </div>
                                    <div class="border border-gray-300 rounded-lg p-4 text-center hover:border-purple-500 hover:bg-purple-50 cursor-pointer transition-colors">
                                        <i class='bx bx-wallet text-2xl text-purple-600 mb-2'></i>
                                        <div class="text-sm font-medium">Nagad</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-6">
                                <button type="submit" id="sslczPayBtn" 
                                    class="w-full payment-btn text-white py-4 px-6 rounded-lg font-semibold text-lg"
                                    postdata="{}" order="<?= uniqid() ?>" endpoint="checkout_ajax.php">
                                    <i class='bx bx-lock-alt mr-2'></i>
                                    Complete Secure Payment - ৳<?= number_format($finalTotal + 100) ?>
                                </button>
                                <div class="text-center mt-4 text-sm text-gray-500">
                                    <i class='bx bx-shield-check mr-1'></i>
                                    Your payment information is secure and encrypted
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-8 text-center">
            <div class="animate-spin w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-4"></div>
            <div class="text-lg font-semibold text-gray-900">Processing Payment...</div>
            <div class="text-sm text-gray-500 mt-2">Please do not close this window</div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            document.getElementById('loadingOverlay').classList.remove('hidden');
            
            // Validate form
            const requiredFields = ['firstName', 'lastName', 'phone', 'email', 'address', 'country', 'state', 'zip'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('border-red-500');
                    isValid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                document.getElementById('loadingOverlay').classList.add('hidden');
                alert('Please fill in all required fields');
                return;
            }
            
            // Prepare payment data
            const paymentData = {
                cus_name: document.getElementById('firstName').value + ' ' + document.getElementById('lastName').value,
                cus_phone: document.getElementById('phone').value,
                cus_email: document.getElementById('email').value,
                cus_addr1: document.getElementById('address').value,
                amount: <?= $finalTotal + 100 ?>,
                order_id: 'ORDER_' + Date.now()
            };
            
            // Set payment data
            $('#sslczPayBtn').prop('postdata', paymentData);
            
            // Simulate processing delay
            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                alert('Payment integration would be processed here');
            }, 2000);
        });

        // SSL Commerz integration
        var obj = {
            cus_name: 'Customer Name',
            cus_phone: '+8801XXXXXXXXX',
            cus_email: 'customer@email.com',
            cus_addr1: 'Customer Address',
            amount: <?= $finalTotal + 100 ?>
        };
        
        $('#sslczPayBtn').prop('postdata', obj);
        
        (function (window, document) {
            var loader = function () {
                var script = document.createElement("script"), tag = document.getElementsByTagName("script")[0];
                script.src = "https://sandbox.sslcommerz.com/embed.min.js?" + Math.random().toString(36).substring(7);
                tag.parentNode.insertBefore(script, tag);
            };
            
            window.addEventListener ? window.addEventListener("load", loader, false) : window.attachEvent("onload", loader);
        })(window, document);

        // Form field animations
        document.querySelectorAll('.form-field').forEach(field => {
            field.addEventListener('focus', function() {
                this.classList.add('ring-2', 'ring-green-500', 'border-green-500');
            });
            
            field.addEventListener('blur', function() {
                this.classList.remove('ring-2', 'ring-green-500', 'border-green-500');
            });
        });

        // Payment method selection
        document.querySelectorAll('.border').forEach(method => {
            if (method.classList.contains('cursor-pointer')) {
                method.addEventListener('click', function() {
                    // Remove selection from all methods
                    document.querySelectorAll('.border').forEach(m => {
                        if (m.classList.contains('cursor-pointer')) {
                            m.classList.remove('border-2', 'border-blue-500', 'bg-blue-50');
                        }
                    });
                    // Add selection to clicked method
                    this.classList.add('border-2', 'border-blue-500', 'bg-blue-50');
                });
            }
        });
    </script>
</body>

</html>