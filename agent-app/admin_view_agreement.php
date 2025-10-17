<?php include '../Include/SidebarAgent.php'; ?>
<link rel="stylesheet" href="../Include/sidebar.css">
<?php
ob_start();
include '../include/connect-db.php';

$agreement_ref = isset($_GET['ref']) ? $_GET['ref'] : '';

// Fetch full agreement details with related information
$agreement = null;
if ($agreement_ref) {
    $query = "SELECT afa.*, 
              f.full_name as farmer_name, 
              f.contact_number as farmer_contact,
              f.present_address as farmer_address,
              f.land_size as farmer_land_size,
              f.crops_cultivated as farmer_crops,
              f.farmer_type as farmer_type,
              u.full_name as agent_name,
              u.phone as agent_phone,
              ai.nid_number as agent_nid,
              ai.region as agent_region,
              ai.district as agent_district,
              ai.experience_years as agent_experience
              FROM agent_farmer_agreements afa
              JOIN farmers f ON afa.farmer_id = f.id
              JOIN users u ON afa.agent_id = u.user_id
              JOIN agent_info ai ON u.user_id = ai.agent_id
              WHERE afa.agreement_reference = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $agreement_ref);
    $stmt->execute();
    $result = $stmt->get_result();
    $agreement = $result->fetch_assoc();
}

if (!$agreement) {
    header("Location: admin_agreements_review.php");
    exit();
}

