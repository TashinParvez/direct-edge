<?php
// Connect database
include '../include/connect-db.php'; // Database connection

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE full_name LIKE ? OR contact_number LIKE ? OR crops_cultivated LIKE ?";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term];
}

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM farmers $search_condition";
$count_stmt = $conn->prepare($count_sql);
if (!empty($search_params)) {
    $count_stmt->bind_param("sss", ...$search_params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Check if created_at column exists
$columns_query = "SHOW COLUMNS FROM farmers LIKE 'created_at'";
$has_created_at = $conn->query($columns_query)->num_rows > 0;

// Get farmers data - adjust query based on column existence
$select_columns = "id, full_name, contact_number, farmer_type, crops_cultivated, land_size, profile_picture";
$order_by = "ORDER BY id DESC"; // Default ordering by id

if ($has_created_at) {
    $select_columns .= ", created_at";
    $order_by = "ORDER BY created_at DESC";
}

$sql = "SELECT $select_columns 
        FROM farmers $search_condition 
        $order_by 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($search_params)) {
    $stmt->bind_param("ssii", ...[...$search_params, $records_per_page, $offset]);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="px-8 md:px-16 lg:px-24 xl:px-32 2xl:px-40">
        <div class="max-w-6xl mx-auto px-12 py-4">

            <!-- Search Bar and Add Button - Horizontal Layout -->
            <div class="bg-white rounded-lg shadow-sm p-3 mb-4">
                <form method="GET" class="flex items-center gap-3">
                    <div class="flex-grow">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Search farmers..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-green-500 focus:outline-none text-sm">
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="farmers-list.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition text-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                    <a href="add-farmers-info.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                        <i class="fas fa-plus mr-1"></i>Add Farmer
                    </a>
                </form>
            </div>

            <!-- Farmers Grid -->
            <?php if (empty($farmers)): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No farmers found</h3>
                    <p class="text-gray-500 mb-4">
                        <?php echo !empty($search) ? 'Try adjusting your search terms.' : 'Start by adding your first farmer.'; ?>
                    </p>
                    <a href="add-farmers-info.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-plus mr-2"></i>Add First Farmer
                    </a>
                </div>
            <?php else: ?>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($farmers as $farmer): ?>
                        <!-- Clickable Card -->
                        <a href="farmer-profile.php?id=<?php echo $farmer['id']; ?>" 
                           class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 p-4 block cursor-pointer transform hover:scale-105">

                            <!-- Profile Picture -->
                            <div class="text-center mb-3">
                                <?php if ($farmer['profile_picture'] && file_exists($farmer['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($farmer['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($farmer['full_name']); ?>" 
                                         class="w-16 h-16 rounded-full mx-auto object-cover border border-green-200">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-green-200 rounded-full mx-auto flex items-center justify-center">
                                        <i class="fas fa-user text-lg text-green-600"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Farmer Info -->
                            <div class="text-center">
                                <h3 class="text-base font-semibold text-gray-800 mb-3">
                                    <?php echo htmlspecialchars($farmer['full_name']); ?>
                                </h3>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <p class="flex items-center justify-center">
                                        <i class="fas fa-phone mr-2 text-green-600"></i>
                                        <?php echo htmlspecialchars($farmer['contact_number']); ?>
                                    </p>
                                    <p class="flex items-center justify-center">
                                        <i class="fas fa-tag mr-2 text-green-600"></i>
                                        <?php echo htmlspecialchars($farmer['farmer_type']); ?> Farmer
                                    </p>
                                    <?php if ($farmer['land_size']): ?>
                                        <p class="flex items-center justify-center">
                                            <i class="fas fa-map mr-2 text-green-600"></i>
                                            <?php echo $farmer['land_size']; ?> acres
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-6 flex justify-center">
                        <nav class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo ($page-1); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 border rounded text-sm <?php echo ($i == $page) ? 'bg-green-600 text-white border-green-600' : 'bg-white border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo ($page+1); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>

                    <div class="mt-4 text-center text-sm text-gray-600">
                        Showing <?php echo (($page-1) * $records_per_page + 1); ?> to 
                        <?php echo min($page * $records_per_page, $total_records); ?> of 
                        <?php echo $total_records; ?> farmers
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</body>
</html>