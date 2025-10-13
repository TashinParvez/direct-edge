<?php
include '../include/navbar.php';
$agent_id = isset($user_id) ? $user_id : 64;
include '../include/connect-db.php'; // Database connection


// Handle form submissions (Add/Update/Delete Payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_payment' || $_POST['action'] === 'edit_payment') {
            $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
            $farmer_id = isset($_POST['farmer_id']) ? (int)$_POST['farmer_id'] : 0;
            $total_amount = (float)$_POST['total_amount'];
            $paid_amount = (float)$_POST['paid_amount'];
            $due_date = $_POST['due_date'] ? date('Y-m-d', strtotime($_POST['due_date'])) : NULL;
            $status = $_POST['status']; // This will be auto-set by JS, but validate/override if needed
            $notes = trim($_POST['notes']);

            // Auto-set status based on amounts (server-side validation/override for security)
            if ($paid_amount >= $total_amount) {
                $status = 'Paid';
            } elseif ($paid_amount > 0) {
                $status = 'Partial';
            } else {
                $status = 'Pending';
            }

            // Validate agent ownership
            $valid = false;
            if ($payment_id > 0) {
                // For updates, check payment ownership and get correct farmer_id
                $check_stmt = $conn->prepare("SELECT fp.farmer_id FROM farmer_payments fp JOIN farmers f ON fp.farmer_id = f.id WHERE fp.payment_id = ? AND fp.agent_id = ? AND f.agent_id = ?");
                $check_stmt->bind_param('iii', $payment_id, $agent_id, $agent_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $farmer_id = (int)$row['farmer_id'];  // Override with payment's farmer_id
                    $valid = true;
                }
                $check_stmt->close();
            } else {
                // For new payments, check farmer ownership
                $check_stmt = $conn->prepare("SELECT id FROM farmers WHERE id = ? AND agent_id = ?");
                $check_stmt->bind_param('ii', $farmer_id, $agent_id);
                $check_stmt->execute();
                $valid = $check_stmt->get_result()->num_rows > 0;
                $check_stmt->close();
            }


            if (!$valid) {
                $message = $payment_id > 0 ? "Unauthorized payment access." : "Unauthorized farmer access.";
                $success = false;
            } else {
                if ($payment_id > 0) {
                    // Update (now using validated $farmer_id)
                    $update_stmt = $conn->prepare("UPDATE farmer_payments SET total_amount = ?, paid_amount = ?, due_date = ?, status = ?, notes = ?, updated_at = NOW() WHERE payment_id = ? AND agent_id = ? AND farmer_id = ?");
                    $update_stmt->bind_param('ddsssiii', $total_amount, $paid_amount, $due_date, $status, $notes, $payment_id, $agent_id, $farmer_id);
                    $success = $update_stmt->execute();
                    $message = $success ? "Payment updated successfully." : "Failed to update payment.";
                    $update_stmt->close();
                } else {
                    // Insert new
                    $insert_stmt = $conn->prepare("INSERT INTO farmer_payments (agent_id, farmer_id, total_amount, paid_amount, due_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param('iiddsss', $agent_id, $farmer_id, $total_amount, $paid_amount, $due_date, $status, $notes);
                    $success = $insert_stmt->execute();
                    $message = $success ? "Payment added successfully." : "Failed to add payment.";
                    $insert_stmt->close();
                }
            }
        } elseif ($_POST['action'] === 'delete_payment') {
            $payment_id = (int)$_POST['payment_id'];
            // Verify payment belongs to agent
            $check_stmt = $conn->prepare("SELECT payment_id FROM farmer_payments WHERE payment_id = ? AND agent_id = ?");
            $check_stmt->bind_param('ii', $payment_id, $agent_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $check_stmt->close();
                $delete_stmt = $conn->prepare("DELETE FROM farmer_payments WHERE payment_id = ? AND agent_id = ?");
                $delete_stmt->bind_param('ii', $payment_id, $agent_id);
                $success = $delete_stmt->execute();
                $message = $success ? "Payment deleted successfully." : "Failed to delete payment.";
                $delete_stmt->close();
            } else {
                $message = "Unauthorized access.";
                $success = false;
            }
        }
        // Redirect to self to clear POST
        header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode($message ?? ''));
        exit;
    }
}


// Get message from URL
$message = $_GET['msg'] ?? '';
$success = strpos($message, 'success') !== false || strpos($message, 'added') !== false || strpos($message, 'updated') !== false || strpos($message, 'deleted') !== false;


// Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? 'all';


