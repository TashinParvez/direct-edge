<?php
// admin-agent-management.php - Admin panel to manage all agents (FIXED VERSION)
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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

// Fetch admin user data
$stmt = $conn->prepare("SELECT user_id, full_name, role FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ✅ STRICT ROLE CHECK - Only Admin can access
if (!$admin || $admin['role'] !== 'Admin') {
    header("Location: profile.php");
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$success = false;

// Handle agent status update (Approve/Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $agent_info_id = (int)$_POST['agent_info_id'];
        $new_status = $_POST['status'];

        if (in_array($new_status, ['Approved', 'Rejected', 'Pending'])) {
            $stmt = $conn->prepare("UPDATE agent_info SET status=?, updated_at=NOW() WHERE agent_info_id=?");
            $stmt->bind_param("si", $new_status, $agent_info_id);

            if ($stmt->execute()) {
                $success = true;
                $message = "Agent status updated to: $new_status";
            } else {
                $message = "Failed to update status!";
            }
            $stmt->close();
        }
    }
}

// Handle agent removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'remove_agent') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $agent_user_id = (int)$_POST['agent_user_id'];

        mysqli_begin_transaction($conn);
        try {
            // Delete agent_info record
            $stmt = $conn->prepare("DELETE FROM agent_info WHERE user_id=?");
            $stmt->bind_param("i", $agent_user_id);
            $stmt->execute();
            $stmt->close();

            // Change user role back to User
            $stmt = $conn->prepare("UPDATE users SET role='User', updated_at=NOW() WHERE user_id=?");
            $stmt->bind_param("i", $agent_user_id);
            $stmt->execute();
            $stmt->close();

            mysqli_commit($conn);
            $success = true;
            $message = "Agent removed successfully and role changed to User!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "Failed to remove agent: " . $e->getMessage();
        }
    }
}

// Handle complete account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $agent_user_id = (int)$_POST['agent_user_id'];

        mysqli_begin_transaction($conn);
        try {
            // Delete agent_info first
            $stmt = $conn->prepare("DELETE FROM agent_info WHERE user_id=?");
            $stmt->bind_param("i", $agent_user_id);
            $stmt->execute();
            $stmt->close();

            // Delete user account
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
            $stmt->bind_param("i", $agent_user_id);
            $stmt->execute();
            $stmt->close();

            mysqli_commit($conn);
            $success = true;
            $message = "Agent account permanently deleted!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "Failed to delete account: " . $e->getMessage();
        }
    }
}

