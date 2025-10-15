<?php
ob_start();
include '../include/navbar.php';
include '../include/connect-db.php';

$agent_id = $_SESSION['user_id'];

// Fetch all agreements for this agent
$query = "SELECT afa.*, 
          f.full_name as farmer_name, 
          f.contact_number as farmer_contact,
          f.land_size as farmer_land_size,
          f.crops_cultivated as farmer_crops
          FROM agent_farmer_agreements afa
          JOIN farmers f ON afa.farmer_id = f.id
          WHERE afa.agent_id = ?
          ORDER BY afa.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$agreements = $result->fetch_all(MYSQLI_ASSOC);

// Count by status
$status_counts = [
    'Pending' => 0,
    'Active' => 0,
    'Expired' => 0,
    'Terminated' => 0
];

foreach ($agreements as $agreement) {
    $status_counts[$agreement['agreement_status']]++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Agreements - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">My Partnership Agreements</h1>
                    <p class="text-gray-600">Manage and track all your farmer partnership agreements</p>
                </div>
                <a href="Agreement.php" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    <i class="fas fa-plus mr-2"></i>Create New Agreement
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Agreements</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo count($agreements); ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-file-contract text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Active</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $status_counts['Active']; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Pending</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $status_counts['Pending']; ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Expired/Terminated</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $status_counts['Expired'] + $status_counts['Terminated']; ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="searchInput" placeholder="Search by farmer name, reference number..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Active">Active</option>
                    <option value="Expired">Expired</option>
                    <option value="Terminated">Terminated</option>
                </select>
            </div>
        </div>

        <!-- Agreements List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if (count($agreements) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Farmer</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Land & Crops</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Commission</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="agreementsTable">
                            <?php foreach ($agreements as $agreement): ?>
                                <tr class="hover:bg-gray-50 transition agreement-row"
                                    data-status="<?php echo htmlspecialchars($agreement['agreement_status']); ?>"
                                    data-search="<?php echo htmlspecialchars(strtolower($agreement['farmer_name'] . ' ' . $agreement['agreement_reference'])); ?>">

                                    <!-- Reference -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($agreement['agreement_reference']); ?></p>
                                            <p class="text-gray-500 text-xs"><?php echo date('M d, Y', strtotime($agreement['created_at'])); ?></p>
                                        </div>
                                    </td>

                                    <!-- Farmer -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($agreement['farmer_name']); ?></p>
                                            <p class="text-gray-500"><?php echo htmlspecialchars($agreement['farmer_contact']); ?></p>
                                        </div>
                                    </td>

                                    <!-- Land & Crops -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="text-gray-900"><?php echo $agreement['farmer_land_size']; ?> acres</p>
                                            <p class="text-gray-500 text-xs"><?php echo htmlspecialchars(substr($agreement['farmer_crops'], 0, 30)) . (strlen($agreement['farmer_crops']) > 30 ? '...' : ''); ?></p>
                                        </div>
                                    </td>

                                    <!-- Commission -->
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-semibold text-green-700"><?php echo number_format($agreement['commission_percentage'], 2); ?>%</span>
                                        <?php if ($agreement['exclusive_rights']): ?>
                                            <p class="text-xs text-gray-500">Exclusive</p>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Duration -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <p class="text-gray-900"><?php echo date('M d, Y', strtotime($agreement['start_date'])); ?></p>
                                            <p class="text-gray-500 text-xs">to <?php echo date('M d, Y', strtotime($agreement['end_date'])); ?></p>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4">
                                        <?php
                                        $status_colors = [
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'Active' => 'bg-green-100 text-green-800',
                                            'Expired' => 'bg-gray-100 text-gray-800',
                                            'Terminated' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_class = $status_colors[$agreement['agreement_status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-3 py-1 <?php echo $status_class; ?> rounded-full text-xs font-semibold">
                                            <?php echo htmlspecialchars($agreement['agreement_status']); ?>
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <a href="view_agreement.php?ref=<?php echo urlencode($agreement['agreement_reference']); ?>"
                                                class="text-blue-600 hover:text-blue-800 transition" title="View Full Agreement">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="printAgreement('<?php echo htmlspecialchars($agreement['agreement_reference']); ?>')"
                                                class="text-green-600 hover:text-green-800 transition" title="Print">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-16">
                    <i class="fas fa-file-contract text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No Agreements Yet</h3>
                    <p class="text-gray-500 mb-6">Start by creating your first partnership agreement with a farmer</p>
                    <a href="Agreement.php" class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-plus mr-2"></i>Create Agreement
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', filterAgreements);
        document.getElementById('statusFilter').addEventListener('change', filterAgreements);

        function filterAgreements() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const statusValue = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.agreement-row');

            rows.forEach(row => {
                const searchText = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');

                const matchesSearch = searchText.includes(searchValue);
                const matchesStatus = statusValue === '' || status === statusValue;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function printAgreement(ref) {
            window.open('view_agreement.php?ref=' + encodeURIComponent(ref), '_blank');
        }
    </script>
</body>

</html>
<?php
ob_end_flush();
?>