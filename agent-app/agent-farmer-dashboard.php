<?php include '../Include/SidebarAgent.php'; ?>
<link rel="stylesheet" href="../Include/sidebar.css">

<?php
$agent_id = isset($user_id) ? $user_id : 64;


// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$databasename = "direct-edge";


$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


// Fetch user data
$stmt = $conn->prepare("SELECT user_id, full_name, role FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();


// Get agent_id from agent_info table
$stmt = $conn->prepare("SELECT agent_info_id FROM agent_info WHERE agent_id = ? LIMIT 1");
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


        // Verify this farmer has agreement with current agent
        $stmt = $conn->prepare("SELECT f.id FROM farmers f 
                               INNER JOIN agent_farmer_agreements afa ON f.id = afa.farmer_id 
                               WHERE f.id = ? AND afa.agent_id = ? AND afa.agreement_status = 'Active'");
        $stmt->bind_param("ii", $farmer_id, $user_id);
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


// Build query - ONLY SHOW FARMERS WITH ACTIVE AGREEMENTS
$where_clauses = ["afa.agent_id = $user_id", "afa.agreement_status = 'Active'"];


if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(f.full_name LIKE '%$search_escaped%' OR f.contact_number LIKE '%$search_escaped%' OR f.nid_number LIKE '%$search_escaped%' OR f.crops_cultivated LIKE '%$search_escaped%')";
}
if ($farmer_type_filter !== 'all') {
    $where_clauses[] = "f.farmer_type = '" . mysqli_real_escape_string($conn, $farmer_type_filter) . "'";
}
if ($land_ownership_filter !== 'all') {
    $where_clauses[] = "f.land_ownership = '" . mysqli_real_escape_string($conn, $land_ownership_filter) . "'";
}


$where_sql = implode(" AND ", $where_clauses);


// Fetch farmers with active agreements only
$sql = "SELECT f.*, 
        afa.agreement_reference,
        afa.commission_percentage,
        afa.start_date,
        afa.end_date
        FROM farmers f
        INNER JOIN agent_farmer_agreements afa ON f.id = afa.farmer_id
        WHERE $where_sql 
        ORDER BY f.created_at DESC";
$result = mysqli_query($conn, $sql);
$farmers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $farmers[] = $row;
    }
}


// Get statistics - only farmers with active agreements
$stats_sql = "SELECT 
    COUNT(DISTINCT f.id) as total,
    SUM(CASE WHEN f.farmer_type = 'Small' THEN 1 ELSE 0 END) as small,
    SUM(CASE WHEN f.farmer_type = 'Medium' THEN 1 ELSE 0 END) as medium,
    SUM(CASE WHEN f.farmer_type = 'Large' THEN 1 ELSE 0 END) as large,
    SUM(f.land_size) as total_land,
    AVG(f.land_size) as avg_land
FROM farmers f
INNER JOIN agent_farmer_agreements afa ON f.id = afa.farmer_id
WHERE afa.agent_id = $user_id AND afa.agreement_status = 'Active'";
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
    <!-- Favicon -->
    <script src="https://cdn.tailwindcss.com"></script>


    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">


</head>


