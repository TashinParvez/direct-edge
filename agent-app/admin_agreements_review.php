<?php
ob_start();
include '../include/navbar.php';
include '../include/connect-db.php';

$admin_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $agreement_id = intval($_POST['agreement_id']);
    $new_status = $_POST['new_status'];
    $admin_notes = $_POST['admin_notes'] ?? '';

    $update_query = "UPDATE agent_farmer_agreements 
                     SET agreement_status = ?, 
                         updated_at = NOW() 
                     WHERE agreement_id = ?";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $agreement_id);

    if ($stmt->execute()) {
        // Log the action
        $log_query = "INSERT INTO activity_logs (admin_id, action, description, created_at) 
                      VALUES (?, 'Agreement Status Update', ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_desc = "Updated agreement ID {$agreement_id} to status: {$new_status}. Notes: {$admin_notes}";
        $log_stmt->bind_param("is", $admin_id, $log_desc);
        $log_stmt->execute();

        $success_message = "Agreement status updated successfully!";
    } else {
        $error_message = "Failed to update agreement status.";
    }
}

// Fetch filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$query = "SELECT afa.*, 
          f.full_name as farmer_name, 
          f.contact_number as farmer_contact,
          f.land_size as farmer_land_size,
          f.crops_cultivated as farmer_crops,
          u.full_name as agent_name,
          u.phone as agent_phone,
          ai.district as agent_district,
          ai.region as agent_region
          FROM agent_farmer_agreements afa
          JOIN farmers f ON afa.farmer_id = f.id
          JOIN users u ON afa.agent_id = u.user_id
          JOIN agent_info ai ON u.user_id = ai.agent_id
          WHERE 1=1";

if ($status_filter) {
    $query .= " AND afa.agreement_status = ?";
}

if ($search) {
    $query .= " AND (afa.agreement_reference LIKE ? OR f.full_name LIKE ? OR u.full_name LIKE ?)";
}

$query .= " ORDER BY 
            CASE 
                WHEN afa.agreement_status = 'Pending' THEN 1
                WHEN afa.agreement_status = 'Active' THEN 2
                ELSE 3
            END,
            afa.created_at DESC";

$stmt = $conn->prepare($query);

