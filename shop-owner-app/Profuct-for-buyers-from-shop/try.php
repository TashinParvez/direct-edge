<?php
// PHP backend logic can go here if needed
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

<body class="bg-gray-100 font-sans">

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
                    <div class="min-w-[250px] bg-white rounded-lg shadow p-4 mx-2 flex-shrink-0">
                        <img src="../../include/All Images/Single-Produc-Images/istockphoto-1036777904-612x612.jpg"
                            alt="Product 1" class="w-full h-48 object-cover rounded-md mb-2">
                        <h3 class="font-semibold text-lg">Product 1</h3>
                        <p class="text-gray-500 mb-1"><span class="line-through">$20</span> <span
                                class="text-red-500">$15</span> <span class="text-green-600">(25% off)</span></p>
                        <span class="text-green-600 font-medium">In Stock</span>
                        <div class="mt-4 flex justify-between items-center">
                            <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                                onclick="window.location.href='#'">Add Item</button>
                            <a href="#" class="text-blue-500 underline">View Details</a>
                        </div>
                    </div>
                    <div class="min-w-[250px] bg-white rounded-lg shadow p-4 mx-2 flex-shrink-0">
                        <img src="../../include/All Images/Single-Produc-Images/istockphoto-1085723794-612x612.jpg"
                            alt="Product 2" class="w-full h-48 object-cover rounded-md mb-2">
                        <h3 class="font-semibold text-lg">Product 2</h3>
                        <p class="text-gray-500 mb-1"><span class="line-through">$30</span> <span
                                class="text-red-500">$24</span> <span class="text-green-600">(20% off)</span></p>
                        <span class="text-red-500 font-medium">Out of Stock</span>
                        <div class="mt-4 flex justify-between items-center">
                            <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                                onclick="window.location.href='#'">Add Item</button>
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
            <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg"
                    alt="Product 1" class="h-48 w-full object-cover rounded-md mb-2">
                <h3 class="font-semibold text-lg">Product 1</h3>
                <p class="text-gray-500 mb-1">High-quality widget for everyday use, durable and reliable.</p>
                <span class="text-sm text-gray-400">Bakery</span>
                <p class="text-gray-500 mb-1"><span class="line-through">$20</span> <span
                        class="text-red-500">$15</span> <span class="text-green-600">(25% off)</span></p>
                <span class="text-green-600 font-medium">In Stock</span>
                <span class="text-gray-500 text-sm mb-2">10 items left</span>
                <div class="mt-auto flex justify-between items-center">
                    <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                        onclick="window.location.href='#'">Add Item</button>
                    <a href="#" class="text-blue-500 underline">View Details</a>
                </div>
            </div>
            <!-- Repeat product cards here -->
            <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg"
                    alt="Product 1" class="h-48 w-full object-cover rounded-md mb-2">
                <h3 class="font-semibold text-lg">Product 1</h3>
                <p class="text-gray-500 mb-1">High-quality widget for everyday use, durable and reliable.</p>
                <span class="text-sm text-gray-400">Bakery</span>
                <p class="text-gray-500 mb-1"><span class="line-through">$20</span> <span
                        class="text-red-500">$15</span> <span class="text-green-600">(25% off)</span></p>
                <span class="text-green-600 font-medium">In Stock</span>
                <span class="text-gray-500 text-sm mb-2">10 items left</span>
                <div class="mt-auto flex justify-between items-center">
                    <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                        onclick="window.location.href='#'">Add Item</button>
                    <a href="#" class="text-blue-500 underline">View Details</a>
                </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg"
                    alt="Product 1" class="h-48 w-full object-cover rounded-md mb-2">
                <h3 class="font-semibold text-lg">Product 1</h3>
                <p class="text-gray-500 mb-1">High-quality widget for everyday use, durable and reliable.</p>
                <span class="text-sm text-gray-400">Bakery</span>
                <p class="text-gray-500 mb-1"><span class="line-through">$20</span> <span
                        class="text-red-500">$15</span> <span class="text-green-600">(25% off)</span></p>
                <span class="text-green-600 font-medium">In Stock</span>
                <span class="text-gray-500 text-sm mb-2">10 items left</span>
                <div class="mt-auto flex justify-between items-center">
                    <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                        onclick="window.location.href='#'">Add Item</button>
                    <a href="#" class="text-blue-500 underline">View Details</a>
                </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg"
                    alt="Product 1" class="h-48 w-full object-cover rounded-md mb-2">
                <h3 class="font-semibold text-lg">Product 1</h3>
                <p class="text-gray-500 mb-1">High-quality widget for everyday use, durable and reliable.</p>
                <span class="text-sm text-gray-400">Bakery</span>
                <p class="text-gray-500 mb-1"><span class="line-through">$20</span> <span
                        class="text-red-500">$15</span> <span class="text-green-600">(25% off)</span></p>
                <span class="text-green-600 font-medium">In Stock</span>
                <span class="text-gray-500 text-sm mb-2">10 items left</span>
                <div class="mt-auto flex justify-between items-center">
                    <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                        onclick="window.location.href='#'">Add Item</button>
                    <a href="#" class="text-blue-500 underline">View Details</a>
                </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg"
                    alt="Product 1" class="h-48 w-full object-cover rounded-md mb-2">
                <h3 class="font-semibold text-lg">Product 1</h3>
                <p class="text-gray-500 mb-1">High-quality widget for everyday use, durable and reliable.</p>
                <span class="text-sm text-gray-400">Bakery</span>
                <p class="text-gray-500 mb-1"><span class="line-through">$20</span> <span
                        class="text-red-500">$15</span> <span class="text-green-600">(25% off)</span></p>
                <span class="text-green-600 font-medium">In Stock</span>
                <span class="text-gray-500 text-sm mb-2">10 items left</span>
                <div class="mt-auto flex justify-between items-center">
                    <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                        onclick="window.location.href='#'">Add Item</button>
                    <a href="#" class="text-blue-500 underline">View Details</a>
                </div>
            </div>
        </div>

    </section>

    <script>
        // Carousel functionality
        const carousel = document.getElementById('carousel');
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        let index = 0;

        nextBtn.addEventListener('click', () => {
            const cardWidth = carousel.children[0].offsetWidth + 16; // margin
            index++;
            if (index >= carousel.children.length) index = 0;
            carousel.style.transform = `translateX(-${index * cardWidth}px)`;
        });

        prevBtn.addEventListener('click', () => {
            const cardWidth = carousel.children[0].offsetWidth + 16; // margin
            index--;
            if (index < 0) index = carousel.children.length - 1;
            carousel.style.transform = `translateX(-${index * cardWidth}px)`;
        });
    </script>

</body>

</html>