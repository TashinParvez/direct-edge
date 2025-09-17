<?php

// include '../../include/connect-db.php';


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Requests</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="all-inventory-requests.css">
    
</head>

<body>

    <?php // include '../direct-edge/include/Sidebar.php'; 
    ?>
    <section>
        <h1>Inventory Requests</h1>

        <!-- Search bar -->
        <div class="search-bar">
            <div class="search-container">
                <input type="search" id="search-input" placeholder="Search products...">
                <i class='bx bx-search search-icon'></i>
            </div>
        </div>

        <div class="main-container">
            <!-- Filter column -->
            <div class="filter-column">
                <!-- Volume -->
                <div class="filter-group">
                    <label><span>Volume more than:</span> <input type="number" id="min-volume" step="0.1"><span>m³</span></label>
                    <label><span>Volume less than:</span> <input type="number" id="max-volume" step="0.1"><span>m³</span></label>
                </div>

                <!-- Duration -->
                <div class="filter-group">
                    <label><span>Duration more than:</span> <input type="number" id="min-duration" step="1"><span>days</span></label>
                    <label><span>Duration less than:</span> <input type="number" id="max-duration" step="1"><span>days</span></label>
                </div>

                <!-- Start Date -->
                <div class="filter-group">
                    <h3>Start Date</h3>
                    <input type="date" id="start-date">
                    <div class="date-options">
                        <label><input type="radio" name="start-date-option" value="before"> Before this date</label>
                        <label><input type="radio" name="start-date-option" value="after"> After this date</label>
                    </div>
                </div>

                <!-- End Date -->
                <div class="filter-group">
                    <h3>End Date</h3>
                    <input type="date" id="end-date">
                    <div class="date-options">
                        <label><input type="radio" name="end-date-option" value="before"> Before this date</label>
                        <label><input type="radio" name="end-date-option" value="after"> After this date</label>
                    </div>
                </div>

                <!-- Categories -->
                <div class="filter-group">
                    <h3>Categories</h3>
                    <label><input type="checkbox" class="category-checkbox" value="Fresh Produce"> Fresh Produce</label>
                    <label><input type="checkbox" class="category-checkbox" value="Dairy & Eggs"> Dairy & Eggs</label>
                    <label><input type="checkbox" class="category-checkbox" value="Meat & Poultry"> Meat & Poultry</label>
                    <label><input type="checkbox" class="category-checkbox" value="Seafood"> Seafood</label>
                    <label><input type="checkbox" class="category-checkbox" value="Bakery"> Bakery</label>
                    <label><input type="checkbox" class="category-checkbox" value="Frozen Foods"> Frozen Foods</label>
                    <label><input type="checkbox" class="category-checkbox" value="Canned Goods"> Canned Goods</label>
                    <label><input type="checkbox" class="category-checkbox" value="Snacks & Sweets"> Snacks & Sweets</label>
                    <label><input type="checkbox" class="category-checkbox" value="Beverages"> Beverages</label>
                    <label><input type="checkbox" class="category-checkbox" value="Household Essentials"> Household Essentials</label>
                </div>
            </div>

            <!-- Cards column -->
            <div class="cards-column">
                <!-- Header -->
                <div class="all-products-header">
                    <h2 id="requests-header">All Requests</h2>
                    <div>
                        <span class="label-text">Show:</span>
                        <select class="dropdown" id="per-page">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Display All</option>
                        </select>
                        <span class="label-text">Order by:</span>
                        <select class="dropdown" id="sort-by">
                            <option value="request-order" selected>Request Order (First Come, First Serve)</option>
                            <option value="volume-high-low">Volume (Highest to Lowest)</option>
                            <option value="volume-low-high">Volume (Lowest to Highest)</option>
                            <option value="duration-high-low">Duration (Highest to Lowest)</option>
                            <option value="duration-low-high">Duration (Lowest to Highest)</option>
                            <option value="a-z">A-Z</option>
                        </select>
                    </div>
                </div>

                <!-- Cards container -->
                <div class="products-container">
                    <!-- Cards will be rendered here by JS -->
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <button id="first-page">&lt;&lt;</button>
                    <button id="prev-page">&lt;</button>
                    <span id="page-info">1 / 1</span>
                    <button id="next-page">&gt;</button>
                    <button id="last-page">&gt;&gt;</button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div id="requestModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2 id="modalHeading"></h2>
                <img id="modalImage" src="" alt="Product Thumbnail">
                <div class="modal-details">
                    <p><strong>Product Name:</strong> <span id="modalName"></span></p>
                    <p><strong>Category:</strong> <span id="modalCategory"></span></p>
                    <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
                    <p><strong>Inventory Need From:</strong> <span id="modalStartDate"></span></p>
                    <p><strong>Duration:</strong> <span id="modalDuration"></span> days</p>
                </div>
                <div class="modal-buttons">
                    <a class="switch-branch" href="#">Switch to other branch</a>
                    <button class="accept">Accept</button>
                    <button class="reject">Reject</button>
                </div>
            </div>
        </div>
    </section>
