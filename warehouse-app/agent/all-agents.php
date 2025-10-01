<?php


include '../../include/connect-db.php';
// include '../../assets/user-image/IMG_0530.jpg';

// ----------- Placeholder sample data -----------
$search = $_GET['search'] ?? '';
$area_filter = $_GET['area'] ?? '';
$warehouse_filter = $_GET['warehouse'] ?? '';
$farmer_count_filter = $_GET['farmer_count'] ?? '';

$areas = ['Dhaka', 'Chittagong', 'Sylhet', 'Khulna'];
$warehouses = ['Dhaka WH', 'Chittagong WH', 'Sylhet WH'];




// ===============  agents data ======================


$sql = "SELECT 
            u.user_id AS id,
            u.full_name AS name,
            u.email,
            u.phone,
            c.name AS area,
            w.name AS warehouse,
            u.image_url AS image
        FROM users u

        JOIN agent_assigned_cities aac ON u.user_id = aac.agent_id
        JOIN cities c ON aac.city_id = c.city_id
        JOIN warehouses w ON w.warehouse_id = aac.city_id  -- assuming each city has a warehouse; adjust if needed
        LEFT JOIN stock_requests sr ON sr.requester_id = u.user_id
        WHERE u.role = 'Agent'
        GROUP BY u.user_id, u.full_name, u.email, u.phone, c.name, w.name, u.image_url;";


$result = mysqli_query($conn, $sql);
$agents = [];

while ($row = mysqli_fetch_assoc($result)) {
    $agents[] = [
        'id'        => $row['id'],
        'name'      => $row['name'],
        'email'     => $row['email'],
        'phone'     => $row['phone'],
        'area'      => $row['area'],
        'warehouse' => $row['warehouse'],
        'image'     => $row['image']
    ];
}

// print_r($agents);



// ==========================================================================================================================================================
// ==========================================================================================================================================================
// ==========================================================================================================================================================
// ==========================================================================================================================================================


// ----------- Filter & Search Logic (frontend placeholder) -----------

$agents_filtered = array_filter($agents, function ($a) use ($search, $area_filter, $warehouse_filter, $farmer_count_filter) {
    $ok = true;
    if ($search) {
        $ok = $ok && (stripos($a['name'], $search) !== false || stripos($a['email'], $search) !== false);
    }
    if ($area_filter) {
        $ok = $ok && ($a['area'] === $area_filter);
    }
    if ($warehouse_filter) {
        $ok = $ok && ($a['warehouse'] === $warehouse_filter);
    }
    if ($farmer_count_filter) {
        $ok = $ok && ($a['farmers'] >= (int)$farmer_count_filter);
    }
    return $ok;
});
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Agents</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 p-6">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <header class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">All Agents</h1>
        </header>

        <!-- Search & Filters -->
        <form method="GET" class="flex flex-col md:flex-row gap-3 mb-6">
            <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>" class="border p-2 rounded w-full md:w-1/3 focus:ring focus:ring-blue-200">

            <select name="area" class="border p-2 rounded w-full md:w-1/5">
                <option value="">All Areas</option>
                <?php foreach ($areas as $a): ?>
                    <option value="<?= $a ?>" <?= $area_filter === $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>

            <select name="warehouse" class="border p-2 rounded w-full md:w-1/5">
                <option value="">All Warehouses</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w ?>" <?= $warehouse_filter === $w ? 'selected' : '' ?>><?= $w ?></option>
                <?php endforeach; ?>
            </select>

            <select name="farmer_count" class="border p-2 rounded w-full md:w-1/5">
                <option value="">Min Farmers Added</option>
                <option value="5" <?= $farmer_count_filter === '5' ? 'selected' : '' ?>>5+</option>
                <option value="10" <?= $farmer_count_filter === '10' ? 'selected' : '' ?>>10+</option>
                <option value="15" <?= $farmer_count_filter === '15' ? 'selected' : '' ?>>15+</option>
                <option value="20" <?= $farmer_count_filter === '20' ? 'selected' : '' ?>>20+</option>
            </select>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
        </form>




        <!--  ======================  Agents Cards Grid   ====================== -->


        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">


            <?php if (count($agents_filtered) === 0): ?>
                <div class="col-span-full text-center text-gray-500 py-10">No agents found</div>
            <?php endif; ?>


            <?php foreach ($agents_filtered as $agent): ?>
                <a href="one-agent-info.php?id=<?= $agent['id'] ?>" class="bg-white rounded-lg shadow hover:shadow-lg overflow-hidden transition duration-200">
                    <img src="<?= $agent['image'] ?>" alt="<?= htmlspecialchars($agent['name']) ?>" class="w-full h-40 object-cover">
                    <div class="p-4">
                        <h2 class="font-bold text-lg"><?= htmlspecialchars($agent['name']) ?></h2>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($agent['email']) ?></p>
                        <p class="text-sm text-gray-500">Phone: <?= htmlspecialchars($agent['phone']) ?></p>
                        <p class="text-sm text-gray-500">Area: <?= htmlspecialchars($agent['area']) ?></p>
                        <p class="text-sm text-gray-500">Warehouse: <?= htmlspecialchars($agent['warehouse']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>


            
        </div>

    </div>
</body>

</html>