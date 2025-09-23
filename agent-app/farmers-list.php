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

// Get farmers data
$sql = "SELECT id, full_name, contact_number, farmer_type, crops_cultivated, land_size, profile_picture, created_at 
        FROM farmers $search_condition 
        ORDER BY created_at DESC 
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
    <!-- Header -->
    <header class="bg-green-600 text-white p-6 shadow-lg">
        <div class="container mx-auto flex items-center justify-between">
            <h1 class="text-2xl font-bold flex items-center">
                <i class="fas fa-users mr-3"></i>
                Farmers Directory
            </h1>
            <div class="flex items-center space-x-4">
                <span class="text-green-100">Total: <?php echo $total_records; ?> farmers</span>
                <a href="add-farmers-info.php" class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition font-semibold">
                    <i class="fas fa-plus mr-2"></i>Add New Farmer
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">

        <!-- Search Bar -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by name, phone, or crops..." 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="farmers-list.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    <?php endif; ?>
                </div>
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($farmers as $farmer): ?>
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                        <!-- Profile Picture -->
                        <div class="text-center mb-4">
                            <?php if ($farmer['profile_picture'] && file_exists($farmer['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($farmer['profile_picture']); ?>" 
                                     alt="<?php echo htmlspecialchars($farmer['full_name']); ?>" 
                                     class="w-20 h-20 rounded-full mx-auto object-cover border-2 border-green-200">
                            <?php else: ?>
                                <div class="w-20 h-20 bg-green-200 rounded-full mx-auto flex items-center justify-center">
                                    <i class="fas fa-user text-2xl text-green-600"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Farmer Info -->
                        <div class="text-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($farmer['full_name']); ?>
                            </h3>
                            <div class="space-y-1 text-sm text-gray-600">
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
                                        <?php echo $farmer['land_size']; ?> acres/bigha
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Crops -->
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Crops:</p>
                            <p class="text-sm text-gray-800 bg-gray-50 p-2 rounded">
                                <?php echo $farmer['crops_cultivated'] ? htmlspecialchars($farmer['crops_cultivated']) : 'Not specified'; ?>
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <a href="farmer-profile.php?id=<?php echo $farmer['id']; ?>" 
                               class="flex-1 bg-green-600 text-white text-center py-2 px-3 rounded-lg hover:bg-green-700 transition text-sm">
                                <i class="fas fa-eye mr-1"></i>View Profile
                            </a>
                            <a href="edit-farmer.php?id=<?php echo $farmer['id']; ?>" 
                               class="flex-1 bg-blue-600 text-white text-center py-2 px-3 rounded-lg hover:bg-blue-700 transition text-sm">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                        </div>

                        <!-- Registration Date -->
                        <div class="mt-3 text-xs text-gray-500 text-center">
                            Added: <?php echo isset($farmer['created_at']) ? date('d M Y', strtotime($farmer['created_at'])) : 'Unknown'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                               class="px-3 py-2 border rounded-lg transition
                                      <?php echo ($i == $page) ? 'bg-green-600 text-white border-green-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Page Info -->
                <div class="mt-4 text-center text-sm text-gray-600">
                    Showing <?php echo (($page-1) * $records_per_page + 1); ?> to 
                    <?php echo min($page * $records_per_page, $total_records); ?> of 
                    <?php echo $total_records; ?> farmers
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>