// Bind parameters
if ($status_filter && $search) {
    $search_param = "%{$search}%";
    $stmt->bind_param("ssss", $status_filter, $search_param, $search_param, $search_param);
} elseif ($status_filter) {
    $stmt->bind_param("s", $status_filter);
} elseif ($search) {
    $search_param = "%{$search}%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();
$agreements = $result->fetch_all(MYSQLI_ASSOC);

// Count by status
$status_query = "SELECT agreement_status, COUNT(*) as count FROM agent_farmer_agreements GROUP BY agreement_status";
$status_result = $conn->query($status_query);
$status_counts = [
    'Pending' => 0,
    'Active' => 0,
    'Expired' => 0,
    'Terminated' => 0
];
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['agreement_status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agreement Review - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg flex-shrink-0">
            <div class="p-6 border-b bg-gradient-to-r from-blue-600 to-blue-700">
                <h2 class="text-white font-bold text-lg flex items-center">
                    <i class="fas fa-handshake mr-2"></i>
                    Agreements
                </h2>
            </div>

            <!-- Stats in Sidebar -->
            <div class="p-4 space-y-3">
                <a href="?status=" class="block p-3 rounded-lg hover:bg-blue-50 transition <?php echo $status_filter === '' ? 'bg-blue-50 border-l-4 border-blue-600' : 'bg-gray-50'; ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-600 mb-1">All Agreements</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo array_sum($status_counts); ?></p>
                        </div>
                        <i class="fas fa-list text-gray-400"></i>
                    </div>
                </a>

                <a href="?status=Pending" class="block p-3 rounded-lg hover:bg-yellow-50 transition <?php echo $status_filter === 'Pending' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-gray-50'; ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Pending Review</p>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo $status_counts['Pending']; ?></p>
                        </div>
                        <i class="fas fa-clock text-yellow-400"></i>
                    </div>
                </a>

                <a href="?status=Active" class="block p-3 rounded-lg hover:bg-green-50 transition <?php echo $status_filter === 'Active' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-gray-50'; ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Active</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $status_counts['Active']; ?></p>
                        </div>
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                </a>

                <a href="?status=Terminated" class="block p-3 rounded-lg hover:bg-red-50 transition <?php echo $status_filter === 'Terminated' ? 'bg-red-50 border-l-4 border-red-500' : 'bg-gray-50'; ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Terminated</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo $status_counts['Terminated']; ?></p>
                        </div>
                        <i class="fas fa-ban text-red-400"></i>
                    </div>
                </a>
            </div>

            <div class="absolute bottom-0 w-64 p-4 border-t">
                <a href="admin_dashboard.php" class="block w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-center text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Bar -->
            <div class="bg-white shadow-sm border-b px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Agreement Management</h1>
                        <p class="text-sm text-gray-600">
                            <?php
                            if ($status_filter) {
                                echo ucfirst($status_filter) . ' Agreements';
                            } else {
                                echo 'All Agreements';
                            }
                            ?>
                            <span class="text-gray-400 mx-2">•</span>
                            <?php echo count($agreements); ?> results
                        </p>
                    </div>

                    <!-- Search Bar -->
                    <form method="GET" class="flex gap-2">
                        <?php if ($status_filter): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search agreements..."
                                class="w-80 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                            Search
                        </button>
                        <?php if ($search): ?>
                            <a href="?status=<?php echo $status_filter; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                                Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="mt-4 bg-green-50 border-l-4 border-green-500 px-4 py-3 flex items-center justify-between rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            <span class="text-green-800 text-sm font-medium"><?php echo htmlspecialchars($success_message); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Agreements List -->
            <div class="flex-1 overflow-y-auto p-6">
                <?php if (count($agreements) > 0): ?>
                    <div class="space-y-3">
                        <?php foreach ($agreements as $agreement): ?>
                            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition border border-gray-200">
                                <div class="p-4">
                                    <div class="flex items-center justify-between">

                                        <!-- Left Section: Agreement Info -->
                                        <div class="flex items-center space-x-4 flex-1">
                                            <!-- Status Icon -->
                                            <div class="flex-shrink-0">
                                                <?php
                                                $status_icons = [
                                                    'Pending' => ['icon' => 'fa-clock', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
                                                    'Active' => ['icon' => 'fa-check-circle', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
                                                    'Expired' => ['icon' => 'fa-calendar-times', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
                                                    'Terminated' => ['icon' => 'fa-ban', 'bg' => 'bg-red-100', 'text' => 'text-red-600']
                                                ];
                                                $icon_data = $status_icons[$agreement['agreement_status']] ?? ['icon' => 'fa-circle', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
                                                ?>
                                                <div class="w-12 h-12 <?php echo $icon_data['bg']; ?> rounded-lg flex items-center justify-center">
                                                    <i class="fas <?php echo $icon_data['icon'] . ' ' . $icon_data['text']; ?> text-lg"></i>
                                                </div>
                                            </div>

                                            <!-- Agreement Details -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($agreement['agreement_reference']); ?></h3>
                                                    <?php
                                                    $status_badges = [
                                                        'Pending' => 'bg-yellow-100 text-yellow-800',
                                                        'Active' => 'bg-green-100 text-green-800',
                                                        'Expired' => 'bg-gray-100 text-gray-800',
                                                        'Terminated' => 'bg-red-100 text-red-800'
                                                    ];
                                                    $badge_class = $status_badges[$agreement['agreement_status']] ?? 'bg-gray-100 text-gray-800';
                                                    ?>
                                                    <span class="px-2 py-0.5 <?php echo $badge_class; ?> rounded text-xs font-semibold">
                                                        <?php echo htmlspecialchars($agreement['agreement_status']); ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center text-sm text-gray-600 space-x-4">
                                                    <span>
                                                        <i class="fas fa-user-tie text-blue-600 mr-1"></i>
                                                        <?php echo htmlspecialchars($agreement['agent_name']); ?>
                                                    </span>
                                                    <span class="text-gray-300">•</span>
                                                    <span>
                                                        <i class="fas fa-seedling text-green-600 mr-1"></i>
                                                        <?php echo htmlspecialchars($agreement['farmer_name']); ?>
                                                    </span>
                                                    <span class="text-gray-300">•</span>
                                                    <span>
                                                        <i class="fas fa-calendar text-gray-400 mr-1"></i>
                                                        <?php echo date('M d, Y', strtotime($agreement['created_at'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Middle Section: Terms -->
                                        <div class="hidden lg:flex items-center space-x-6 px-6">
                                            <div class="text-center">
                                                <p class="text-xs text-gray-500 mb-1">Commission</p>
                                                <p class="text-lg font-bold text-purple-600"><?php echo number_format($agreement['commission_percentage'], 1); ?>%</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs text-gray-500 mb-1">Land</p>
                                                <p class="text-lg font-bold text-green-600"><?php echo $agreement['farmer_land_size']; ?> ac</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs text-gray-500 mb-1">Duration</p>
                                                <p class="text-sm font-bold text-indigo-600">
                                                    <?php
                                                    $start = new DateTime($agreement['start_date']);
                                                    $end = new DateTime($agreement['end_date']);
                                                    $diff = $start->diff($end);
                                                    echo $diff->y > 0 ? $diff->y . 'y' : $diff->m . 'm';
                                                    ?>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Right Section: Actions -->
                                        <div class="flex items-center space-x-2 flex-shrink-0">
                                            <button onclick="viewDetails('<?php echo htmlspecialchars($agreement['agreement_reference']); ?>')"
                                                class="px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition text-sm font-medium"
                                                title="View Details">
                                                <i class="fas fa-eye mr-1"></i>
                                                View
                                            </button>
                                            <button onclick="openStatusModal(<?php echo $agreement['agreement_id']; ?>, '<?php echo htmlspecialchars($agreement['agreement_reference']); ?>', '<?php echo $agreement['agreement_status']; ?>')"
                                                class="px-4 py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition text-sm font-medium"
                                                title="Update Status">
                                                <i class="fas fa-edit mr-1"></i>
                                                Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-lg shadow-sm p-16 text-center">
                        <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">No Agreements Found</h3>
                        <p class="text-gray-500">Try adjusting your search or filter criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white">Update Agreement Status</h3>
                    <button onclick="closeStatusModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <form method="POST" id="statusForm" class="p-6">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="agreement_id" id="modal_agreement_id">

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Agreement Reference</label>
                    <div class="bg-gray-50 rounded-lg p-3 border">
                        <p class="font-mono font-bold text-gray-800" id="modal_agreement_ref"></p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Current Status</label>
                    <div class="bg-gray-50 rounded-lg p-3 border">
                        <p class="font-semibold" id="modal_current_status"></p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Status</label>
                    <select name="new_status" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Status --</option>
                        <option value="Pending">Pending</option>
                        <option value="Active">Active</option>
                        <option value="Expired">Expired</option>
                        <option value="Terminated">Terminated</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Admin Notes (Optional)</label>
                    <textarea name="admin_notes" rows="3"
                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                        placeholder="Add notes about this change..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeStatusModal()"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition font-semibold">
                        <i class="fas fa-check mr-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(agreementId, agreementRef, currentStatus) {
            document.getElementById('modal_agreement_id').value = agreementId;
            document.getElementById('modal_agreement_ref').textContent = agreementRef;
            document.getElementById('modal_current_status').textContent = currentStatus;

            const statusColors = {
                'Pending': 'text-yellow-600',
                'Active': 'text-green-600',
                'Expired': 'text-gray-600',
                'Terminated': 'text-red-600'
            };
            document.getElementById('modal_current_status').className = 'font-semibold ' + (statusColors[currentStatus] || 'text-gray-800');

            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
            document.getElementById('statusForm').reset();
        }

        function viewDetails(agreementRef) {
            window.open('admin_view_agreement.php?ref=' + encodeURIComponent(agreementRef), '_blank');
        }

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>

</html>
<?php
ob_end_flush();
?>