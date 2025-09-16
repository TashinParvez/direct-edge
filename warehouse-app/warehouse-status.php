<?php
include '../include/connect-db.php'; // database connection



$sql = "SELECT warehouse_id, name,location, capacity_total,capacity_used, status,type
        FROM `warehouses` WHERE 1";

$result = mysqli_query($conn, $sql);
// $allwarehouses = mysqli_fetch_all($result);
$allwarehouses = mysqli_fetch_all($result, MYSQLI_ASSOC);



// print_r($allwarehouses);  // print all
// print_r($allwarehouses[0]);  // single print
// print_r($allwarehouses[0][0]);  // single item


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-gray-100 flex">

    <!-- Sidebar -->
    <?php
    // include '../include/Sidebar.php';
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

            <select id="filterCity" class="p-2 border border-gray-300 rounded-md shadow-sm">
                <option value="" hidden>Filter by City/Location</option>
                <option value="All">All</option>
                <option value="Dhaka">Dhaka</option>
                <option value="Chattogram">Chattogram</option
                    <option value="Khulna">Khulna</option>
            </select>

            <select id="filterCapacity" class="p-2 border border-gray-300 rounded-md shadow-sm">
                <option value="" hidden>Filter by Capacity</option>
                <option value="All">All</option>
                <option>0 - 1000 sq ft</option>
                <option>1001 - 5000 sq ft</option>
                <option>5001+ sq ft</option>
            </select>
            <select id="filterStatus" class="p-2 border border-gray-300 rounded-md shadow-sm">
                <option value="" hidden>Filter by Status</option>
                <option value="All">All</option>
                <option>Available</option>
                <option>Occupied</option>
                <option>Maintenance</option>
            </select>
        </div>



        <!-- Warehouse Cards -->
        <div id="warehouseCards" class="space-y-4">



            <?php foreach ($allwarehouses as $warehouse): ?>
                <div onclick="window.location.href='warehouse-info.php?id=<?= $warehouse['warehouse_id'] ?>'"
                    class="card bg-white rounded-lg shadow-md p-5 transition hover:shadow-lg">
                    <h5 class="text-lg font-semibold text-gray-800"><?= $warehouse['name'] ?></h5>
                    <p class="text-gray-600"><strong>Location:</strong> <?= $warehouse['location'] ?></p>
                    <p class="text-gray-600"><strong>Capacity:</strong> <?= $warehouse['capacity_total'] ?> sq ft</p>
                    <p class="text-gray-600"><strong>Status:</strong> <?= $warehouse['status'] ?></p>
                    <p class="text-gray-600"><strong>Type:</strong> <?= $warehouse['type'] ?></p>
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

        function filterWarehouses() {
            const search = searchBar.value.toLowerCase();
            const city = filterCity.value.toLowerCase();
            const capacity = filterCapacity.value.toLowerCase();
            const status = filterStatus.value.toLowerCase();

            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                let show = true;

                if (search && !text.includes(search)) show = false;
                if (city && !text.includes(city)) show = false;
                if (capacity && !text.includes(capacity.split(" ")[0])) show = false;
                if (status && !text.includes(status)) show = false;

                card.style.display = show ? "" : "none";
            });
        }

        searchBar.addEventListener('input', filterWarehouses);
        filterCity.addEventListener('change', filterWarehouses);
        filterCapacity.addEventListener('change', filterWarehouses);
        filterStatus.addEventListener('change', filterWarehouses);
    </script>
</body>

</html>