// Build query for farmers with aggregated payments
$where_conditions = ["f.agent_id = ?"];
$params = [$agent_id];
$types = 'i';


if (!empty($search)) {
    $where_conditions[] = "(f.full_name LIKE ? OR f.contact_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}


if ($status_filter !== 'all') {
    if ($status_filter === 'Pending') {
        $where_conditions[] = "(SELECT SUM(fp.total_amount - fp.paid_amount) FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = f.agent_id) > 0";
    } elseif ($status_filter === 'Paid') {
        $where_conditions[] = "(SELECT SUM(fp.total_amount - fp.paid_amount) FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = f.agent_id) = 0 AND EXISTS (SELECT 1 FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = f.agent_id)";
    }
}


$where_sql = implode(' AND ', $where_conditions);


$sql = "SELECT 
    f.id,
    f.full_name,
    f.contact_number,
    (SELECT COALESCE(SUM(fp.total_amount - fp.paid_amount), 0) FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = ?) as due_amount,
    (SELECT fp.notes FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = ? ORDER BY fp.created_at DESC LIMIT 1) as last_notes,
    (SELECT fp.last_payment_amount FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = ? AND fp.last_payment_date IS NOT NULL ORDER BY fp.last_payment_date DESC LIMIT 1) as last_amount,
    (SELECT fp.last_payment_date FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = ? AND fp.last_payment_date IS NOT NULL ORDER BY fp.last_payment_date DESC LIMIT 1) as last_date,
    (SELECT fp.due_date FROM farmer_payments fp WHERE fp.farmer_id = f.id AND fp.agent_id = ? AND (fp.total_amount - fp.paid_amount) > 0 AND fp.due_date IS NOT NULL ORDER BY fp.due_date ASC LIMIT 1) as next_due_date
FROM farmers f 
WHERE $where_sql 
ORDER BY due_amount DESC, f.id DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param($types . 'iiiii', ...array_merge($params, [$agent_id, $agent_id, $agent_id, $agent_id, $agent_id]));
$stmt->execute();
$result = $stmt->get_result();
$farmers = [];
while ($row = $result->fetch_assoc()) {
    $row['status'] = ($row['due_amount'] > 0) ? 'Pending' : 'Paid';
    $row['days_ago'] = $row['last_date'] ? floor((time() - strtotime($row['last_date'])) / (60 * 60 * 24)) : 0;
    $row['farmer_id_display'] = '#' . $row['id'];


    // Calculate notes based on due date
    if ($row['next_due_date']) {
        $today = new DateTime();
        $dueDate = new DateTime($row['next_due_date']);
        $diff = $today->diff($dueDate);
        $daysDiff = (int)$diff->format('%r%a');


        if ($daysDiff > 0) {
            $row['next_due_notes'] = "Payment due in {$daysDiff} days";
        } elseif ($daysDiff < 0) {
            $row['next_due_notes'] = "Payment overdue by " . abs($daysDiff) . " days";
        } else {
            $row['next_due_notes'] = "Payment due today";
        }
    } else {
        $row['next_due_notes'] = "No upcoming due";
    }


    $farmers[] = $row;
}
$stmt->close();


// Fetch all payments for JS (grouped by farmer)
$all_payments_sql = "SELECT fp.* FROM farmer_payments fp WHERE fp.agent_id = ? ORDER BY fp.farmer_id, fp.created_at DESC";
$payments_stmt = $conn->prepare($all_payments_sql);
$payments_stmt->bind_param('i', $agent_id);
$payments_stmt->execute();
$all_payments_result = $payments_stmt->get_result();
$payments_data = [];
while ($row = $all_payments_result->fetch_assoc()) {
    $fid = $row['farmer_id'];
    if (!isset($payments_data[$fid])) $payments_data[$fid] = [];
    $payments_data[$fid][] = $row;
}
$payments_json = json_encode($payments_data);
$payments_stmt->close();


// Fetch farmers for modal dropdown BEFORE closing connection
$farmers_for_select_query = $conn->query("SELECT id, full_name FROM farmers WHERE agent_id = $agent_id ORDER BY full_name");
$farmers_for_select = [];
while ($f = $farmers_for_select_query->fetch_assoc()) {
    $farmers_for_select[] = $f;
}


$conn->close();
?>


<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <title>Payment & Financial Handling - Stock Integrated</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .financial-container {
            transition: all 0.3s ease;
        }


        .financial-container:hover {
            background-color: #f9fafb;
        }


        .table-row {
            transition: all 0.2s ease;
        }


        .table-row:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
        }


        .filter-container {
            transition: all 0.3s ease;
        }


        .filter-container:hover {
            background-color: #f9fafb;
        }


        .search-field {
            transition: all 0.3s ease;
        }


        .search-field:hover {
            background-color: #f3f4f6;
        }


        .search-field:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }


        .status-badge {
            transition: all 0.2s ease;
        }


        .status-badge:hover {
            transform: scale(1.05);
        }


        .action-btn {
            transition: all 0.2s ease;
        }


        .action-btn:hover {
            transform: translateY(-1px);
        }


        .table-container {
            animation: fadeIn 0.6s ease-out;
        }


        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }


            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        @media print {
            .no-print {
                display: none !important;
            }


            .table-row:hover {
                background-color: transparent !important;
                transform: none !important;
            }
        }

        #notification {
            transition: opacity 0.5s ease-out;
        }
    </style>
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>