// Fetch filter
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["u.role = 'Agent'"];
if ($status_filter !== 'all') {
    $where_clauses[] = "ai.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(u.full_name LIKE '%$search_escaped%' OR u.email LIKE '%$search_escaped%' OR u.phone LIKE '%$search_escaped%' OR ai.region LIKE '%$search_escaped%')";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch all agents with their info (removed reviewed_at and reviewer columns)
$sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.image_url, u.created_at,
               ai.agent_info_id, ai.nid_number, ai.region, ai.district, ai.upazila, 
               ai.coverage_area_km, ai.experience_years, ai.crops_expertise, 
               ai.vehicle_types, ai.warehouse_capacity, ai.status
        FROM users u
        LEFT JOIN agent_info ai ON u.user_id = ai.user_id
        WHERE $where_sql
        ORDER BY ai.created_at DESC";

$result = mysqli_query($conn, $sql);
$agents = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $agents[] = $row;
    }
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN ai.status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN ai.status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN ai.status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM users u
LEFT JOIN agent_info ai ON u.user_id = ai.user_id
WHERE u.role = 'Agent'";
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
    <title>Agent Management - Admin Panel</title>
    <link rel="icon" type="image/x-icon" href="../Logo/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-600 text-white grid place-items-center font-bold">🔧</div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">DirectEdge Admin</h1>
                        <p class="text-xs text-gray-500">Agent Management</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-700">👤 <?php echo sanitize($admin['full_name']); ?></span>
                    <a href="profile.php" class="text-sm text-blue-600 hover:text-blue-700">Dashboard</a>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-700 font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Agent Management</h2>
            <p class="text-gray-600 mt-1">View, approve, reject, and manage all agricultural agents</p>
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
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Agents</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['total'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 grid place-items-center text-xl">👥</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['pending'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 grid place-items-center text-xl">⏳</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Approved</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['approved'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 grid place-items-center text-xl">✅</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Rejected</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['rejected'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 grid place-items-center text-xl">❌</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo sanitize($search); ?>"
                        placeholder="Search by name, email, phone, or region..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Filter</button>
                <a href="admin-agent-management.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium text-center">Clear</a>
            </form>
        </div>

        <!-- Agents Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($agents)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="text-4xl">📭</span>
                                        <p class="text-sm">No agents found matching your criteria</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($agents as $agent): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($agent['image_url'])): ?>
                                                <img src="<?php echo sanitize($agent['image_url']); ?>" alt="Photo" class="w-10 h-10 rounded-full object-cover">
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded-full bg-green-100 text-green-700 grid place-items-center font-bold text-sm">
                                                    <?php echo strtoupper(substr($agent['full_name'], 0, 2)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900"><?php echo sanitize($agent['full_name']); ?></p>
                                                <p class="text-xs text-gray-500">ID: <?php echo $agent['user_id']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-900"><?php echo sanitize($agent['email']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo sanitize($agent['phone']); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-900"><?php echo sanitize($agent['region']); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php if ($agent['district']): ?>
                                                <?php echo sanitize($agent['district']); ?>
                                                <?php if ($agent['upazila']): ?>, <?php echo sanitize($agent['upazila']); ?><?php endif; ?>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-xs text-gray-600">🌾 <?php echo sanitize($agent['crops_expertise']); ?></p>
                                        <p class="text-xs text-gray-600 mt-1">📏 <?php echo sanitize($agent['coverage_area_km']); ?> km</p>
                                        <p class="text-xs text-gray-600">⏱️ <?php echo sanitize($agent['experience_years']); ?> yrs</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        echo match ($agent['status']) {
                                            'Approved' => 'bg-green-100 text-green-800',
                                            'Rejected' => 'bg-red-100 text-red-800',
                                            default => 'bg-yellow-100 text-yellow-800'
                                        };
                                        ?>">
                                            <?php echo sanitize($agent['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="openModal(<?php echo htmlspecialchars(json_encode($agent), ENT_QUOTES); ?>)"
                                            class="text-blue-600 hover:text-blue-900 font-medium">View/Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div id="agentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Agent Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>

            <div id="modalContent" class="space-y-6">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function openModal(agent) {
            const modal = document.getElementById('agentModal');
            const content = document.getElementById('modalContent');

            content.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Personal Information</h4>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-600">Name:</dt><dd class="font-medium">${agent.full_name}</dd></div>
                            <div><dt class="text-gray-600">Email:</dt><dd>${agent.email}</dd></div>
                            <div><dt class="text-gray-600">Phone:</dt><dd>${agent.phone}</dd></div>
                            <div><dt class="text-gray-600">NID:</dt><dd>${agent.nid_number}</dd></div>
                            <div><dt class="text-gray-600">Joined:</dt><dd>${new Date(agent.created_at).toLocaleDateString()}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Professional Details</h4>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-600">Region:</dt><dd>${agent.region}${agent.district ? ', ' + agent.district : ''}${agent.upazila ? ', ' + agent.upazila : ''}</dd></div>
                            <div><dt class="text-gray-600">Coverage:</dt><dd>${agent.coverage_area_km} km</dd></div>
                            <div><dt class="text-gray-600">Experience:</dt><dd>${agent.experience_years} years</dd></div>
                            <div><dt class="text-gray-600">Crops:</dt><dd>${agent.crops_expertise}</dd></div>
                            <div><dt class="text-gray-600">Vehicles:</dt><dd>${agent.vehicle_types || 'N/A'}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Update Status</h4>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="agent_info_id" value="${agent.agent_info_id}">

                        <div class="flex gap-4">
                            <select name="status" required class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="Pending" ${agent.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Approved" ${agent.status === 'Approved' ? 'selected' : ''}>Approved</option>
                                <option value="Rejected" ${agent.status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                            </select>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t pt-4 flex gap-3">
                    <form method="POST" onsubmit="return confirm('Remove agent role? User account will remain but role will change to User.');" class="flex-1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="remove_agent">
                        <input type="hidden" name="agent_user_id" value="${agent.user_id}">
                        <button type="submit" class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-medium">
                            🔄 Remove Agent Role
                        </button>
                    </form>

                    <form method="POST" onsubmit="return confirm('PERMANENTLY DELETE this account? This action cannot be undone!');" class="flex-1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="agent_user_id" value="${agent.user_id}">
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                            🗑️ Delete Account
                        </button>
                    </form>
                </div>
            `;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('agentModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('agentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>