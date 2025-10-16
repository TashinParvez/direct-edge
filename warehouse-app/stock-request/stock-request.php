<?php

include '../../include/navbar.php';
$admin_id = isset($user_id) ? $user_id : 65;

include '../../include/connect-db.php'; // database connection

// Preserve current filters across actions
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Handle status update if approve/reject clicked
if (isset($_GET['action'], $_GET['id'])) {
    $requestId = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $statusVal = 'Done';
    } elseif ($action === 'reject') {
        $statusVal = 'Rejected';
    }

    if (isset($statusVal)) {
        $stmt = $conn->prepare("UPDATE stock_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE request_id = ?");
        $stmt->bind_param("si", $statusVal, $requestId);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect back with the same filters (no resubmission)
    $params = $_GET;
    unset($params['action'], $params['id']);
    $redirect = 'stock-request.php' . (!empty($params) ? ('?' . http_build_query($params)) : '');
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stock Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto bg-white shadow rounded-lg p-6">
        <h1 class="text-3xl font-bold text-blue-700 mb-6">Stock Requests</h1>

        <!-- Search & Filters (dynamic auto-search on typing with debounce, no Enter needed) -->
        <form id="filterForm" method="GET" class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
            <input
                id="searchInput"
                type="text"
                name="search"
                placeholder="Search by product or requester..."
                value="<?= htmlspecialchars($search) ?>"
                autocomplete="off"
                autocapitalize="none"
                autocorrect="off"
                spellcheck="false"
                class="border p-2 rounded w-full md:w-full focus:ring focus:ring-blue-200" />
            <div class="flex gap-2">
                <select name="status" id="statusSelect" class="border p-2 rounded w-full md:w-auto">
                    <option value="">Filter by Status</option>
                    <option value="Pending" <?= $status === 'Pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="Done" <?= $status === 'Done'     ? 'selected' : '' ?>>Done</option>
                    <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Working" <?= $status === 'Working'  ? 'selected' : '' ?>>Working</option>
                </select>
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
                            <td class="p-3"><?= htmlspecialchars($row['status']) ?></td>
                            <td class="p-3"><?= date('d M, Y H:i', strtotime($row['requested_at'])) ?></td>
                            <td class="p-3"><?= $row['notes'] !== null ? htmlspecialchars($row['notes']) : '-' ?></td>

                            <!-- action btn -->
                            <td class="p-3 flex gap-2">
                                <!-- Approve -->
                                <a
                                    href="?action=approve&id=<?= (int)$row['request_id'] ?><?= $qsAppend ?>"
                                    class="text-green-500 hover:text-green-700"
                                    title="Approve">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                <!-- Reject -->
                                <a
                                    href="?action=reject&id=<?= (int)$row['request_id'] ?><?= $qsAppend ?>"
                                    class="text-red-500 hover:text-red-700"
                                    title="Reject">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2.293-9.707a1 1 0 011.414 0L10 10.586l1.879-1.879a1 1 0 111.414 1.414L11.414 12l1.879 1.879a1 1 0 11-1.414 1.414L10 13.414l-1.879 1.879a1 1 0 11-1.414-1.414L8.586 12 6.707 10.121a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </a>
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
        // Dynamic search like before: auto-submit on typing with improved debounce and focus handling
        const form = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');
        const statusSelect = document.getElementById('statusSelect');

        let debounceTimer;

        // Debounced auto-submit on search input (dynamic, no Enter needed)
        function debounceSearch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                // Submit if search has content or is empty (to reset)
                const q = searchInput.value.trim();
                if (q.length >= 0) {
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            }, 300); // 300ms debounce delay balances UX and performance
        }

        searchInput.addEventListener('input', debounceSearch);

        // Optional: Immediate submit on Enter (for faster confirmation)
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(debounceTimer);
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
                e.preventDefault(); // Prevent default form submit if needed
            }
        });

        // Auto-submit when search is cleared (if browser supports native clear button)
        searchInput.addEventListener('search', debounceSearch);

        // Status filter: immediate submit on change (as before)
        statusSelect.addEventListener('change', () => {
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });

        // Prevent Enter in status select from submitting unexpectedly
        statusSelect.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });

        // Global: Prevent Enter in form from causing double-submit
        form.addEventListener('submit', (e) => {
            // Optional: Add loading state or disable during submit if needed
        });
    </script>
</body>

</html>