<body class="bg-gray-100">


    <?php if ($message): ?>
        <div id="notification" class="fixed top-4 right-4 z-50 bg-<?php echo $success ? 'green' : 'red'; ?>-500 text-white px-4 py-2 rounded-lg shadow-lg no-print">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>


    <section class="home-section p-0 pb-4 mx-2 md:mx-8 lg:mx-16">
        <div class="flex justify-between items-center p-4 no-print">
            <h1 class="text-2xl font-bold">Payment & Financial Records</h1>
            <button class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600" onclick="openAddModal(0)">
                <i class='bx bx-plus'></i> Add Payment
            </button>
        </div>


        <div class="container mx-auto px-4">


            <!-- Filters / Search Section -->
            <div class="bg-white shadow-md rounded-lg p-4 mb-6 filter-container no-print">
                <form method="GET" class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-search mr-1'></i>Search Farmer
                        </label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by farmer name..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                    </div>


                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-filter mr-1'></i>Payment Status
                        </label>
                        <select name="status_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Paid" <?php echo $status_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>


                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition-colors">
                            <i class='bx bx-filter-alt mr-2'></i>Apply Filter
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="flex-1 bg-gray-500 text-white px-6 py-2 rounded text-center hover:bg-gray-600 transition-colors">
                            Clear
                        </a>
                    </div>
                </form>
            </div>


            <!-- Farmers with Payments Table -->
            <?php if (empty($farmers)): ?>
                <div class="bg-white shadow-lg rounded-lg p-8 text-center">
                    <i class='bx bx-money text-gray-300 text-6xl mb-4'></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Payment Records</h3>
                    <p class="text-gray-500 mb-6">Start by adding payments for your farmers.</p>
                    <button class="bg-green-500 text-white px-6 py-3 rounded hover:bg-green-600" onclick="openAddModal(0)">+ Add First Payment</button>
                </div>
            <?php else: ?>
                <div class="bg-white shadow-lg rounded-lg p-6 financial-container">
                    <div class="overflow-x-auto table-container">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class='bx bx-user mr-1'></i>Farmer
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class='bx bx-money mr-1'></i>Due Amount
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class='bx bx-calendar-check mr-1'></i>Last Payment
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class='bx bx-check-circle mr-1'></i>Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class='bx bx-note mr-1'></i>Notes
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider no-print">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($farmers as $farmer): ?>
                                    <tr class="table-row">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                        <i class='bx bx-user text-green-600'></i>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($farmer['full_name']); ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?php echo $farmer['farmer_id_display']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold <?php echo $farmer['due_amount'] > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                ৳ <?php echo number_format($farmer['due_amount'], 2); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($farmer['last_date']): ?>
                                                <div class="text-sm text-gray-900"><?php echo date('Y-m-d', strtotime($farmer['last_date'])); ?></div>
                                                <div class="text-sm text-gray-500">৳<?php echo number_format($farmer['last_amount'], 2); ?> (<?php echo $farmer['days_ago']; ?> days ago)</div>
                                            <?php else: ?>
                                                <div class="text-sm text-gray-500">No payments yet</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $farmer['status'] === 'Pending' ? 'yellow' : 'green'; ?>-100 text-<?php echo $farmer['status'] === 'Pending' ? 'yellow' : 'green'; ?>-800 status-badge">
                                                <i class='bx bx-<?php echo $farmer['status'] === 'Pending' ? 'time-five' : 'check'; ?> mr-1'></i><?php echo $farmer['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($farmer['next_due_notes']); ?></div>
                                            <?php if ($farmer['last_notes']): ?>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($farmer['last_notes']); ?></div>
                                            <?php endif; ?>
                                            <div class="text-sm text-gray-500">Contact: <?php echo htmlspecialchars($farmer['contact_number']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium no-print">
                                            <div class="flex justify-center space-x-3">
                                                <button onclick="openAddModal(<?php echo $farmer['id']; ?>)" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 text-xs" title="Add Payment">
                                                    <i class='bx bx-plus'></i> Add
                                                </button>
                                                <button onclick="openViewModal(<?php echo $farmer['id']; ?>)" class="text-gray-600 hover:text-gray-900 action-btn" title="View Details">
                                                    <i class='bx bx-show text-lg'></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>


        </div>
    </section>


    <!-- Add/Edit Payment Modal (Landscape) -->
    <div id="addPaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold" id="modalTitle">Add Payment</h3>
                        <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                    </div>
                    <form method="POST" id="paymentForm">
                        <input type="hidden" name="action" value="add_payment" id="formAction">
                        <input type="hidden" name="payment_id" id="edit_payment_id" value="0">
                        <input type="hidden" name="status" id="hidden_status" value="Pending">


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Farmer *</label>
                                <select name="farmer_id" id="farmer_select" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                                    <option value="">Select Farmer</option>
                                    <?php
                                    foreach ($farmers_for_select as $f) {
                                        echo "<option value='{$f['id']}'>" . htmlspecialchars($f['full_name']) . " (ID: #{$f['id']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>


                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount (৳) *</label>
                                <input type="number" name="total_amount" id="total_amount" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            </div>


                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Paid Amount (৳) *</label>
                                <input type="number" name="paid_amount" id="paid_amount" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Due Amount (৳)</label>
                                <input type="number" id="due_amount" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed" readonly>
                            </div>


                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>


                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed" disabled required>
                                    <option value="Pending">Pending</option>
                                    <option value="Partial">Partial</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>


                            <div class="mb-4 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" id="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                            </div>
                        </div>


                        <div class="flex justify-end space-x-3 mt-4">
                            <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- View Payments Modal -->
    <div id="viewPaymentsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 id="viewModalTitle" class="text-xl font-bold">Payment Details</h3>
                        <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                    </div>
                    <div id="viewModalContent" class="space-y-4">
                        <!-- Payments list populated by JS -->
                    </div>
                    <div class="flex justify-end mt-6 space-x-3">
                        <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php include '../include/footer.php'; ?>


    <script>
        const paymentsData = <?php echo $payments_json; ?>;
        let currentFarmerId = 0;

        // Function to update due amount and status
        function updateDueAndStatus() {
            const total = parseFloat(document.getElementById('total_amount').value) || 0;
            const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
            const due = total - paid;
            const statusSelect = document.getElementById('status');
            const dueInput = document.getElementById('due_amount');
            const hiddenStatus = document.getElementById('hidden_status');

            // Update due amount display
            dueInput.value = due.toFixed(2);

            // Auto-update status
            let newStatus = 'Pending';
            if (paid >= total) {
                newStatus = 'Paid';
            } else if (paid > 0) {
                newStatus = 'Partial';
            }

            statusSelect.value = newStatus;
            hiddenStatus.value = newStatus; // For form submission

            // Color the due input based on value
            if (due > 0) {
                dueInput.className = 'w-full px-3 py-2 border border-red-300 rounded-md bg-red-50 text-red-600';
            } else {
                dueInput.className = 'w-full px-3 py-2 border border-green-300 rounded-md bg-green-50 text-green-600';
            }
        }


        function openAddModal(farmerId = 0) {
            const modal = document.getElementById('addPaymentModal');
            const form = document.getElementById('paymentForm');
            const farmerSelect = document.getElementById('farmer_select');
            const modalTitle = document.getElementById('modalTitle');


            // Reset form
            form.reset();
            document.getElementById('edit_payment_id').value = 0;
            document.getElementById('formAction').value = 'add_payment';
            document.getElementById('due_amount').value = '0.00';
            document.getElementById('status').value = 'Pending';
            document.getElementById('hidden_status').value = 'Pending';
            farmerSelect.disabled = false;
            modalTitle.textContent = 'Add Payment';


            if (farmerId > 0) {
                farmerSelect.value = farmerId;
                farmerSelect.disabled = true;
            }


            modal.classList.remove('hidden');
        }


        function closeAddModal() {
            document.getElementById('addPaymentModal').classList.add('hidden');
        }


        function openViewModal(farmerId) {
            currentFarmerId = farmerId;
            const modal = document.getElementById('viewPaymentsModal');
            const content = document.getElementById('viewModalContent');
            const title = document.getElementById('viewModalTitle');


            const farmer = <?php echo json_encode($farmers); ?>.find(f => f.id == farmerId);
            if (!farmer) return;


            title.textContent = `Payments for ${farmer.full_name} (ID: ${farmer.farmer_id_display})`;


            const payments = paymentsData[farmerId] || [];
            if (payments.length === 0) {
                content.innerHTML = '<p class="text-gray-500 text-center">No payments yet.</p>';
            } else {
                let html = '';
                payments.forEach(payment => {
                    const dueAmt = (parseFloat(payment.total_amount) - parseFloat(payment.paid_amount)).toFixed(2);
                    html += `
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-semibold text-lg">Payment #${payment.payment_id}</h4>
                                <div class="flex space-x-2">
                                    <button onclick="openEditPayment(${payment.payment_id}, ${farmerId})" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 text-sm">
                                        <i class='bx bx-edit'></i> Edit
                                    </button>
                                    <button onclick="deletePayment(${payment.payment_id})" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                                        <i class='bx bx-trash'></i> Delete
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <p><strong>Total Amount:</strong> ৳ ${parseFloat(payment.total_amount).toFixed(2)}</p>
                                <p><strong>Paid Amount:</strong> ৳ ${parseFloat(payment.paid_amount).toFixed(2)}</p>
                                <p><strong>Due Amount:</strong> <span class="${dueAmt > 0 ? 'text-red-600 font-semibold' : 'text-green-600'}">৳ ${dueAmt}</span></p>
                                <p><strong>Due Date:</strong> ${payment.due_date || 'N/A'}</p>
                                <p class="col-span-2"><strong>Status:</strong> <span class="px-2 py-1 bg-${payment.status === 'Paid' ? 'green' : payment.status === 'Partial' ? 'yellow' : 'red'}-100 text-${payment.status === 'Paid' ? 'green' : payment.status === 'Partial' ? 'yellow' : 'red'}-800 text-xs rounded-full">${payment.status}</span></p>
                                ${payment.notes ? `<p class="col-span-2"><strong>Notes:</strong> ${payment.notes}</p>` : ''}
                            </div>
                        </div>
                    `;
                });
                html += `
                    <div class="pt-4 border-t text-center">
                    <button onclick="closeViewModal(); openAddModal(${farmerId});" class="bg-green-500 text-white px-6 py-3 rounded hover:bg-green-600">+ Add New Payment</button>
                </div>
                `;
                content.innerHTML = html;
            }


            modal.classList.remove('hidden');
        }


        function closeViewModal() {
            document.getElementById('viewPaymentsModal').classList.add('hidden');
        }


        function openEditPayment(paymentId, farmerId) {
            const payment = Object.values(paymentsData).flat().find(p => p.payment_id == paymentId);
            if (!payment) return;


            closeViewModal();


            const modal = document.getElementById('addPaymentModal');
            const form = document.getElementById('paymentForm');
            const modalTitle = document.getElementById('modalTitle');
            const farmerSelect = document.getElementById('farmer_select');


            // Populate form
            document.getElementById('edit_payment_id').value = paymentId;
            farmerSelect.value = farmerId;
            farmerSelect.disabled = true;
            document.getElementById('total_amount').value = payment.total_amount;
            document.getElementById('paid_amount').value = payment.paid_amount;
            document.getElementById('due_date').value = payment.due_date || '';
            document.getElementById('notes').value = payment.notes || '';


            document.getElementById('formAction').value = 'edit_payment';
            modalTitle.textContent = `Edit Payment #${paymentId}`;

            // Update due and status for edit
            updateDueAndStatus();

            modal.classList.remove('hidden');
        }


        function deletePayment(paymentId) {
            if (confirm('Delete this payment? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete_payment"><input type="hidden" name="payment_id" value="${paymentId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }


        // Close modals on outside click
        document.getElementById('addPaymentModal').addEventListener('click', (e) => {
            if (e.target.id === 'addPaymentModal') closeAddModal();
        });
        document.getElementById('viewPaymentsModal').addEventListener('click', (e) => {
            if (e.target.id === 'viewPaymentsModal') closeViewModal();
        });

        // Auto-hide notification after 3 seconds with fade out
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 500);
                }, 3000);
            }
        });

        // Event listeners for auto-update on input change
        document.addEventListener('DOMContentLoaded', function() {
            const totalInput = document.getElementById('total_amount');
            const paidInput = document.getElementById('paid_amount');

            if (totalInput) {
                totalInput.addEventListener('input', updateDueAndStatus);
            }
            if (paidInput) {
                paidInput.addEventListener('input', updateDueAndStatus);
            }
        });
    </script>


</body>


</html>