<?php
// agent-farmer-dashboard.php - Agent's farmer management dashboard
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login-Signup/login.php");
    // exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$databasename = "direct-edge";

$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = (int)$_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT user_id, full_name, role FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ✅ STRICT ROLE CHECK - Only Agents can access
if (!$user || $user['role'] !== 'Agent') {
    header("Location: ../Login-Signup/profile.php");
    exit();
}

// Get agent_id from agent_info table
$stmt = $conn->prepare("SELECT agent_info_id FROM agent_info WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$agent_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$agent_id = $agent_info['agent_info_id'] ?? 0;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$success = false;

// Handle farmer deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_farmer') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $farmer_id = (int)$_POST['farmer_id'];

        // Verify this farmer belongs to current agent
        $stmt = $conn->prepare("SELECT id FROM farmers WHERE id = ? AND agent_id = ?");
        $stmt->bind_param("ii", $farmer_id, $agent_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM farmers WHERE id = ?");
            $stmt->bind_param("i", $farmer_id);
            if ($stmt->execute()) {
                $success = true;
                $message = "Farmer deleted successfully!";
            } else {
                $message = "Failed to delete farmer!";
            }
            $stmt->close();
        } else {
            $message = "Unauthorized action!";
        }
    }
}

// Filters
$search = $_GET['search'] ?? '';
$farmer_type_filter = $_GET['farmer_type'] ?? 'all';
$land_ownership_filter = $_GET['land_ownership'] ?? 'all';

// Build query
$where_clauses = ["agent_id = $agent_id"];

if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(full_name LIKE '%$search_escaped%' OR contact_number LIKE '%$search_escaped%' OR nid_number LIKE '%$search_escaped%' OR crops_cultivated LIKE '%$search_escaped%')";
}
if ($farmer_type_filter !== 'all') {
    $where_clauses[] = "farmer_type = '" . mysqli_real_escape_string($conn, $farmer_type_filter) . "'";
}
if ($land_ownership_filter !== 'all') {
    $where_clauses[] = "land_ownership = '" . mysqli_real_escape_string($conn, $land_ownership_filter) . "'";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch farmers
$sql = "SELECT * FROM farmers WHERE $where_sql ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$farmers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $farmers[] = $row;
    }
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN farmer_type = 'Small' THEN 1 ELSE 0 END) as small,
    SUM(CASE WHEN farmer_type = 'Medium' THEN 1 ELSE 0 END) as medium,
    SUM(CASE WHEN farmer_type = 'Large' THEN 1 ELSE 0 END) as large,
    SUM(land_size) as total_land,
    AVG(land_size) as avg_land
FROM farmers WHERE agent_id = $agent_id";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

mysqli_close($conn);

