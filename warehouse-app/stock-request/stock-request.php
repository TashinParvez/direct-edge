<?php
// Start output buffering to prevent header issues
ob_start();
?>
<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php

// include '../../include/navbar.php';
$admin_id = isset($user_id) ? $user_id : 65;

include '../../include/connect-db.php'; // database connection

// Preserve current filters across actions
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$successMessage = '';
$errorMessage = '';

// Handle status update if approve/reject clicked
if (isset($_GET['action'], $_GET['id'])) {
    $requestId = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $statusVal = 'Done';
        $message = 'approved';
    } elseif ($action === 'reject') {
        $statusVal = 'Rejected';
        $message = 'rejected';
    }

    if (isset($statusVal)) {
        $stmt = $conn->prepare("UPDATE stock_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE request_id = ?");
        $stmt->bind_param("si", $statusVal, $requestId);

        if ($stmt->execute()) {
            // --- NOTIFICATION ---
            include_once __DIR__ . '/../../include/notification_helpers.php';

            // Get the requester_id (who is a ShopOwner) for this request
            $stmtRequester = $conn->prepare("SELECT requester_id FROM stock_requests WHERE request_id = ?");
            $stmtRequester->bind_param("i", $requestId);
            $stmtRequester->execute();
            $result = $stmtRequester->get_result();
            if ($row = $result->fetch_assoc()) {
                $shop_owner_id = $row['requester_id'];
                $notification_message = "Your stock request #" . htmlspecialchars($requestId) . " has been " . htmlspecialchars($message) . ".";
                $notification_link = "/shop-owner-app/Self-Service-Orders/Self-Service-Orders.php"; // A relevant page for shop owners
                create_notification($conn, $shop_owner_id, 'stock_request_update', $notification_message, $notification_link);
            }
            // --- END NOTIFICATION ---

            $successMessage = "Request has been " . $message . " successfully!";
        } else {
            $errorMessage = "Failed to update request status.";
        }
        $stmt->close();
    }

    // Redirect back with the same filters (no resubmission)
    $params = $_GET;
    unset($params['action'], $params['id']);
    $redirect = 'stock-request.php' . (!empty($params) ? ('?' . http_build_query($params)) : '');

    // Clean buffer and redirect
    ob_end_clean();
    header("Location: $redirect");
    exit;
}

// Base SQL (single product per request in stock_requests)
$sql = "
SELECT 
    sr.request_id,
    sr.requester_id,
    u.full_name AS requester_name,
    sr.status,
    sr.requested_at,
    sr.updated_at,
    sr.note AS notes, 
    sr.product_id,
    p.name AS product_name,
    sr.quantity
FROM stock_requests sr
JOIN users u   ON sr.requester_id = u.user_id
JOIN products p ON sr.product_id = p.product_id
WHERE 1
";

// Apply search filter
if ($search !== '') {
    $searchEscaped = $conn->real_escape_string($search);
    $sql .= " AND (p.name LIKE '%$searchEscaped%' OR u.full_name LIKE '%$searchEscaped%')";
}

// Apply status filter
$allowedStatuses = ['Pending', 'Done', 'Rejected', 'Working'];
if ($status !== '' && in_array($status, $allowedStatuses, true)) {
    $statusEscaped = $conn->real_escape_string($status);
    $sql .= " AND sr.status = '$statusEscaped'";
}

$sql .= " ORDER BY sr.requested_at DESC, sr.request_id ASC";

$result = $conn->query($sql);

// Build query string for action links to preserve filters
$qsStr = http_build_query(array_filter([
    'search' => $search,
    'status' => $status
], fn($v) => $v !== '' && $v !== null));
$qsAppend = $qsStr ? '&' . htmlspecialchars($qsStr, ENT_QUOTES) : '';

// Flush output buffer before HTML
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stock Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../Include/sidebar.css">
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        .fade-out {
            animation: fadeOut 0.3s ease-out;
        }
    </style>
</head>

