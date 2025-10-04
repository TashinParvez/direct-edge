<?php
include '../../include/connect-db.php'; // database connection

$sql = "SELECT warehouse_id, name, location, capacity_total, capacity_used, status, type
        FROM `warehouses` WHERE 1";
$result = mysqli_query($conn, $sql);
$allwarehouses = mysqli_fetch_all($result, MYSQLI_ASSOC);

// NEW: fetch distinct locations for dynamic City/Location filter
$locRes = mysqli_query($conn, "SELECT DISTINCT location FROM warehouses WHERE location IS NOT NULL AND TRIM(location) <> '' ORDER BY location ASC");
$locations = mysqli_fetch_all($locRes, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Generate Receipt</title>
    <link rel="icon" type="image/x-icon" href="../../Logo/LogoBG.png">
    <link rel="stylesheet" href="..\include\sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }

        .filter-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
            display: block;
        }

        .badge {
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 12px;
        }

        .badge-available {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-occupied {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-maint {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php
    // include '../../include/Sidebar.php';
    ?>

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-64">
        <!-- Page Header -->
        <h2 class="text-2xl font-bold mb-6">Warehouse Status</h2>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" id="searchBar"
                class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Search warehouses by name or ID...">
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label for="filterCity" class="filter-label">City/Location</label>
                <select id="filterCity" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    <option value="All" selected>All</option>
                    <?php foreach ($locations as $loc):
                        $val = trim($loc['location']);
                        if ($val === '') continue;
                    ?>
                        <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="filterCapacity" class="filter-label">Capacity</label>
                <select id="filterCapacity" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    <option value="All" selected>All</option>
                    <option>0 - 1000 sq ft</option>
                    <option>1001 - 5000 sq ft</option>
                    <option>5001+ sq ft</option>
                </select>
            </div>

            <div>
                <label for="filterStatus" class="filter-label">Status</label>
                <select id="filterStatus" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    <option value="All" selected>All</option>
                    <option>Available</option>
                    <option>Occupied</option>
                    <option>Under Maintenance</option>
                </select>
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
                            <p class="text-gray-600"><strong>Total Capacity:</strong> <?= $warehouse['capacity_total'] ?> sq ft</p>
                            <p class="text-gray-600"><strong>Used:</strong> <?= $warehouse['capacity_used'] ?> sq ft</p>
                            <p class="text-gray-600"><strong>Available:</strong> <?= $available ?> sq ft</p>
                            <p class="text-gray-600"><strong>Type:</strong> <?= $warehouse['type'] ?></p>
                        </div>
                        <div>
                            <?php
                            $badgeClass = $derived === 'Available' ? 'badge-available' : ($derived === 'Occupied' ? 'badge-occupied' : 'badge-maint');
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $derived ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!--============================= Pagination =============================-->
        <div class="flex justify-center mt-8">
            <nav class="inline-flex rounded-md shadow-sm -space-x-px">
                <a href="#"
                    class="px-3 py-2 border border-gray-300 bg-white text-sm text-gray-500 rounded-l-md hover:bg-gray-100">Previous</a>
                <a href="#"
                    class="px-3 py-2 border border-gray-300 bg-blue-500 text-sm text-white font-medium">1</a>
                <a href="#"
                    class="px-3 py-2 border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-100">2</a>
                <a href="#"
                    class="px-3 py-2 border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-100">3</a>
                <a href="#"
                    class="px-3 py-2 border border-gray-300 bg-white text-sm text-gray-500 rounded-r-md hover:bg-gray-100">Next</a>
            </nav>
        </div>
    </div>

    <!-- JS for Filters -->
    <script>
        const searchBar = document.getElementById('searchBar');

        const filterCity = document.getElementById('filterCity');
        const filterCapacity = document.getElementById('filterCapacity');
        const filterStatus = document.getElementById('filterStatus');

        const cards = document.querySelectorAll('#warehouseCards .card');

        // Default all filters to 'All'
        filterCity.value = 'All';
        filterCapacity.value = 'All';
        filterStatus.value = 'All';

        function isInCapacityRange(card, capacity) {
            const total = parseInt(card.dataset.capacityTotal, 10) || 0;
            if (capacity === '0 - 1000 sq ft') return total >= 0 && total <= 1000;
            if (capacity === '1001 - 5000 sq ft') return total >= 1001 && total <= 5000;
            if (capacity === '5001+ sq ft') return total >= 5001;
            return true; // 'All'
        }

        function matchesStatus(card, status) {
            if (status === 'All') return true;
            const derived = card.dataset.derivedStatus; // 'available' | 'occupied' | 'under maintenance'
            return derived === status.toLowerCase();
        }

        function filterWarehouses() {
            const search = searchBar.value.toLowerCase();
            const city = filterCity.value.toLowerCase();
            const capacity = filterCapacity.value;
            const status = filterStatus.value;

            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                const cardCity = card.dataset.city || '';
                let show = true;

                if (search && !text.includes(search)) show = false;
                if (city !== 'all' && city && cardCity !== city) show = false;
                if (!isInCapacityRange(card, capacity)) show = false;
                if (!matchesStatus(card, status)) show = false;

                card.style.display = show ? "" : "none";
            });
        }

        // Run once to ensure filters are applied on load
        filterWarehouses();

        searchBar.addEventListener('input', filterWarehouses);
        filterCity.addEventListener('change', filterWarehouses);
        filterCapacity.addEventListener('change', filterWarehouses);
        filterStatus.addEventListener('change', filterWarehouses);
    </script>
</body>

</html>