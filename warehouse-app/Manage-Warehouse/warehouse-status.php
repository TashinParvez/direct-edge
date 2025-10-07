<?php
include '../../include/connect-db.php'; // database connection

$sql = "SELECT warehouse_id, name, location, capacity_total, capacity_used, status, type
        FROM `warehouses` WHERE 1";
$result = mysqli_query($conn, $sql);
$allwarehouses = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch distinct locations for dynamic City/Location filter
$locRes = mysqli_query($conn, "SELECT DISTINCT location FROM warehouses WHERE location IS NOT NULL AND TRIM(location) <> '' ORDER BY location ASC");
$locations = mysqli_fetch_all($locRes, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Warehouse Status - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .warehouse-card {
            transition: all 0.3s ease;
        }
        .warehouse-card:hover {
            background-color: #f9fafb;
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .filter-container {
            transition: all 0.3s ease;
        }
        .filter-container:hover {
            background-color: #f9fafb;
        }
        .search-field {
            transition: all 0.3s ease;
        }
        .search-field:hover {
            background-color: #f3f4f6;
        }
        .search-field:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .capacity-bar {
            transition: all 0.3s ease;
        }
        .capacity-fill {
            transition: width 0.5s ease-in-out;
        }
        .warehouse-grid {
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .toggle-btn {
            transition: all 0.2s ease;
        }
        .toggle-btn:hover {
            transform: scale(1.05);
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include '../../include/Sidebar.php'; ?>
    
    <section class="home-section p-0">
        <div class="flex justify-between items-center p-4">
            <h1 class="text-2xl font-bold">Warehouse Status</h1>
            <div class="flex space-x-2 no-print">
                <button onclick="window.print()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">
                    <i class='bx bx-printer'></i> Print
                </button>
                <button class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class='bx bx-plus'></i> Add Warehouse
                </button>
            </div>
        </div>

        <div class="container mx-auto px-4">

            <!-- Search and Filter Section -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-6 filter-container">
                <div class="mb-4">
                    <input type="text" id="searchBar" placeholder="Search warehouses by name, location, or ID..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="filterCity" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-map mr-1'></i>City/Location
                        </label>
                        <select id="filterCity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="All" selected>All Locations</option>
                            <?php foreach ($locations as $loc):
                                $val = trim($loc['location']);
                                if ($val === '') continue;
                            ?>
                                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="filterCapacity" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-cube mr-1'></i>Capacity Range
                        </label>
                        <select id="filterCapacity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="All" selected>All Capacities</option>
                            <option>0 - 1000 sq ft</option>
                            <option>1001 - 5000 sq ft</option>
                            <option>5001+ sq ft</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterStatus" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-check-circle mr-1'></i>Status
                        </label>
                        <select id="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="All" selected>All Status</option>
                            <option>Available</option>
                            <option>Occupied</option>
                            <option>Under Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Results Info -->
            <div class="bg-white shadow-md rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <div id="resultsCount" class="text-lg font-semibold text-gray-700">
                        Showing <?= count($allwarehouses) ?> warehouses
                    </div>
                    <div class="flex space-x-2 no-print">
                        <button class="toggle-btn bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600" onclick="toggleView('grid')">
                            <i class='bx bx-grid-alt mr-1'></i>Grid View
                        </button>
                        <button class="toggle-btn bg-gray-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-600" onclick="toggleView('list')">
                            <i class='bx bx-list-ul mr-1'></i>List View
                        </button>
                    </div>
                </div>
            </div>

        <!-- Warehouse Cards -->
        <div id="warehouseCards" class="space-y-4">
            <?php foreach ($allwarehouses as $warehouse):
                $available = (int)$warehouse['capacity_total'] - (int)$warehouse['capacity_used'];
                $derived = $available > 0 ? 'Available' : 'Occupied';
                if ($warehouse['status'] === 'Under Maintenance') $derived = 'Under Maintenance';
            ?>
                <div
                    data-city="<?= htmlspecialchars(strtolower($warehouse['location'])) ?>"
                    data-capacity-total="<?= (int)$warehouse['capacity_total'] ?>"
                    data-capacity-used="<?= (int)$warehouse['capacity_used'] ?>"
                    data-available="<?= max(0, $available) ?>"
                    data-derived-status="<?= strtolower($derived) ?>"
                    onclick="window.location.href='manage_warehouse.php?id=<?= $warehouse['warehouse_id'] ?>'"
                    class="card bg-white rounded-lg shadow-md p-5 transition hover:shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <h5 class="text-lg font-semibold text-gray-800"><?= $warehouse['name'] ?></h5>
                            <p class="text-gray-600"><strong>Location:</strong> <?= $warehouse['location'] ?></p>
                            <p class="text-gray-600"><strong>Total Capacity:</strong> <?= $warehouse['capacity_total'] ?> ft<sup>2</sup></p>
                            <p class="text-gray-600"><strong>Used:</strong> <?= $warehouse['capacity_used'] ?> ft<sup>2</sup></p>
                            <p class="text-gray-600"><strong>Available:</strong> <?= $available ?> ft<sup>2</sup></p>
                            <p class="text-gray-600"><strong>Type:</strong> <?= $warehouse['type'] ?></p>
                        </div>
                        <div>
                            <?php
                            $statusColors = [
                                'Available' => 'bg-green-100 text-green-800 border border-green-200',
                                'Occupied' => 'bg-red-100 text-red-800 border border-red-200',
                                'Under Maintenance' => 'bg-yellow-100 text-yellow-800 border border-yellow-200'
                            ];
                            $statusIcons = [
                                'Available' => 'bx-check-circle',
                                'Occupied' => 'bx-error-circle',
                                'Under Maintenance' => 'bx-time-five'
                            ];
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $statusColors[$derived] ?>">
                                <i class='<?= $statusIcons[$derived] ?> mr-1'></i><?= $derived ?>
                            </span>
                        </div>
                        
                        <!-- Warehouse Icon -->
                        <div class="p-6 pb-4">
                            <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                <i class='bx bx-buildings text-3xl text-blue-600'></i>
                            </div>
                            
                            <!-- Warehouse Header -->
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($warehouse['name']) ?></h3>
                            <p class="text-sm text-gray-500 mb-4">ID: WH-<?= $warehouse['warehouse_id'] ?></p>
                            
                            <!-- Warehouse Info -->
                            <div class="space-y-3">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class='bx bx-map mr-3 text-gray-400'></i>
                                    <span class="font-medium">Location:</span>
                                    <span class="ml-2"><?= htmlspecialchars($warehouse['location']) ?></span>
                                </div>
                                
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class='bx bx-category mr-3 text-gray-400'></i>
                                    <span class="font-medium">Type:</span>
                                    <span class="ml-2"><?= htmlspecialchars($warehouse['type']) ?></span>
                                </div>
                                
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class='bx bx-cube mr-3 text-gray-400'></i>
                                    <span class="font-medium">Total Capacity:</span>
                                    <span class="ml-2"><?= number_format($warehouse['capacity_total']) ?> sq ft</span>
                                </div>
                                
                                <div class="flex items-center text-sm">
                                    <i class='bx bx-layer-plus mr-3 text-gray-400'></i>
                                    <span class="font-medium text-gray-600">Available:</span>
                                    <span class="ml-2 font-semibold <?= $available > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= number_format($available) ?> sq ft
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Capacity Bar -->
                            <div class="mt-5">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-600">Capacity Usage</span>
                                    <span class="text-sm font-semibold text-gray-900"><?= number_format($usagePercent, 1) ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 capacity-bar">
                                    <div class="h-3 rounded-full capacity-fill <?= $usagePercent >= 90 ? 'bg-red-500' : ($usagePercent >= 70 ? 'bg-yellow-500' : 'bg-green-500') ?>" 
                                         style="width: <?= min(100, $usagePercent) ?>%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1 text-center">
                                    <?= number_format($warehouse['capacity_used']) ?> / <?= number_format($warehouse['capacity_total']) ?> sq ft used
                                </div>
                            </div>
                            
                            <!-- View Details Button -->
                            <div class="mt-5">
                                <div class="bg-gray-50 hover:bg-gray-100 text-center py-3 rounded-lg text-sm font-medium text-gray-700 transition-colors">
                                    <i class='bx bx-show mr-2'></i>View Details
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="bg-white shadow-md rounded-lg p-4 flex justify-center items-center space-x-2">
                <button class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                    <i class='bx bx-chevrons-left'></i>
                </button>
                <button class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                    <i class='bx bx-chevron-left'></i>
                </button>
                <button class="bg-blue-500 text-white px-4 py-2 rounded-lg font-medium">1</button>
                <button class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">2</button>
                <button class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">3</button>
                <button class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                    <i class='bx bx-chevron-right'></i>
                </button>
                <button class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                    <i class='bx bx-chevrons-right'></i>
                </button>
            </div>

        </div>
    </section>

    <!-- JavaScript -->
    <script>
        const searchBar = document.getElementById('searchBar');
        const filterCity = document.getElementById('filterCity');
        const filterCapacity = document.getElementById('filterCapacity');
        const filterStatus = document.getElementById('filterStatus');
        const cards = document.querySelectorAll('#warehouseCards .warehouse-card');
        const resultsCount = document.getElementById('resultsCount');

        // Set default values
        filterCity.value = 'All';
        filterCapacity.value = 'All';
        filterStatus.value = 'All';

        function isInCapacityRange(card, capacity) {
            const total = parseInt(card.dataset.capacityTotal, 10) || 0;
            if (capacity === '0 - 1000 ft\u00B2') return total >= 0 && total <= 1000;
            if (capacity === '1001 - 5000 ft\u00B2') return total >= 1001 && total <= 5000;
            if (capacity === '5001+ ft\u00B2') return total >= 5001;
            return true; // 'All'
        }

        function matchesStatus(card, status) {
            if (status === 'All') return true;
            const derived = card.dataset.derivedStatus;
            return derived === status.toLowerCase();
        }

        function filterWarehouses() {
            const search = searchBar.value.toLowerCase();
            const city = filterCity.value.toLowerCase();
            const capacity = filterCapacity.value;
            const status = filterStatus.value;
            let visibleCount = 0;

            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                const cardCity = card.dataset.city || '';
                let show = true;

                if (search && !text.includes(search)) show = false;
                if (city !== 'all' && city && cardCity !== city) show = false;
                if (!isInCapacityRange(card, capacity)) show = false;
                if (!matchesStatus(card, status)) show = false;

                card.style.display = show ? "" : "none";
                if (show) visibleCount++;
            });

            // Update results count
            resultsCount.textContent = `Showing ${visibleCount} of ${cards.length} warehouses`;
        }

        function toggleView(viewType) {
            const warehouseGrid = document.getElementById('warehouseCards');
            const toggleBtns = document.querySelectorAll('.toggle-btn');
            
            // Update active button
            toggleBtns.forEach(btn => {
                btn.classList.remove('bg-blue-500');
                btn.classList.add('bg-gray-500');
            });
            event.target.classList.remove('bg-gray-500');
            event.target.classList.add('bg-blue-500');
            
            // Update grid class
            if (viewType === 'list') {
                warehouseGrid.className = 'grid grid-cols-1 gap-6 mb-6 warehouse-grid';
            } else {
                warehouseGrid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6 warehouse-grid';
            }
        }

        // Initialize filters
        filterWarehouses();

        // Event listeners
        searchBar.addEventListener('input', filterWarehouses);
        filterCity.addEventListener('change', filterWarehouses);
        filterCapacity.addEventListener('change', filterWarehouses);
        filterStatus.addEventListener('change', filterWarehouses);
    </script>
</body>

</html>