<body class="bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto bg-white shadow rounded-lg p-6">
        <!-- Success/Error Messages -->
        <?php if ($successMessage): ?>
            <div id="successAlert" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded fade-in">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div id="errorAlert" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded fade-in">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        <h1 class="text-3xl font-bold text-blue-700 mb-6">Stock Requests</h1>

        <!-- Search & Filters -->
        <form id="filterForm" method="GET" class="flex flex-col md:flex-row md:items-end md:justify-between mb-6 gap-4">
            <input
                id="searchInput"
                type="text"
                name="search"
                placeholder="Search by product or requester..."
                value="<?= htmlspecialchars($search) ?>"
                autocomplete="off"
                class="border p-2 rounded w-full md:flex-1 focus:ring focus:ring-blue-200" />

            <div class="flex gap-2">
                <select name="status" id="statusSelect" class="border p-2 rounded w-full md:w-auto">
                    <option value="">Filter by Status</option>
                    <option value="Pending" <?= $status === 'Pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="Done" <?= $status === 'Done'     ? 'selected' : '' ?>>Done</option>
                    <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Working" <?= $status === 'Working'  ? 'selected' : '' ?>>Working</option>
                </select>

                <!-- Clear Filters Button -->
                <?php if ($search || $status): ?>
                    <a href="stock-request.php"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors text-sm inline-flex items-center">
                        <i class='bx bx-x-circle mr-1'></i>Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Stock Requests Table -->
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3 text-left">Requester</th>
                        <th class="p-3 text-left">Product</th>
                        <th class="p-3 text-left">Quantity</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Requested At</th>
                        <th class="p-3 text-left">Notes</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-3"><?= htmlspecialchars($row['requester_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['product_name']) ?></td>
                            <td class="p-3"><?= (int)$row['quantity'] ?></td>
                            <td class="p-3">
                                <span class="status-badge px-2 py-1 rounded text-sm font-medium
                                    <?php
                                    echo match ($row['status']) {
                                        'Pending' => 'bg-yellow-100 text-yellow-800',
                                        'Done' => 'bg-green-100 text-green-800',
                                        'Rejected' => 'bg-red-100 text-red-800',
                                        'Working' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="p-3"><?= date('d M, Y H:i', strtotime($row['requested_at'])) ?></td>
                            <td class="p-3"><?= $row['notes'] !== null ? htmlspecialchars($row['notes']) : '-' ?></td>

                            <!-- action btn -->
                            <td class="p-3 flex gap-2">
                                <?php if ($row['status'] === 'Pending' || $row['status'] === 'Working'): ?>
                                    <!-- Approve -->
                                    <button
                                        onclick="confirmAction(<?= (int)$row['request_id'] ?>, 'approve', this)"
                                        class="approve-btn text-green-500 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                        title="Approve">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <!-- Reject -->
                                    <button
                                        onclick="confirmAction(<?= (int)$row['request_id'] ?>, 'reject', this)"
                                        class="reject-btn text-red-500 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                        title="Reject">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2.293-9.707a1 1 0 011.414 0L10 10.586l1.879-1.879a1 1 0 111.414 1.414L11.414 12l1.879 1.879a1 1 0 11-1.414 1.414L10 13.414l-1.879 1.879a1 1 0 11-1.414-1.414L8.586 12 6.707 10.121a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (dummy) -->
        <div class="mt-6 flex justify-center space-x-2">
            <button class="px-3 py-1 border rounded hover:bg-gray-100">1</button>
            <button class="px-3 py-1 border rounded hover:bg-gray-100">2</button>
            <button class="px-3 py-1 border rounded hover:bg-gray-100">Next</button>
        </div>
    </div>

    <script>
        // Auto-fade alerts after 3 seconds
        const successAlert = document.getElementById('successAlert');
        const errorAlert = document.getElementById('errorAlert');

        if (successAlert) {
            setTimeout(() => {
                successAlert.classList.add('fade-out');
                setTimeout(() => successAlert.remove(), 300);
            }, 3000);
        }

        if (errorAlert) {
            setTimeout(() => {
                errorAlert.classList.add('fade-out');
                setTimeout(() => errorAlert.remove(), 300);
            }, 3000);
        }

        // Search with Enter key (no auto-submit)
        const form = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');
        const statusSelect = document.getElementById('statusSelect');

        // Submit only on Enter key press in search input
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
                e.preventDefault();
            }
        });

        // Auto-submit when status filter changes
        statusSelect.addEventListener('change', () => {
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });

        // Confirmation dialog before action
        function confirmAction(requestId, action, button) {
            const actionText = action === 'approve' ? 'approve' : 'reject';
            const message = `Are you sure you want to ${actionText} this request? This action cannot be undone.`;

            if (confirm(message)) {
                updateStatus(requestId, action, button);
            }
        }

        // AJAX status update function
        function updateStatus(requestId, action, button) {
            // Disable all buttons in the row
            const row = button.closest('tr');
            const buttons = row.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);

            // Show loading state
            const originalHTML = button.innerHTML;
            button.innerHTML = '<svg class="animate-spin h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

            // Optimistically update UI
            const statusBadge = row.querySelector('.status-badge');
            const newStatus = action === 'approve' ? 'Done' : 'Rejected';
            const newStatusClass = action === 'approve' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';

            const oldStatus = statusBadge.textContent.trim();
            const oldClass = statusBadge.className;

            statusBadge.className = 'status-badge px-2 py-1 rounded text-sm font-medium ' + newStatusClass;
            statusBadge.textContent = newStatus;

            // Send AJAX request
            const params = new URLSearchParams(window.location.search);
            const queryString = params.toString();

            fetch(`?action=${action}&id=${requestId}${queryString ? '&' + queryString : ''}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        // Success - show notification
                        showNotification('success', `Request ${action === 'approve' ? 'approved' : 'rejected'} successfully!`);

                        // Hide action buttons
                        const actionCell = row.querySelector('td:last-child');
                        actionCell.innerHTML = '<span class="text-gray-400 text-sm">-</span>';
                    } else {
                        throw new Error('Update failed');
                    }
                })
                .catch(error => {
                    // Revert UI on error
                    statusBadge.className = oldClass;
                    statusBadge.textContent = oldStatus;
                    button.innerHTML = originalHTML;
                    buttons.forEach(btn => btn.disabled = false);

                    showNotification('error', 'Failed to update status. Please try again.');
                });
        }

        // Show notification function
        function showNotification(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded shadow-lg fade-in z-50 ${
                type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
            }`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.classList.add('fade-out');
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
        }
    </script>
</body>

</html>