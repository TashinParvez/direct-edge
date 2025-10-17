<?php include '../Include/SidebarAgent.php'; ?>
<link rel="stylesheet" href="../Include/sidebar.css">
<?php

ob_start();
include '../include/connect-db.php';

$agent_id = $_SESSION['user_id'];
$agreement_ref = isset($_GET['ref']) ? $_GET['ref'] : '';

// Fetch agreement details
$agreement = null;
if ($agreement_ref) {
    $query = "SELECT afa.*, 
              f.full_name as farmer_name, 
              f.contact_number as farmer_contact,
              u.full_name as agent_name
              FROM agent_farmer_agreements afa
              JOIN farmers f ON afa.farmer_id = f.id
              JOIN users u ON afa.agent_id = u.user_id
              WHERE afa.agreement_reference = ? AND afa.agent_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $agreement_ref, $agent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agreement = $result->fetch_assoc();
}

if (!$agreement) {
    header("Location: agent_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agreement Submitted Successfully - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">

        <!-- Success Message -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                    <i class="fas fa-check-circle text-green-600 text-5xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Agreement Submitted Successfully!</h1>
                <p class="text-gray-600">Your partnership agreement has been created and is now pending review.</p>
            </div>

            <!-- Agreement Reference -->
            <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Agreement Reference Number</p>
                        <p class="text-2xl font-bold text-green-700"><?php echo htmlspecialchars($agreement['agreement_reference']); ?></p>
                    </div>
                    <button onclick="copyReference()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-copy mr-2"></i>Copy
                    </button>
                </div>
            </div>

            <!-- Agreement Summary -->
            <div class="border-t border-b py-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Agreement Summary</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Party Information</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="text-gray-600">Agent:</span> <span class="font-medium"><?php echo htmlspecialchars($agreement['agent_name']); ?></span></p>
                            <p><span class="text-gray-600">Farmer:</span> <span class="font-medium"><?php echo htmlspecialchars($agreement['farmer_name']); ?></span></p>
                            <p><span class="text-gray-600">Farmer Contact:</span> <span class="font-medium"><?php echo htmlspecialchars($agreement['farmer_contact']); ?></span></p>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Agreement Terms</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="text-gray-600">Commission:</span> <span class="font-medium"><?php echo number_format($agreement['commission_percentage'], 2); ?>%</span></p>
                            <p><span class="text-gray-600">Duration:</span> <span class="font-medium"><?php echo date('M d, Y', strtotime($agreement['start_date'])); ?> - <?php echo date('M d, Y', strtotime($agreement['end_date'])); ?></span></p>
                            <p><span class="text-gray-600">Exclusive Rights:</span>
                                <span class="font-medium">
                                    <?php echo $agreement['exclusive_rights'] ? 'Yes' : 'No'; ?>
                                </span>
                            </p>
                            <p><span class="text-gray-600">Status:</span>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                    <?php echo htmlspecialchars($agreement['agreement_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    What Happens Next?
                </h3>
                <ul class="space-y-2 text-sm text-blue-800">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                        <span>Your agreement is currently in <strong>Pending</strong> status</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                        <span>The system admin will review the agreement details</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                        <span>Once approved, the agreement becomes <strong>Active</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                        <span>You can start managing the farmer's crops and sales</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                        <span>Both parties will receive email notifications of status changes</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="view_agreement.php?ref=<?php echo urlencode($agreement['agreement_reference']); ?>"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center">
                    <i class="fas fa-file-alt mr-2"></i>View Full Agreement
                </a>
                <a href="my_agreements.php"
                    class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-center">
                    <i class="fas fa-list mr-2"></i>View All Agreements
                </a>
                <a href="agent_dashboard.php"
                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-center">
                    <i class="fas fa-home mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Important Notice -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Important:</strong> Please save your agreement reference number (<?php echo htmlspecialchars($agreement['agreement_reference']); ?>) for future reference. You can track your agreement status from your dashboard.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <script>
        function copyReference() {
            const referenceNumber = '<?php echo htmlspecialchars($agreement['agreement_reference']); ?>';

            // Create temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = referenceNumber;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            // Show feedback
            alert('Agreement reference number copied to clipboard!');
        }

        // Auto-scroll to top
        window.scrollTo(0, 0);
    </script>
</body>

</html>
<?php
ob_end_flush();
?>