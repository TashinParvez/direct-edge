<?php
    include '../../include/connect-db.php';
    include '../../include/navbar.php'; 


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Product</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <!-- <link rel="stylesheet" href="../Available-Products-List/available-products-list.css"> -->
    <link rel="stylesheet" href="available-products-list.css">

</head>

<body>
    <?php //include '../../include/Sidebar.php'; 
    ?>
    <section class="home-section p-0">

        <div>
            <h2>Available Products</h2>
        </div>

        <!-- Search bar -->
        <div class="search-bar">
            <div class="search-container">
                <input type="search" placeholder="Search products...">
                <i class='bx bx-search search-icon'></i>
            </div>
        </div>

        <!-- Carousel for discounted products -->
        <div class="carousel-header">
            <h2>Featured Discounts</h2>
        </div>
        <div class="carousel-container">
            <div class="carousel-slide">
                <!-- Sample discounted product cards -->
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-1036777904-612x612.jpg" alt="Product 1">
                    <h3>Product 1</h3>
                    <span class="price">$20</span>
                    <span class="discounted-price">$15</span>
                    <span class="discount">(25% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-1085723794-612x612.jpg" alt="Product 2">
                    <h3>Product 2</h3>
                    <span class="price">$30</span>
                    <span class="discounted-price">$24</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock out-stock">Out of Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-1198016565-612x612.jpg" alt="Product 3">
                    <h3>Product 3</h3>
                    <span class="price">$50</span>
                    <span class="discounted-price">$35</span>
                    <span class="discount">(30% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-1282866808-612x612.jpg" alt="Product 4">
                    <h3>Product 4</h3>
                    <span class="price">$40</span>
                    <span class="discounted-price">$32</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-1346380707-612x612.jpg" alt="Product 1">
                    <h3>Product 1</h3>
                    <span class="price">$20</span>
                    <span class="discounted-price">$15</span>
                    <span class="discount">(25% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-2204214171-612x612.jpg" alt="Product 2">
                    <h3>Product 2</h3>
                    <span class="price">$30</span>
                    <span class="discounted-price">$24</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock out-stock">Out of Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-2204214171-612x612.jpg" alt="Product 3">
                    <h3>Product 3</h3>
                    <span class="price">$50</span>
                    <span class="discounted-price">$35</span>
                    <span class="discount">(30% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
                <div class="carousel-card">
                    <img src="../../include/All Images/Single-Produc-Images/istockphoto-2233520721-612x612.jpg" alt="Product 4">
                    <h3>Product 4</h3>
                    <span class="price">$40</span>
                    <span class="discounted-price">$32</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <button class="carousel-btn prev">&#10094;</button>
            <button class="carousel-btn next">&#10095;</button>
        </div>

        <!-- All products section -->
        <div class="all-products-header">
            <h2>All Products</h2>
            <select class="dropdown">
                <option>Category: Default</option>
                <option>Fresh Produce</option>
                <option>Vegetables</option>
                <option>Beverages</option>
            </select>
        </div>

        <div class="products-container">
            <!-- Product rows -->
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg" alt="Product 1">
                <div class="product-info">
                    <h3>Product 1</h3>
                    <p class="description">High-quality widget for everyday use, durable and reliable.</p>
                    <span class="category">Bakery</span>
                    <span class="price">$20</span>
                    <span class="discounted-price">$15</span>
                    <span class="discount">(25% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">10 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-510015094-612x612.jpg" alt="Product 2">
                <div class="product-info">
                    <h3>Product 2</h3>
                    <p class="description">Stylish accessory for modern lifestyles, limited stock.</p>
                    <span class="category">Beverages</span>
                    <span class="price">$30</span>
                    <span class="discounted-price">$24</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock out-stock">Out of Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-1346380707-612x612.jpg" alt="Product 3">
                <div class="product-info">
                    <h3>Product 3</h3>
                    <p class="description">Premium gadget with advanced features for professionals.</p>
                    <span class="category">Fresh Produce</span>
                    <span class="price">$50</span>
                    <span class="discounted-price">$35</span>
                    <span class="discount">(30% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">15 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-2204214171-612x612.jpg" alt="Product 4">
                <div class="product-info">
                    <h3>Product 4</h3>
                    <p class="description">Stylish clothing for all seasons.</p>
                    <span class="category">Vegetables</span>
                    <span class="price">$40</span>
                    <span class="discounted-price">$32</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">20 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-2226910903-612x612.jpg" alt="Product 5">
                <div class="product-info">
                    <h3>Product 5</h3>
                    <p class="description">Affordable tool for home and office use.</p>
                    <span class="category">Vegetables</span>
                    <span class="discounted-price">$25</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">12 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-2182314369-612x612.jpg" alt="Product 1">
                <div class="product-info">
                    <h3>Product 1</h3>
                    <p class="description">Versatile widget for multiple applications.</p>
                    <span class="category">Vegetables</span>
                    <span class="price">$20</span>
                    <span class="discounted-price">$15</span>
                    <span class="discount">(25% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">10 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-2234472958-612x612.jpg" alt="Product 2">
                <div class="product-info">
                    <h3>Product 2</h3>
                    <p class="description">Fashionable item with a sleek design.</p>
                    <span class="category">Beverages</span>
                    <span class="price">$30</span>
                    <span class="discounted-price">$24</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock out-stock">Out of Stock</div>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-695597866-612x612.jpg" alt="Product 3">
                <div class="product-info">
                    <h3>Product 3</h3>
                    <p class="description">High-performance gadget for tech enthusiasts.</p>
                    <span class="category">Bakery</span>
                    <span class="price">$50</span>
                    <span class="discounted-price">$35</span>
                    <span class="discount">(30% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">15 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-2204214171-612x612.jpg" alt="Product 4">
                <div class="product-info">
                    <h3>Product 4</h3>
                    <p class="description">Stylish clothing for all seasons.</p>
                    <span class="category">Clothing</span>
                    <span class="price">$40</span>
                    <span class="discounted-price">$32</span>
                    <span class="discount">(20% off)</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">20 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
            <div class="product-row">
                <img src="../../include/All Images/Single-Produc-Images/istockphoto-2233520596-612x612.jpg" alt="Product 5">
                <div class="product-info">
                    <h3>Product 5</h3>
                    <p class="description">Reliable tool for professional tasks.</p>
                    <span class="category">Vegetables</span>
                    <span class="discounted-price">$25</span>
                    <div class="stock in-stock">In Stock</div>
                    <span class="stock-count">12 items left</span>
                    <a class="view-details-link" href="#">View Details</a>
                    <button class="view-details" onclick="window.location.href='#'">Add Item</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Carousel JavaScript
        const carouselSlide = document.querySelector('.carousel-slide');
        const carouselCards = document.querySelectorAll('.carousel-card');
        const prevBtn = document.querySelector('.prev');
        const nextBtn = document.querySelector('.next');

        // Clone first and last cards for circular effect
        const firstCard = carouselCards[0].cloneNode(true);
        const lastCard = carouselCards[carouselCards.length - 1].cloneNode(true);
        carouselSlide.appendChild(firstCard);
        carouselSlide.insertBefore(lastCard, carouselCards[0]);

        // Update card list to include clones
        const allCards = document.querySelectorAll('.carousel-card');
        let currentIndex = 1; // Start at the first real card (after cloned last card)
        const cardWidth = allCards[0].offsetWidth + 20; // Including margin

        // Set initial position
        carouselSlide.style.transform = `translateX(-${currentIndex * cardWidth}px)`;

        nextBtn.addEventListener('click', () => {
            currentIndex++;
            carouselSlide.style.transition = 'transform 0.7s ease';
            carouselSlide.style.transform = `translateX(-${currentIndex * cardWidth}px)`;

            // If at the cloned first card (end of carousel), reset to first real card
            if (currentIndex === allCards.length - 1) {
                setTimeout(() => {
                    carouselSlide.style.transition = 'none';
                    currentIndex = 1;
                    carouselSlide.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
                }, 700); // Match transition duration
            }
        });

        prevBtn.addEventListener('click', () => {
            currentIndex--;
            carouselSlide.style.transition = 'transform 0.7s ease';
            carouselSlide.style.transform = `translateX(-${currentIndex * cardWidth}px)`;

            // If at the cloned last card (start of carousel), reset to last real card
            if (currentIndex === 0) {
                setTimeout(() => {
                    carouselSlide.style.transition = 'none';
                    currentIndex = allCards.length - 2;
                    carouselSlide.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
                }, 700); // Match transition duration
            }
        });

        // Search bar JavaScript
        const searchInput = document.querySelector('.search-bar input');
        const searchIcon = document.querySelector('.search-icon');

        searchInput.addEventListener('focus', () => {
            searchIcon.style.display = 'block';
        });

        searchInput.addEventListener('blur', () => {
            searchIcon.style.display = 'none';
        });

        searchIcon.addEventListener('click', () => {
            const query = searchInput.value;
            console.log('Search query:', query); // Replace with actual search logic
        });
    </script>
</body>

</html>