function sanitize($v)
{
    return htmlspecialchars($v ?? "", ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Management - Agent Dashboard</title>
    <link rel="icon" type="image/x-icon" href="../Logo/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-600 text-white grid place-items-center font-bold text-lg">🌾</div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">DirectEdge Agent</h1>
                        <p class="text-xs text-gray-500">Farmer Management</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-700 hidden sm:block">👤 <?php echo sanitize($user['full_name']); ?></span>
                    <a href="agent-profile.php" class="text-sm text-blue-600 hover:text-blue-700">Profile</a>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-700 font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">My Farmers</h2>
                    <p class="text-gray-600 mt-1">Manage and track all farmers in your network</p>
                </div>
                <a href="add-farmers-info.php" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium shadow-lg hover:shadow-xl transition-all">
                    ➕ Add New Farmer
                </a>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
            <div class="mb-6 rounded-lg p-4 <?php echo $success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                <p class="<?php echo $success ? 'text-green-800' : 'text-red-800'; ?> text-sm font-medium">
                    <?php echo sanitize($message); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Farmers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['total'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 grid place-items-center text-xl">👥</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Small Farmers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['small'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 grid place-items-center text-xl">🌱</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Land</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['total_land'] ?? 0, 1); ?></p>
                        <p class="text-xs text-gray-500">acres</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 grid place-items-center text-xl">📏</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Avg Land Size</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['avg_land'] ?? 0, 1); ?></p>
                        <p class="text-xs text-gray-500">acres</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 grid place-items-center text-xl">📊</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <form method="GET" class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo sanitize($search); ?>"
                        placeholder="Search by name, phone, NID, or crops..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                </div>
                <select name="farmer_type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="all" <?php echo $farmer_type_filter === 'all' ? 'selected' : ''; ?>>All Farmer Types</option>
                    <option value="Small" <?php echo $farmer_type_filter === 'Small' ? 'selected' : ''; ?>>Small</option>
                    <option value="Medium" <?php echo $farmer_type_filter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="Large" <?php echo $farmer_type_filter === 'Large' ? 'selected' : ''; ?>>Large</option>
                </select>
                <select name="land_ownership" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="all" <?php echo $land_ownership_filter === 'all' ? 'selected' : ''; ?>>All Ownership</option>
                    <option value="Own Land" <?php echo $land_ownership_filter === 'Own Land' ? 'selected' : ''; ?>>Own Land</option>
                    <option value="Leased Land" <?php echo $land_ownership_filter === 'Leased Land' ? 'selected' : ''; ?>>Leased Land</option>
                </select>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filter</button>
                <a href="agent-farmer-dashboard.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium text-center">Clear</a>
            </form>
        </div>

        <!-- Farmers Grid -->
        <?php if (empty($farmers)): ?>
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <div class="text-6xl mb-4">🌾</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No Farmers Found</h3>
                <p class="text-gray-600 mb-6">Start building your farmer network by adding your first farmer</p>
                <a href="add-farmer.php" class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                    ➕ Add Your First Farmer
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($farmers as $farmer): ?>
                    <div class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow overflow-hidden">
                        <!-- Farmer Header -->
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-4">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($farmer['profile_picture'])): ?>
                                    <img src="<?php echo sanitize($farmer['profile_picture']); ?>" alt="Photo" class="w-16 h-16 rounded-full object-cover border-4 border-white">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-full bg-white text-green-600 grid place-items-center font-bold text-xl border-4 border-white">
                                        <?php echo strtoupper(substr($farmer['full_name'], 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <h3 class="text-white font-bold text-lg"><?php echo sanitize($farmer['full_name']); ?></h3>
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-white text-green-700">
                                        <?php echo sanitize($farmer['farmer_type']); ?> Farmer
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Farmer Details -->
                        <div class="p-4 space-y-3">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">📞</span>
                                <span class="text-gray-900"><?php echo sanitize($farmer['contact_number']); ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">🆔</span>
                                <span class="text-gray-900"><?php echo sanitize($farmer['nid_number']); ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">🏡</span>
                                <span class="text-gray-900"><?php echo sanitize($farmer['land_ownership']); ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">📏</span>
                                <span class="text-gray-900 font-medium"><?php echo number_format($farmer['land_size'], 2); ?> acres</span>
                            </div>
                            <div class="pt-2 border-t">
                                <p class="text-xs text-gray-500 mb-1">Crops:</p>
                                <p class="text-sm text-gray-900 font-medium line-clamp-2"><?php echo sanitize($farmer['crops_cultivated']); ?></p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="p-4 bg-gray-50 flex gap-2">
                            <button onclick="viewFarmer(<?php echo htmlspecialchars(json_encode($farmer), ENT_QUOTES); ?>)"
                                class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                                View
                            </button>
                            <a href="edit-farmer.php?id=<?php echo $farmer['id']; ?>"
                                class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium text-center">
                                Edit
                            </a>
                            <form method="POST" onsubmit="return confirm('Delete this farmer? This action cannot be undone!');" class="flex-1">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="delete_farmer">
                                <input type="hidden" name="farmer_id" value="<?php echo $farmer['id']; ?>">
                                <button type="submit" class="w-full px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Farmer Details Modal -->
    <div id="farmerModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Farmer Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="modalContent" class="space-y-4">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function viewFarmer(farmer) {
            const modal = document.getElementById('farmerModal');
            const content = document.getElementById('modalContent');

            content.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3 border-b pb-2">Personal Information</h4>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-600">Full Name:</dt><dd class="font-medium">${farmer.full_name}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Date of Birth:</dt><dd>${farmer.dob || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">NID Number:</dt><dd>${farmer.nid_number}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Contact:</dt><dd>${farmer.contact_number}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Address:</dt><dd>${farmer.present_address || 'N/A'}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3 border-b pb-2">Farm Details</h4>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-600">Farmer Type:</dt><dd><span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">${farmer.farmer_type}</span></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Land Size:</dt><dd class="font-medium">${parseFloat(farmer.land_size).toFixed(2)} acres</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Ownership:</dt><dd>${farmer.land_ownership}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Crops:</dt><dd class="font-medium">${farmer.crops_cultivated}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Fertilizer:</dt><dd>${farmer.fertilizer_usage || 'N/A'}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">Financial Information</h4>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-600">Bank Account:</dt><dd>${farmer.bank_account || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Mobile Banking:</dt><dd>${farmer.mobile_banking_account || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Avg Selling Price:</dt><dd class="font-medium">${farmer.avg_selling_price || 'N/A'}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">Additional Info</h4>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-600">Training:</dt><dd>${farmer.training_received || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600">Registered:</dt><dd>${new Date(farmer.created_at).toLocaleDateString()}</dd></div>
                        </dl>
                    </div>
                </div>

                ${farmer.additional_notes ? `
                    <div class="pt-4 border-t">
                        <h4 class="font-semibold text-gray-900 mb-2">Additional Notes</h4>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">${farmer.additional_notes}</p>
                    </div>
                ` : ''}

                <div class="pt-4 border-t flex gap-3">
                    <a href="edit-farmer.php?id=${farmer.id}" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-center">
                        ✏️ Edit Farmer
                    </a>
                    <button onclick="closeModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">
                        Close
                    </button>
                </div>
            `;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('farmerModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('farmerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>