<body class="bg-gray-50">


    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">My Farmers</h2>
                    <p class="text-gray-600 mt-1">Farmers with active partnership agreements</p>
                </div>
                <a href="Agreement.php" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium shadow-lg hover:shadow-xl transition-all">
                    <i class="fas fa-plus mr-2"></i>Create Agreement
                </a>
            </div>
        </div>


        <!-- Alert Message -->
        <?php if ($message): ?>
            <div
                class="mb-6 rounded-lg p-4 <?php echo $success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
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
                        <p class="text-xs text-green-600 mt-1">With Active Agreements</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 grid place-items-center text-xl">👥
                    </div>
                </div>
            </div>


            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Small Farmers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['small'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 grid place-items-center text-xl">🌱
                    </div>
                </div>
            </div>


            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Land</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">
                            <?php echo number_format($stats['total_land'] ?? 0, 1); ?></p>
                        <p class="text-xs text-gray-500">acres</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 grid place-items-center text-xl">📏
                    </div>
                </div>
            </div>


            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Avg Land Size</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">
                            <?php echo number_format($stats['avg_land'] ?? 0, 1); ?></p>
                        <p class="text-xs text-gray-500">acres</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 grid place-items-center text-xl">📊
                    </div>
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
                <select name="farmer_type"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="all" <?php echo $farmer_type_filter === 'all' ? 'selected' : ''; ?>>All Farmer Types
                    </option>
                    <option value="Small" <?php echo $farmer_type_filter === 'Small' ? 'selected' : ''; ?>>Small
                    </option>
                    <option value="Medium" <?php echo $farmer_type_filter === 'Medium' ? 'selected' : ''; ?>>Medium
                    </option>
                    <option value="Large" <?php echo $farmer_type_filter === 'Large' ? 'selected' : ''; ?>>Large
                    </option>
                </select>
                <select name="land_ownership"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                    <option value="all" <?php echo $land_ownership_filter === 'all' ? 'selected' : ''; ?>>All Ownership
                    </option>
                    <option value="Own Land" <?php echo $land_ownership_filter === 'Own Land' ? 'selected' : ''; ?>>Own
                        Land</option>
                    <option value="Leased Land"
                        <?php echo $land_ownership_filter === 'Leased Land' ? 'selected' : ''; ?>>Leased Land</option>
                </select>
                <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filter</button>
                <a href="agent-farmer-dashboard.php"
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium text-center">Clear</a>
            </form>
        </div>


        <!-- Farmers Grid -->
        <?php if (empty($farmers)): ?>
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <div class="text-6xl mb-4">🌾</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No Farmers With Active Agreements</h3>
                <p class="text-gray-600 mb-6">Create partnership agreements with farmers to see them here</p>
                <a href="Agreement.php"
                    class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                    <i class="fas fa-handshake mr-2"></i>Create Agreement
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
                                    <img src="<?php echo sanitize($farmer['profile_picture']); ?>" alt="Photo"
                                        class="w-16 h-16 rounded-full object-cover border-4 border-white">
                                <?php else: ?>
                                    <div
                                        class="w-16 h-16 rounded-full bg-white text-green-600 grid place-items-center font-bold text-xl border-4 border-white">
                                        <?php echo strtoupper(substr($farmer['full_name'], 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <h3 class="text-white font-bold text-lg"><?php echo sanitize($farmer['full_name']); ?></h3>
                                    <div class="flex gap-2">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-white text-green-700">
                                            <?php echo sanitize($farmer['farmer_type']); ?> Farmer
                                        </span>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-green-700 text-white">
                                            <i class="fas fa-check-circle mr-1"></i>Active
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Farmer Details -->
                        <div class="p-4 space-y-3">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">📞</span>
                                <span class="text-gray-900"><?php echo sanitize($farmer['contact_number']); ?></span>
                            </div>
                            <!-- <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">🆔</span>
                                <span class="text-gray-900"><?php echo sanitize($farmer['nid_number']); ?></span>
                            </div> -->
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">🏡</span>
                                <span class="text-gray-900"><?php echo sanitize($farmer['land_ownership']); ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">📏</span>
                                <span class="text-gray-900 font-medium"><?php echo number_format($farmer['land_size'], 2); ?>
                                    acres</span>
                            </div>
                            <!-- <div class="pt-2 border-t">
                                <p class="text-xs text-gray-500 mb-1">Crops:</p>
                                <p class="text-sm text-gray-900 font-medium line-clamp-2">
                                    <?php echo sanitize($farmer['crops_cultivated']); ?></p>
                            </div> -->

                            <!-- Agreement Info -->
                            <div class="pt-2 border-t bg-blue-50 -mx-4 px-4 py-2">
                                <!-- <p class="text-xs text-blue-600 mb-1">
                                    <i class="fas fa-handshake mr-1"></i>Agreement: <?php echo sanitize($farmer['agreement_reference']); ?>
                                </p> -->
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Commission: <strong><?php echo number_format($farmer['commission_percentage'], 1); ?>%</strong></span>
                                    <span class="text-gray-600">Until: <strong><?php echo date('M d, Y', strtotime($farmer['end_date'])); ?></strong></span>
                                </div>
                            </div>
                        </div>


                        <!-- Action Buttons -->
                        <div class="p-4 bg-gray-50 flex gap-2">
                            <button onclick="viewFarmer(<?php echo htmlspecialchars(json_encode($farmer), ENT_QUOTES); ?>)"
                                class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                                <i class="fas fa-eye mr-1"></i>View
                            </button>
                            <a href="view_agreement.php?ref=<?php echo urlencode($farmer['agreement_reference']); ?>"
                                class="flex-1 px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium text-center" target="_blank">
                                <i class="fas fa-file-contract mr-1"></i>Agreement
                            </a>
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
                <!-- Agreement Status Banner -->
                <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-800">Active Partnership Agreement</p>
                            <p class="text-sm text-green-700">Reference: ${farmer.agreement_reference}</p>
                            <p class="text-xs text-green-600">Commission: ${parseFloat(farmer.commission_percentage).toFixed(1)}% • Valid until: ${new Date(farmer.end_date).toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>

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
                    <a href="view_agreement.php?ref=${encodeURIComponent(farmer.agreement_reference)}" target="_blank" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium text-center">
                        <i class="fas fa-file-contract mr-2"></i>View Agreement
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


    <?php include '../include/footer.php'; ?>


</body>


</html>