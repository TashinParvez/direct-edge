<?php include '../Include/SidebarAgent.php'; ?>
<link rel="stylesheet" href="../Include/sidebar.css">
<?php

$agent_id = isset($user_id) ? $user_id : 45;

// Connect database
include '../include/connect-db.php'; // Database connection

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality (updated to match dashboard: include nid_number)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$search_params = [];

// Base WHERE condition for agent
$where_clause = "WHERE agent_id = ?";
$base_params = [$agent_id];

// Add search filter if needed
if (!empty($search)) {
    $search_condition = " AND (full_name LIKE ? OR contact_number LIKE ? OR nid_number LIKE ? OR crops_cultivated LIKE ?)";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term, $search_term];
}

// Combine conditions
$final_where = $where_clause . $search_condition;
$final_params = array_merge($base_params, $search_params);

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM farmers $final_where";
$count_stmt = $conn->prepare($count_sql);

// Bind parameters dynamically
$count_types = str_repeat('s', count($final_params)); // all strings fine, agent_id will cast automatically
$count_stmt->bind_param($count_types, ...$final_params);

$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Check if created_at column exists
$columns_query = "SHOW COLUMNS FROM farmers LIKE 'created_at'";
$has_created_at = $conn->query($columns_query)->num_rows > 0;

// Get farmers data
$select_columns = "id, full_name, contact_number, farmer_type, crops_cultivated, land_size, profile_picture";
$order_by = "ORDER BY id DESC"; // Default ordering by id

if ($has_created_at) {
    $select_columns .= ", created_at";
    $order_by = "ORDER BY created_at DESC";
}

$sql = "SELECT $select_columns 
        FROM farmers 
        $final_where 
        $order_by 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Add pagination parameters
$data_params = array_merge($final_params, [$records_per_page, $offset]);
$data_types = str_repeat('s', count($final_params)) . 'ii'; // agent_id + search terms + limit + offset

$stmt->bind_param($data_types, ...$data_params);

