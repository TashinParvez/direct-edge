<?php
// PHP backend logic if needed
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Products</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Boxicons CDN -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body class="bg-gray-100 font-sans relative">

    <!-- Floating Cart Button -->
    <button id="cartBtn"
        class="fixed bottom-5 right-5 bg-blue-500 text-white px-5 py-3 rounded-full shadow-lg hover:bg-blue-600 z-50 flex items-center">
        <i class='bx bx-cart mr-2'></i> Cart (<span id="cartCount">0</span>)
    </button>

    <section class="p-6 max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Available Products</h2>
        </div>

        <!-- Search bar -->
        <div class="mb-8 flex justify-center">
            <div class="relative w-full max-w-md">
                <input type="search" placeholder="Search products..."
                    class="w-full border border-gray-300 rounded-full py-2 px-4 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class='bx bx-search absolute left-3 top-2.5 text-gray-400'></i>
            </div>
        </div>

        <!-- Carousel -->
        <div class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Featured Discounts</h2>
            <div class="relative overflow-hidden">
                <div id="carousel" class="flex transition-transform duration-500 ease-in-out">
                    <!-- Sample Card -->
                    <div class="min-w-[250px] bg-white rounded-lg shadow p-4 mx-2 flex-shrink-0 product-card" data-id="1"
                        data-name="Product 1" data-price="15" data-stock="10">
                        <img src="../../include/All Images/Single-Produc-Images/istockphoto-1036777904-612x612.jpg"
                            alt="Product 1" class="w-full h-48 object-cover rounded-md mb-2">
                        <h3 class="font-semibold text-lg">Product 1</h3>
                        <p class="text-gray-500 mb-1"><span class="line-through">$20</span> <span
                                class="text-red-500">$15</span> <span class="text-green-600">(25% off)</span></p>
                        <span class="text-green-600 font-medium">In Stock</span>
                        <div class="mt-4 flex justify-between items-center">
                            <button
                                class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 add-btn">Add Item
                            </button>
                            <a href="#" class="text-blue-500 underline">View Details</a>
                        </div>
                    </div>
                    <!-- Add more cards as needed -->
                </div>

                <!-- Carousel Buttons -->
                <button id="prevBtn"
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full shadow p-2 hover:bg-gray-100">&#10094;</button>
                <button id="nextBtn"
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full shadow p-2 hover:bg-gray-100">&#10095;</button>
            </div>
        </div>

        <!-- All Products -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-semibold text-gray-700">All Products</h2>
            <select class="border border-gray-300 rounded px-3 py-2">
                <option>Category: Default</option>
                <option>Fresh Produce</option>
                <option>Vegetables</option>
                <option>Beverages</option>
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Product Card -->
            <div class="bg-white p-4 rounded-lg shadow flex flex-col product-card" data-id="2"
                data-name="Product 2" data-price="24" data-stock="5">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg"
                    alt="Product 2" class="h-48 w-full object-cover rounded-md mb-2">
                <h3 class="font-semibold text-lg">Product 2</h3>
                <p class="text-gray-500 mb-1">High-quality widget for everyday use, durable and reliable.</p>
                <span class="text-sm text-gray-400">Bakery</span>
                <p class="text-gray-500 mb-1"><span class="line-through">$30</span> <span
                        class="text-red-500">$24</span> <span class="text-green-600">(20% off)</span></p>
                <span class="text-green-600 font-medium">In Stock</span>
                <span class="text-gray-500 text-sm mb-2">5 items left</span>
                <div class="mt-auto flex justify-between items-center">
                    <button
                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 add-btn">Add Item</button>
                    <a href="#" class="text-blue-500 underline">View Details</a>
                </div>
            </div>
        </div>

    </section>

    <!-- Add Item Modal -->
    <div id="addModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
        <div class="bg-white rounded-lg p-6 w-80 relative">
            <button id="closeAddModal" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-xl">&times;</button>
            <h3 class="text-xl font-semibold mb-4" id="modalProductName"></h3>
            <p class="mb-2">Price: $<span id="modalProductPrice"></span></p>
            <p class="mb-4">Available: <span id="modalProductStock"></span></p>
            <div class="flex items-center mb-4">
                <button id="decreaseQty" class="px-3 py-1 bg-gray-200 rounded-l hover:bg-gray-300">-</button>
                <input type="number" id="modalQty" value="1" min="1" class="w-12 text-center border-t border-b border-gray-300">
                <button id="increaseQty" class="px-3 py-1 bg-gray-200 rounded-r hover:bg-gray-300">+</button>
            </div>
            <button id="addToCartBtn" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Add to Cart</button>
        </div>
    </div>

    <!-- Cart Modal -->
    <div id="cartModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
        <div class="bg-white rounded-lg p-6 w-96 relative max-h-[80vh] overflow-y-auto">
            <button id="closeCartModal" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-xl">&times;</button>
            <h3 class="text-xl font-semibold mb-4">Your Cart</h3>
            <div id="cartItems" class="space-y-4"></div>
            <div class="mt-4 flex justify-between font-semibold text-lg">
                <span>Total:</span>
                <span id="cartTotal">$0</span>
            </div>
            <button id="submitOrder"
                class="mt-4 w-full bg-green-500 text-white py-2 rounded hover:bg-green-600">Submit Order</button>
        </div>
    </div>

    <script>
        // Carousel
        const carousel = document.getElementById('carousel');
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        let index = 0;

        nextBtn.addEventListener('click', () => {
            const cardWidth = carousel.children[0].offsetWidth + 16;
            index++;
            if (index >= carousel.children.length) index = 0;
            carousel.style.transform = `translateX(-${index * cardWidth}px)`;
        });

        prevBtn.addEventListener('click', () => {
            const cardWidth = carousel.children[0].offsetWidth + 16;
            index--;
            if (index < 0) index = carousel.children.length - 1;
            carousel.style.transform = `translateX(-${index * cardWidth}px)`;
        });

        // Cart
        let cart = {};

        const cartBtn = document.getElementById('cartBtn');
        const cartModal = document.getElementById('cartModal');
        const closeCartModal = document.getElementById('closeCartModal');
        const cartItemsDiv = document.getElementById('cartItems');
        const cartCount = document.getElementById('cartCount');
        const cartTotal = document.getElementById('cartTotal');
        const submitOrder = document.getElementById('submitOrder');

        cartBtn.addEventListener('click', () => {
            renderCart();
            cartModal.classList.remove('hidden');
            cartModal.classList.add('flex');
        });

        closeCartModal.addEventListener('click', () => {
            cartModal.classList.add('hidden');
        });

        cartModal.addEventListener('click', e => {
            if (e.target === cartModal) cartModal.classList.add('hidden');
        });

        // Add Item Modal
        const addBtns = document.querySelectorAll('.add-btn');
        const addModal = document.getElementById('addModal');
        const closeAddModal = document.getElementById('closeAddModal');
        const modalProductName = document.getElementById('modalProductName');
        const modalProductPrice = document.getElementById('modalProductPrice');
        const modalProductStock = document.getElementById('modalProductStock');
        const modalQty = document.getElementById('modalQty');
        const increaseQty = document.getElementById('increaseQty');
        const decreaseQty = document.getElementById('decreaseQty');
        const addToCartBtn = document.getElementById('addToCartBtn');

        let currentProduct = null;

        addBtns.forEach(btn => {
            btn.addEventListener('click', e => {
                const card = e.target.closest('.product-card');
                currentProduct = {
                    id: card.dataset.id,
                    name: card.dataset.name,
                    price: parseFloat(card.dataset.price),
                    stock: parseInt(card.dataset.stock)
                };
                modalProductName.textContent = currentProduct.name;
                modalProductPrice.textContent = currentProduct.price;
                modalProductStock.textContent = currentProduct.stock;
                modalQty.value = 1;
                addModal.classList.remove('hidden');
                addModal.classList.add('flex');
            });
        });

        closeAddModal.addEventListener('click', () => {
            addModal.classList.add('hidden');
        });

        addModal.addEventListener('click', e => {
            if (e.target === addModal) addModal.classList.add('hidden');
        });

        increaseQty.addEventListener('click', () => {
            let val = parseInt(modalQty.value);
            if (val < currentProduct.stock) modalQty.value = val + 1;
        });

        decreaseQty.addEventListener('click', () => {
            let val = parseInt(modalQty.value);
            if (val > 1) modalQty.value = val - 1;
        });

        addToCartBtn.addEventListener('click', () => {
            const qty = parseInt(modalQty.value);
            if (cart[currentProduct.id]) {
                cart[currentProduct.id].qty += qty;
                if (cart[currentProduct.id].qty > currentProduct.stock) {
                    cart[currentProduct.id].qty = currentProduct.stock;
                }
            } else {
                cart[currentProduct.id] = {
                    ...currentProduct,
                    qty
                };
            }
            updateCartCount();
            addModal.classList.add('hidden');
        });

        function renderCart() {
            cartItemsDiv.innerHTML = '';
            let total = 0;
            for (let id in cart) {
                const item = cart[id];
                total += item.price * item.qty;
                const itemDiv = document.createElement('div');
                itemDiv.classList.add('flex', 'justify-between', 'items-center', 'border-b', 'pb-2');
                itemDiv.innerHTML = `
                    <div>
                        <h4 class="font-semibold">${item.name}</h4>
                        <p>$${item.price} x ${item.qty}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="text-gray-600 hover:text-gray-800 decrease" data-id="${item.id}">-</button>
                        <span>${item.qty}</span>
                        <button class="text-gray-600 hover:text-gray-800 increase" data-id="${item.id}">+</button>
                        <button class="text-red-500 hover:text-red-700 ml-2 remove" data-id="${item.id}">&times;</button>
                    </div>
                `;
                cartItemsDiv.appendChild(itemDiv);
            }
            cartTotal.textContent = '$' + total.toFixed(2);

            // Add events
            document.querySelectorAll('.increase').forEach(btn => {
                btn.addEventListener('click', e => {
                    const id = e.target.dataset.id;
                    if (cart[id].qty < cart[id].stock) cart[id].qty++;
                    renderCart();
                    updateCartCount();
                });
            });
            document.querySelectorAll('.decrease').forEach(btn => {
                btn.addEventListener('click', e => {
                    const id = e.target.dataset.id;
                    if (cart[id].qty > 1) cart[id].qty--;
                    renderCart();
                    updateCartCount();
                });
            });
            document.querySelectorAll('.remove').forEach(btn => {
                btn.addEventListener('click', e => {
                    const id = e.target.dataset.id;
                    delete cart[id];
                    renderCart();
                    updateCartCount();
                });
            });
        }

        function updateCartCount() {
            let count = 0;
            for (let id in cart) count += cart[id].qty;
            cartCount.textContent = count;
        }

        submitOrder.addEventListener('click', () => {
            if (Object.keys(cart).length === 0) {
                alert("Cart is empty!");
                return;
            }
            console.log("Submitting order:", cart);
            alert("Order submitted successfully!");
            cart = {};
            updateCartCount();
            cartModal.classList.add('hidden');
        });
    </script>

</body>

</html>