</body>

<script>
    // Sample data
    const items = [{
            id: 1,
            name: "Product 1",
            category: "Fresh Produce",
            requiredSpace: 50000.5,
            duration: 30,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-510618777-612x612.jpg",
            endDate: "2025-09-10",
            startDate: "2025-08-01",
            quantity: "100 kg"
        },
        {
            id: 2,
            name: "Product 2",
            category: "Dairy & Eggs",
            requiredSpace: 120000.2,
            duration: 45,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-510015094-612x612.jpg",
            endDate: "2025-09-20",
            startDate: "2025-08-05",
            quantity: "50 litres"
        },
        {
            id: 3,
            name: "Product 3",
            category: "Meat & Poultry",
            requiredSpace: 30000,
            duration: 60,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-1346380707-612x612.jpg",
            endDate: "2025-10-05",
            startDate: "2025-08-10",
            quantity: "200 pcs"
        },
        {
            id: 4,
            name: "Product 4",
            category: "Seafood",
            requiredSpace: 80000.7,
            duration: 20,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-2204214171-612x612.jpg",
            endDate: "2025-09-15",
            startDate: "2025-08-15",
            quantity: "75 kg"
        },
        {
            id: 5,
            name: "Product 5",
            category: "Bakery",
            requiredSpace: 150000,
            duration: 90,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-2226910903-612x612.jpg",
            endDate: "2025-10-15",
            startDate: "2025-08-20",
            quantity: "120 pcs"
        },
        {
            id: 6,
            name: "Product 6",
            category: "Frozen Foods",
            requiredSpace: 20000.3,
            duration: 15,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-2182314369-612x612.jpg",
            endDate: "2025-09-25",
            startDate: "2025-08-25",
            quantity: "30 litres"
        },
        {
            id: 7,
            name: "Product 7",
            category: "Canned Goods",
            requiredSpace: 60000,
            duration: 50,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-2234472958-612x612.jpg",
            endDate: "2025-10-01",
            startDate: "2025-08-30",
            quantity: "150 pcs"
        },
        {
            id: 8,
            name: "Product 8",
            category: "Snacks & Sweets",
            requiredSpace: 90000.9,
            duration: 25,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-695597866-612x612.jpg",
            endDate: "2025-09-18",
            startDate: "2025-09-01",
            quantity: "80 kg"
        },
        {
            id: 9,
            name: "Product 9",
            category: "Beverages",
            requiredSpace: 40000,
            duration: 35,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-2204214171-612x612.jpg",
            endDate: "2025-10-10",
            startDate: "2025-09-05",
            quantity: "200 litres"
        },
        {
            id: 10,
            name: "Product 10",
            category: "Household Essentials",
            requiredSpace: 70000.4,
            duration: 40,
            image: "../../include/All Images/Single-Produc-Images/istockphoto-2233520596-612x612.jpg",
            endDate: "2025-09-30",
            startDate: "2025-09-10",
            quantity: "90 pcs"
        }
    ];

    // Event listeners for filters
    const filterElements = [
        document.getElementById('min-volume'),
        document.getElementById('max-volume'),
        document.getElementById('min-duration'),
        document.getElementById('max-duration'),
        document.getElementById('start-date'),
        ...document.querySelectorAll('input[name="start-date-option"]'),
        document.getElementById('end-date'),
        ...document.querySelectorAll('input[name="end-date-option"]'),
        ...document.querySelectorAll('.category-checkbox'),
        document.getElementById('per-page'),
        document.getElementById('sort-by'),
        document.getElementById('search-input')
    ];

    filterElements.forEach(el => {
        const eventType = el.type === 'radio' || el.type === 'checkbox' ? 'change' : 'input';
        el.addEventListener(eventType, renderCards);
    });

    // Pagination buttons
    document.getElementById('first-page').addEventListener('click', () => {
        currentPage = 0;
        renderCards();
    });

    document.getElementById('prev-page').addEventListener('click', () => {
        if (currentPage > 0) currentPage--;
        renderCards();
    });

    document.getElementById('next-page').addEventListener('click', () => {
        if (currentPage < totalPages - 1) currentPage++;
        renderCards();
    });

    document.getElementById('last-page').addEventListener('click', () => {
        currentPage = totalPages - 1;
        renderCards();
    });

    // Modal functionality
    const modal = document.getElementById('requestModal');
    const closeModal = document.querySelector('.close-modal');

    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Initial render
    let currentPage = 0;
    let totalPages = 1;
    renderCards();

    function renderCards() {
        const minVolume = parseFloat(document.getElementById('min-volume').value) || 0;
        const maxVolume = parseFloat(document.getElementById('max-volume').value) || Infinity;
        const minDuration = parseInt(document.getElementById('min-duration').value, 10) || 0;
        const maxDuration = parseInt(document.getElementById('max-duration').value, 10) || Infinity;
        const startDate = document.getElementById('start-date').value;
        const startOption = document.querySelector('input[name="start-date-option"]:checked')?.value;
        const endDate = document.getElementById('end-date').value;
        const endOption = document.querySelector('input[name="end-date-option"]:checked')?.value;
        const selectedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);
        const searchQuery = document.getElementById('search-input').value.toLowerCase();
        const sortValue = document.getElementById('sort-by').value;
        const perPage = document.getElementById('per-page').value === 'all' ? items.length : parseInt(document.getElementById('per-page').value, 10);

        let filtered = items.filter(item => {
            if (item.requiredSpace < minVolume || item.requiredSpace > maxVolume) return false;
            if (item.duration < minDuration || item.duration > maxDuration) return false;
            if (selectedCategories.length > 0 && !selectedCategories.includes(item.category)) return false;
            if (searchQuery && !item.name.toLowerCase().includes(searchQuery)) return false;

            if (startDate && startOption) {
                const itemStart = new Date(item.startDate);
                const selected = new Date(startDate);
                if (startOption === 'before' && itemStart >= selected) return false;
                if (startOption === 'after' && itemStart <= selected) return false;
            }

            if (endDate && endOption) {
                const itemEnd = new Date(item.endDate);
                const selected = new Date(endDate);
                if (endOption === 'before' && itemEnd >= selected) return false;
                if (endOption === 'after' && itemEnd <= selected) return false;
            }

            return true;
        });

        // Sort
        if (sortValue === 'volume-high-low') {
            filtered.sort((a, b) => b.requiredSpace - a.requiredSpace);
        } else if (sortValue === 'volume-low-high') {
            filtered.sort((a, b) => a.requiredSpace - b.requiredSpace);
        } else if (sortValue === 'duration-high-low') {
            filtered.sort((a, b) => b.duration - a.duration);
        } else if (sortValue === 'duration-low-high') {
            filtered.sort((a, b) => a.duration - b.duration);
        } else if (sortValue === 'a-z') {
            filtered.sort((a, b) => a.name.localeCompare(b.name));
        } else if (sortValue === 'request-order') {
            filtered.sort((a, b) => a.id - b.id);
        }

        // Update header
        const header = document.getElementById('requests-header');
        header.innerText = filtered.length === items.length ? 'All Requests' : 'Filtered Requests';

        // Pagination
        totalPages = Math.ceil(filtered.length / perPage);
        if (currentPage > totalPages - 1) currentPage = 0;
        const start = currentPage * perPage;
        const pageItems = filtered.slice(start, start + perPage);

        // Render cards
        const container = document.querySelector('.products-container');
        container.innerHTML = '';
        pageItems.forEach(item => {
            const card = document.createElement('div');
            card.classList.add('product-row');
            card.innerHTML = `
                <img src="${item.image}" alt="${item.name}">
                <div class="product-info">
                    <h3>${item.name}</h3>
                    <span>Category: ${item.category}</span>
                    <span>Required Space: ${item.requiredSpace} m³</span>
                    <span>Duration: ${item.duration} days</span>
                    <a class="view-details-link" href="#" data-id="${item.id}">View Details</a>
                </div>
            `;
            container.appendChild(card);
        });

        // Add event listeners for view details
        document.querySelectorAll('.view-details-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const itemId = parseInt(link.getAttribute('data-id'));
                const item = items.find(i => i.id === itemId);
                if (item) {
                    document.getElementById('modalHeading').textContent = `Inventory Request for ${item.requiredSpace} m³`;
                    document.getElementById('modalImage').src = item.image;
                    document.getElementById('modalName').textContent = item.name;
                    document.getElementById('modalCategory').textContent = item.category;
                    document.getElementById('modalQuantity').textContent = item.quantity;
                    document.getElementById('modalStartDate').textContent = item.startDate;
                    document.getElementById('modalDuration').textContent = item.duration;
                    modal.style.display = 'block';
                }
            });
        });

        // Update pagination info
        document.getElementById('page-info').innerText = `${totalPages > 0 ? currentPage + 1 : 0} / ${totalPages || 1}`;
        document.getElementById('first-page').disabled = currentPage === 0;
        document.getElementById('prev-page').disabled = currentPage === 0;
        document.getElementById('next-page').disabled = currentPage >= totalPages - 1;
        document.getElementById('last-page').disabled = currentPage >= totalPages - 1;
    }
</script>

</html>