$stmt->execute();
$result = $stmt->get_result();
$farmers = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Farmers Directory - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .farmer-card {
            transition: all 0.3s ease;
        }

        .farmer-card:hover {
            background-color: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .search-container {
            transition: all 0.3s ease;
        }

        .search-container:hover {
            background-color: #f9fafb;
        }

        .profile-image {
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.1);
        }

        .pagination-btn {
            transition: all 0.2s ease;
        }

        .pagination-btn:hover {
            transform: translateY(-1px);
        }

        .empty-state {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .farmer-grid {
            animation: staggerIn 0.6s ease-out;
        }

        @keyframes staggerIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>

<body class="bg-gray-100">

    <section class="home-section p-0 pb-4 mx-2 md:mx-8 lg:mx-16">
        <div class="flex justify-between items-center p-4">
            <div class="flex-1">
                <h1 class="text-2xl font-bold">Farmers Directory</h1>
                <p class="text-xs text-gray-500 mt-1">View farmers' profiles, contacts, land, and crops. Search to find easily.</p>
            </div>
            <a href="add-farmers-info.php" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                <i class='bx bx-plus'></i> Add Farmer
            </a>
        </div>

        <div class="container mx-auto px-4">

            <!-- Search Bar Container -->
            <div class="bg-white shadow-md rounded-lg p-4 mb-6 search-container">
                <form method="GET" class="flex items-center gap-3">
                    <div class="flex-grow">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search farmers by name, phone, NID, or crops..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition-colors">
                        <i class='bx bx-search'></i>
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="farmers-list.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
                            <i class='bx bx-x'></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Results Summary -->
            <?php if (!empty($search)): ?>
                <div class="mb-4 text-sm text-gray-600">
                    <?php echo $total_records; ?> farmer<?php echo $total_records != 1 ? 's' : ''; ?> found for "<?php echo htmlspecialchars($search); ?>"
                </div>
            <?php endif; ?>

            <!-- Farmers Grid or Empty State -->
            <?php if (empty($farmers)): ?>
                <div class="bg-white shadow-lg rounded-lg p-8 text-center empty-state">
                    <i class='bx bx-group text-gray-300 text-6xl mb-4'></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No farmers found</h3>
                    <p class="text-gray-500 mb-6">
                        <?php echo !empty($search) ? 'Try adjusting your search terms or browse all farmers.' : 'Start by adding your first farmer to the directory.'; ?>
                    </p>
                    <div class="flex justify-center space-x-3">
                        <a href="add-farmers-info.php" class="bg-green-500 text-white px-6 py-3 rounded hover:bg-green-600 transition-colors">
                            <i class='bx bx-plus mr-2'></i>Add First Farmer
                        </a>
                        <?php if (!empty($search)): ?>
                            <a href="farmers-list.php" class="bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600 transition-colors">
                                <i class='bx bx-list-ul mr-2'></i>View All Farmers
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 farmer-grid">
                    <?php foreach ($farmers as $farmer): ?>
                        <!-- Clickable Farmer Card -->
                        <a href="farmer-profile.php?id=<?php echo $farmer['id']; ?>"
                            class="bg-white rounded-lg shadow-md hover:shadow-lg p-6 block cursor-pointer farmer-card">

                            <!-- Profile Picture -->
                            <div class="text-center mb-4">
                                <?php if ($farmer['profile_picture'] && file_exists($farmer['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($farmer['profile_picture']); ?>"
                                        alt="<?php echo htmlspecialchars($farmer['full_name']); ?>"
                                        class="w-16 h-16 rounded-full mx-auto object-cover border-2 border-green-200 profile-image">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-green-200 rounded-full mx-auto flex items-center justify-center profile-image">
                                        <i class='bx bx-user text-2xl text-green-600'></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Farmer Information -->
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-gray-800 mb-3">
                                    <?php echo htmlspecialchars($farmer['full_name']); ?>
                                </h3>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <p class="flex items-center justify-center">
                                        <i class='bx bx-phone mr-2 text-green-600'></i>
                                        <?php echo htmlspecialchars($farmer['contact_number']); ?>
                                    </p>

                                    <p class="flex items-center justify-center">
                                        <i class='bx bx-category mr-2 text-green-600'></i>
                                        <?php echo htmlspecialchars($farmer['farmer_type']); ?> Farmer
                                    </p>

                                    <?php if ($farmer['land_size']): ?>
                                        <p class="flex items-center justify-center">
                                            <i class='bx bx-map mr-2 text-green-600'></i>
                                            <?php echo $farmer['land_size']; ?> acres/bigha
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($farmer['crops_cultivated']): ?>
                                        <p class="flex items-center justify-center text-xs">
                                            <i class='bx bx-leaf mr-2 text-green-600'></i>
                                            <?php echo htmlspecialchars(substr($farmer['crops_cultivated'], 0, 20)) . (strlen($farmer['crops_cultivated']) > 20 ? '...' : ''); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Registration Date if available -->
                                <?php if ($has_created_at && isset($farmer['created_at'])): ?>
                                    <div class="mt-3 pt-3 border-t border-gray-100">
                                        <p class="text-xs text-gray-400 flex items-center justify-center">
                                            <i class='bx bx-calendar mr-1'></i>
                                            Joined <?php echo date('M Y', strtotime($farmer['created_at'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center">
                        <nav class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-3 py-2 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 pagination-btn">
                                    <i class='bx bx-chevron-left'></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-3 py-2 border rounded text-sm pagination-btn <?php echo ($i == $page) ? 'bg-green-500 text-white border-green-500' : 'bg-white border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-3 py-2 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 pagination-btn">
                                    <i class='bx bx-chevron-right'></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>

                    <div class="mt-4 text-center text-sm text-gray-600">
                        Showing <?php echo (($page - 1) * $records_per_page + 1); ?> to
                        <?php echo min($page * $records_per_page, $total_records); ?> of
                        <?php echo $total_records; ?> farmer<?php echo $total_records != 1 ? 's' : ''; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </section>




    <?php include '../Include/footer.php'; ?>
</body>

</html>