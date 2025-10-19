<?php include '../Include/SidebarWarehouse.php'; ?>
<link rel="stylesheet" href="../Include/sidebar.css">
<?php
ob_start();
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
          u.full_name as agent_name,
          u.phone as agent_phone,
          ai.district as agent_district
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
    <title>Agreement Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl shadow-lg p-5 mb-5 text-white">
            <h1 class="text-2xl font-bold">Agreement Management</h1>
            <p class="text-green-100 text-sm mt-1">Review and manage all partnership agreements</p>
        </div>

        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-5 rounded flex items-center justify-between">
                <span class="text-green-800 font-medium text-sm"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?></span>
                <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- HORIZONTAL Status Bar -->
        <div class="bg-white rounded-xl shadow-lg p-5 mb-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <!-- All Agreements -->
                <a href="?" class="flex items-center gap-4 p-4 rounded-lg transition-all <?php echo $status_filter === '' ? 'bg-blue-50 ring-2 ring-blue-500' : 'hover:bg-gray-50'; ?>">
                    <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clipboard-list text-blue-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-gray-600 uppercase mb-1">All Agreements</p>
                        <p class="text-3xl font-extrabold text-blue-600"><?php echo array_sum($status_counts); ?></p>
                    </div>
                </a>

                <!-- Pending -->
                <a href="?status=Pending" class="flex items-center gap-4 p-4 rounded-lg transition-all <?php echo $status_filter === 'Pending' ? 'bg-yellow-50 ring-2 ring-yellow-500' : 'hover:bg-gray-50'; ?>">
                    <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-hourglass-half text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Pending</p>
                        <div class="flex items-center gap-2">
                            <p class="text-3xl font-extrabold text-yellow-600"><?php echo $status_counts['Pending']; ?></p>
                            <?php if ($status_counts['Pending'] > 0): ?>
                                <span class="px-2 py-0.5 bg-yellow-200 text-yellow-800 text-xs font-bold rounded-full">Action</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>

                <!-- Active -->
                <a href="?status=Active" class="flex items-center gap-4 p-4 rounded-lg transition-all <?php echo $status_filter === 'Active' ? 'bg-green-50 ring-2 ring-green-500' : 'hover:bg-gray-50'; ?>">
                    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check-double text-green-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Active</p>
                        <p class="text-3xl font-extrabold text-green-600"><?php echo $status_counts['Active']; ?></p>
                    </div>
                </a>

                <!-- Terminated -->
                <a href="?status=Terminated" class="flex items-center gap-4 p-4 rounded-lg transition-all <?php echo $status_filter === 'Terminated' ? 'bg-red-50 ring-2 ring-red-500' : 'hover:bg-gray-50'; ?>">
                    <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-ban text-red-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Terminated</p>
                        <p class="text-3xl font-extrabold text-red-600"><?php echo $status_counts['Terminated']; ?></p>
                    </div>
                </a>

            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded-lg shadow p-4 mb-5">
            <form method="GET" class="flex gap-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search agreements..."
                    class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                <?php if ($status_filter): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php endif; ?>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold text-sm">
                    Search
                </button>
                <?php if ($search): ?>
                    <a href="?<?php echo $status_filter ? 'status=' . $status_filter : ''; ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold text-sm">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <?php if (count($agreements) > 0): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Agent</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Farmer</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Commission</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($agreements as $agreement): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-sm text-gray-900"><?php echo htmlspecialchars($agreement['agreement_reference']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($agreement['created_at'])); ?></p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-sm text-gray-900"><?php echo htmlspecialchars($agreement['agent_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($agreement['agent_district']); ?></p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-sm text-gray-900"><?php echo htmlspecialchars($agreement['farmer_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $agreement['farmer_land_size']; ?> acres</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-bold text-purple-600"><?php echo number_format($agreement['commission_percentage'], 1); ?>%</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-700">
                                        <?php
                                        $start = new DateTime($agreement['start_date']);
                                        $end = new DateTime($agreement['end_date']);
                                        $diff = $start->diff($end);
                                        echo $diff->y > 0 ? $diff->y . ' year' : $diff->m . ' month';
                                        ?>
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $status_badges = [
                                        'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                        'Active' => 'bg-green-100 text-green-800 border-green-300',
                                        'Expired' => 'bg-gray-100 text-gray-800 border-gray-300',
                                        'Terminated' => 'bg-red-100 text-red-800 border-red-300'
                                    ];
                                    $badge_class = $status_badges[$agreement['agreement_status']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                                    ?>
                                    <span class="px-3 py-1 <?php echo $badge_class; ?> border rounded-full text-xs font-bold inline-block">
                                        <?php echo $agreement['agreement_status']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="viewDetails('<?php echo htmlspecialchars($agreement['agreement_reference']); ?>')"
                                            class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="openStatusModal(<?php echo $agreement['agreement_id']; ?>, '<?php echo htmlspecialchars($agreement['agreement_reference']); ?>', '<?php echo $agreement['agreement_status']; ?>')"
                                            class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition" title="Update">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-16 text-center">
                <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-gray-700 mb-2">No Agreements Found</h3>
                <p class="text-gray-500">Try adjusting your search or filter</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">Update Status</h3>
                    <button onclick="closeStatusModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <form method="POST" id="statusForm" class="p-6">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="agreement_id" id="modal_agreement_id">

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Agreement</label>
                    <p class="font-mono font-bold text-sm text-gray-800 bg-gray-50 p-3 rounded border" id="modal_agreement_ref"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Current</label>
                    <p class="font-semibold text-sm bg-gray-50 p-3 rounded border" id="modal_current_status"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">New Status</label>
                    <select name="new_status" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-semibold">
                        <option value="">Select Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Active">Active</option>
                        <option value="Expired">Expired</option>
                        <option value="Terminated">Terminated</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Notes</label>
                    <textarea name="admin_notes" rows="3" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Optional notes..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeStatusModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:from-blue-700 hover:to-cyan-700 font-bold">
                        Update
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
            document.getElementById('modal_current_status').className = 'font-semibold text-sm bg-gray-50 p-3 rounded border ' + (statusColors[currentStatus] || 'text-gray-800');

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
            if (e.target === this) closeStatusModal();
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>