// Calculate duration
$start = new DateTime($agreement['start_date']);
$end = new DateTime($agreement['end_date']);
$duration = $start->diff($end);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Agreement - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-5xl">

        <!-- Header with Action Buttons -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Partnership Agreement</h1>
                    <p class="text-gray-600">Reference: <?php echo htmlspecialchars($agreement['agreement_reference']); ?></p>
                </div>
                <div class="flex gap-3">
                    <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <button onclick="downloadPDF()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-download mr-2"></i>Download PDF
                    </button>
                    <a href="admin_agreements_review.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Agreement Document -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6" id="agreement-content">

            <!-- Agreement Header -->
            <div class="text-center border-b-2 border-gray-300 pb-6 mb-6">
                <h1 class="text-3xl font-bold text-green-700 mb-2">AGENT-FARMER PARTNERSHIP AGREEMENT</h1>
                <p class="text-gray-600">DirectEdge Agricultural Supply Chain Platform</p>
                <p class="text-sm text-gray-500 mt-2">Agreement Reference: <?php echo htmlspecialchars($agreement['agreement_reference']); ?></p>
            </div>

            <!-- Status Badge -->
            <div class="flex justify-center mb-6">
                <?php
                $status_colors = [
                    'Pending' => 'bg-yellow-100 text-yellow-800',
                    'Active' => 'bg-green-100 text-green-800',
                    'Expired' => 'bg-gray-100 text-gray-800',
                    'Terminated' => 'bg-red-100 text-red-800'
                ];
                $status_class = $status_colors[$agreement['agreement_status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="px-6 py-2 <?php echo $status_class; ?> rounded-full font-semibold text-lg">
                    Status: <?php echo htmlspecialchars($agreement['agreement_status']); ?>
                </span>
            </div>

            <!-- Parties Information -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">PARTIES TO THE AGREEMENT</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Agent (First Party) -->
                    <div class="border rounded-lg p-5 bg-green-50">
                        <h3 class="font-bold text-green-800 mb-3 text-lg">FIRST PARTY (Agent)</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-semibold">Name:</span> <?php echo htmlspecialchars($agreement['agent_name']); ?></p>
                            <p><span class="font-semibold">NID:</span> <?php echo htmlspecialchars($agreement['agent_nid']); ?></p>
                            <p><span class="font-semibold">Contact:</span> <?php echo htmlspecialchars($agreement['agent_phone']); ?></p>
                            <p><span class="font-semibold">Region:</span> <?php echo htmlspecialchars($agreement['agent_region']); ?></p>
                            <p><span class="font-semibold">District:</span> <?php echo htmlspecialchars($agreement['agent_district']); ?></p>
                            <p><span class="font-semibold">Experience:</span> <?php echo $agreement['agent_experience']; ?> years</p>
                        </div>
                    </div>

                    <!-- Farmer (Second Party) -->
                    <div class="border rounded-lg p-5 bg-blue-50">
                        <h3 class="font-bold text-blue-800 mb-3 text-lg">SECOND PARTY (Farmer)</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-semibold">Name:</span> <?php echo htmlspecialchars($agreement['farmer_name']); ?></p>
                            <p><span class="font-semibold">Contact:</span> <?php echo htmlspecialchars($agreement['farmer_contact']); ?></p>
                            <p><span class="font-semibold">Address:</span> <?php echo htmlspecialchars($agreement['farmer_address']); ?></p>
                            <p><span class="font-semibold">Land Size:</span> <?php echo $agreement['farmer_land_size']; ?> acres</p>
                            <p><span class="font-semibold">Crops:</span> <?php echo htmlspecialchars($agreement['farmer_crops']); ?></p>
                            <p><span class="font-semibold">Farmer Type:</span> <?php echo htmlspecialchars($agreement['farmer_type']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agreement Terms -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">TERMS AND CONDITIONS</h2>

                <!-- Financial Terms -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">1. Financial Terms</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="mb-2"><span class="font-semibold">Commission Percentage:</span> <?php echo number_format($agreement['commission_percentage'], 2); ?>% of total sales</p>
                        <p><span class="font-semibold">Exclusive Selling Rights:</span>
                            <?php echo $agreement['exclusive_rights'] ? 'Yes - Agent has exclusive rights to market and sell farmer\'s produce' : 'No - Farmer may use other channels'; ?>
                        </p>
                    </div>
                </div>

                <!-- Duration -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">2. Agreement Duration</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="mb-2"><span class="font-semibold">Start Date:</span> <?php echo date('F d, Y', strtotime($agreement['start_date'])); ?></p>
                        <p class="mb-2"><span class="font-semibold">End Date:</span> <?php echo date('F d, Y', strtotime($agreement['end_date'])); ?></p>
                        <p><span class="font-semibold">Duration:</span> <?php echo $duration->y; ?> year(s), <?php echo $duration->m; ?> month(s), <?php echo $duration->d; ?> day(s)</p>
                    </div>
                </div>

                <!-- Payment Terms -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">3. Payment Terms</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="whitespace-pre-line"><?php echo htmlspecialchars($agreement['payment_terms']); ?></p>
                    </div>
                </div>

                <!-- Responsibilities -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-3">4. Responsibilities of Parties</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">Agent Responsibilities:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                <li>Quality verification of crops before listing</li>
                                <li>Warehouse coordination and logistics management</li>
                                <li>Market linkage and price negotiation</li>
                                <li>Timely payment processing to farmer</li>
                                <li>Provide technical guidance and market information</li>
                            </ul>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-blue-800 mb-2">Farmer Responsibilities:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                <li>Maintain crop quality standards</li>
                                <li>Follow cultivation best practices</li>
                                <li>Provide accurate crop information and forecasts</li>
                                <li>Ensure harvest availability as per schedule</li>
                                <li>Grant access for quality inspections</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Digital Signatures -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">DIGITAL SIGNATURES</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Agent Signature -->
                    <div class="text-center">
                        <div class="border-2 border-gray-300 rounded-lg p-4 mb-3 bg-white h-40 flex items-center justify-center">
                            <?php if ($agreement['agent_signature_url']): ?>
                                <img src="../agent-app/<?php echo htmlspecialchars($agreement['agent_signature_url']); ?>" alt="Agent Signature" class="max-h-32">
                            <?php else: ?>
                                <p class="text-gray-400">No signature available</p>
                            <?php endif; ?>
                        </div>
                        <p class="font-semibold"><?php echo htmlspecialchars($agreement['agent_name']); ?></p>
                        <p class="text-sm text-gray-600">Agent (First Party)</p>
                        <p class="text-xs text-gray-500">Signed: <?php echo date('M d, Y H:i', strtotime($agreement['created_at'])); ?></p>
                    </div>

                    <!-- Farmer Signature -->
                    <div class="text-center">
                        <div class="border-2 border-gray-300 rounded-lg p-4 mb-3 bg-white h-40 flex items-center justify-center">
                            <?php if ($agreement['farmer_signature_url']): ?>
                                <img src="../agent-app/<?php echo htmlspecialchars($agreement['farmer_signature_url']); ?>" alt="Farmer Signature" class="max-h-32">
                            <?php else: ?>
                                <p class="text-gray-400">No signature available</p>
                            <?php endif; ?>
                        </div>
                        <p class="font-semibold"><?php echo htmlspecialchars($agreement['farmer_name']); ?></p>
                        <p class="text-sm text-gray-600">Farmer (Second Party)</p>
                        <p class="text-xs text-gray-500">Signed: <?php echo date('M d, Y H:i', strtotime($agreement['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Agreement Dates -->
            <div class="border-t pt-6 mt-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <p><span class="font-semibold">Agreement Created:</span> <?php echo date('F d, Y H:i', strtotime($agreement['created_at'])); ?></p>
                    <p><span class="font-semibold">Last Updated:</span> <?php echo date('F d, Y H:i', strtotime($agreement['updated_at'])); ?></p>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-8 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <p class="text-sm text-yellow-800">
                    <strong>Note:</strong> This is a legally binding agreement between the parties mentioned above.
                    Any modification or termination must be mutually agreed upon in writing and approved by DirectEdge platform administrators.
                </p>
            </div>
        </div>

    </div>

    <script>
        function downloadPDF() {
            alert('Please use your browser\'s Print function and select "Save as PDF" as the printer option.');
            window.print();
        }
    </script>
</body>

</html>
<?php
ob_end_flush();
?>