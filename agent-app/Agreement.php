<?php
// Start output buffering BEFORE any includes
ob_start();

include '../include/navbar.php';
$agent_id = isset($user_id);

// connect database
include '../include/connect-db.php';

$agent_id = $_SESSION['user_id'];
$farmer_id = isset($_GET['farmer_id']) ? intval($_GET['farmer_id']) : 0;

// FIXED: Use agent_info (no underscore) - this is correct based on your database
$agent_query = "SELECT u.*, ai.* FROM users u 
                JOIN agent_info ai ON u.user_id = ai.agent_id 
                WHERE u.user_id = ? AND u.role = 'Agent'";
$agent_stmt = $conn->prepare($agent_query);
$agent_stmt->bind_param("i", $agent_id);
$agent_stmt->execute();
$agent_result = $agent_stmt->get_result();
$agent = $agent_result->fetch_assoc();

// Check if agent exists
if (!$agent) {
    die("Error: Agent not found or you don't have permission to access this page.");
}

// Fetch farmer information if farmer_id is provided
$farmer = null;
if ($farmer_id > 0) {
    $farmer_query = "SELECT * FROM farmers WHERE id = ?";
    $farmer_stmt = $conn->prepare($farmer_query);
    $farmer_stmt->bind_param("i", $farmer_id);
    $farmer_stmt->execute();
    $farmer_result = $farmer_stmt->get_result();
    $farmer = $farmer_result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farmer_id = intval($_POST['farmer_id']);
    $commission_percentage = floatval($_POST['commission_percentage']);
    $exclusive_rights = isset($_POST['exclusive_rights']) ? 1 : 0;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $payment_terms = $_POST['payment_terms'];
    $agent_signature = $_POST['agent_signature'];
    $farmer_signature = $_POST['farmer_signature'];

    // Generate unique agreement reference
    $agreement_reference = 'AGR-' . date('Y') . '-' . str_pad($agent_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($farmer_id, 4, '0', STR_PAD_LEFT) . '-' . time();

    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads/signatures')) {
        mkdir('uploads/signatures', 0777, true);
    }

    // Save signatures as images
    $agent_sig_path = 'uploads/signatures/agent_' . $agent_id . '_' . time() . '.png';
    $farmer_sig_path = 'uploads/signatures/farmer_' . $farmer_id . '_' . time() . '.png';

    // Decode and save signatures
    $agent_sig_data = str_replace('data:image/png;base64,', '', $agent_signature);
    $farmer_sig_data = str_replace('data:image/png;base64,', '', $farmer_signature);
    file_put_contents($agent_sig_path, base64_decode($agent_sig_data));
    file_put_contents($farmer_sig_path, base64_decode($farmer_sig_data));

    // Insert agreement into database
    $insert_query = "INSERT INTO agent_farmer_agreements 
                    (agent_id, farmer_id, agreement_reference, commission_percentage, 
                     payment_terms, exclusive_rights, start_date, end_date, 
                     agreement_status, agent_signature_url, farmer_signature_url, 
                     terms_accepted_at, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param(
        "iisdsissss",
        $agent_id,
        $farmer_id,
        $agreement_reference,
        $commission_percentage,
        $payment_terms,
        $exclusive_rights,
        $start_date,
        $end_date,
        $agent_sig_path,
        $farmer_sig_path
    );

    if ($stmt->execute()) {
        // FIXED: Your actual table uses agent_id (with underscore)
        $update_farmer = "UPDATE farmers SET agent_id = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_farmer);
        $update_stmt->bind_param("ii", $agent_id, $farmer_id);
        $update_stmt->execute();

        // Clear output buffer before redirect
        ob_end_clean();

        // Redirect to success page
        header("Location: agreement_success.php?ref=" . $agreement_reference);
        exit();
    } else {
        $error_message = "Failed to create agreement. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent-Farmer Agreement - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        .signature-pad {
            border: 2px solid #d1d5db;
            border-radius: 0.5rem;
            background-color: white;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold mb-2">Agent-Farmer Partnership Agreement</h1>
            <p class="text-green-100">DirectEdge Agricultural Supply Chain Platform</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Progress Indicator -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">1</div>
                        <div class="flex-1 h-1 bg-green-600 mx-2"></div>
                    </div>
                    <p class="text-sm mt-2 text-green-600 font-semibold">Agreement Draft</p>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">2</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
                    </div>
                    <p class="text-sm mt-2 text-gray-500">Review</p>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">3</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
                    </div>
                    <p class="text-sm mt-2 text-gray-500">Signature</p>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">4</div>
                    </div>
                    <p class="text-sm mt-2 text-gray-500">Active</p>
                </div>
            </div>
        </div>

        <form method="POST" id="agreementForm">
            <!-- Party Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Party Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Agent Information -->
                    <div class="border-r pr-6">
                        <h3 class="text-lg font-semibold text-green-700 mb-3">Agent Details</h3>
                        <div class="space-y-2">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($agent['full_name'] ?? 'N/A'); ?></p>
                            <p><strong>NID:</strong> <?php echo htmlspecialchars($agent['nid_number'] ?? 'N/A'); ?></p>
                            <p><strong>Region:</strong> <?php echo htmlspecialchars($agent['region'] ?? 'N/A'); ?></p>
                            <p><strong>District:</strong> <?php echo htmlspecialchars($agent['district'] ?? 'N/A'); ?></p>
                            <p><strong>Experience:</strong> <?php echo $agent['experience_years'] ?? 0; ?> years</p>
                            <p><strong>Expertise:</strong> <?php echo htmlspecialchars($agent['crops_expertise'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- Farmer Selection/Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-green-700 mb-3">Farmer Details</h3>
                        <?php if ($farmer): ?>
                            <div class="space-y-2">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($farmer['full_name']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($farmer['contact_number']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($farmer['present_address']); ?></p>
                                <p><strong>Land Size:</strong> <?php echo $farmer['land_size']; ?> acres</p>
                                <p><strong>Crops:</strong> <?php echo htmlspecialchars($farmer['crops_cultivated']); ?></p>
                                <p><strong>Type:</strong> <?php echo $farmer['farmer_type']; ?></p>
                                <input type="hidden" name="farmer_id" value="<?php echo $farmer_id; ?>">
                            </div>
                        <?php else: ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Farmer</label>
                                <select name="farmer_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                    <option value="">-- Select Farmer --</option>
                                    <?php
                                    // FIXED: Show only farmers added by this specific agent
                                    $farmers_query = "SELECT * FROM farmers WHERE agent_id = ?";
                                    $farmers_stmt = $conn->prepare($farmers_query);
                                    $farmers_stmt->bind_param("i", $agent_id);
                                    $farmers_stmt->execute();
                                    $farmers_result = $farmers_stmt->get_result();

                                    if ($farmers_result && $farmers_result->num_rows > 0) {
                                        while ($f = $farmers_result->fetch_assoc()):
                                    ?>
                                            <option value="<?php echo $f['id']; ?>">
                                                <?php echo htmlspecialchars($f['full_name']) . ' - ' . $f['land_size'] . ' acres'; ?>
                                            </option>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo '<option value="">No farmers added by you</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Agreement Terms</h2>

                <!-- Financial Terms -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Financial Terms</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Commission Percentage (%)</label>
                            <input type="number" name="commission_percentage" step="0.01" min="0" max="50" value="10.00" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 mt-1">Standard rate: 10%</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <input type="checkbox" name="exclusive_rights" class="mr-2">
                                Exclusive Selling Rights
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Agent has exclusive rights to sell farmer's produce</p>
                        </div>
                    </div>
                </div>

                <!-- Duration -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Agreement Duration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" required
                                value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="date" name="end_date" required
                                value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>

                <!-- Payment Terms -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Payment Terms</h3>
                    <textarea name="payment_terms" rows="4" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                        placeholder="Describe payment schedule, methods, and conditions...">Payment will be processed within 7 days of crop delivery. Commission will be deducted before final payment to farmer. Payment method: Bank transfer or Mobile Banking as per farmer's preference.</textarea>
                </div>

                <!-- Responsibilities -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Responsibilities</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">Agent Responsibilities</h4>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                <li>Quality verification of crops before listing</li>
                                <li>Warehouse coordination and logistics</li>
                                <li>Market linkage and price negotiation</li>
                                <li>Timely payment processing</li>
                                <li>Technical guidance to farmer</li>
                            </ul>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-blue-800 mb-2">Farmer Responsibilities</h4>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                <li>Maintain crop quality standards</li>
                                <li>Follow cultivation best practices</li>
                                <li>Provide accurate crop information</li>
                                <li>Ensure harvest availability</li>
                                <li>Grant access for quality inspections</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Terms Acknowledgment -->
                <div class="border-t pt-4">
                    <label class="flex items-start">
                        <input type="checkbox" required class="mt-1 mr-3">
                        <span class="text-sm text-gray-700">
                            I acknowledge that I have read and understood all terms and conditions of this agreement.
                            I agree to abide by the responsibilities outlined and understand that violation may result in termination.
                        </span>
                    </label>
                </div>
            </div>

            <!-- Digital Signatures -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Digital Signatures</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Agent Signature -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Agent Signature</h3>
                        <canvas id="agentSignaturePad" class="signature-pad" width="400" height="200"></canvas>
                        <div class="mt-2 flex gap-2">
                            <button type="button" onclick="clearAgentSignature()"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                Clear
                            </button>
                        </div>
                        <input type="hidden" name="agent_signature" id="agentSignatureData">
                        <p class="text-xs text-gray-500 mt-2">Signed by: <?php echo htmlspecialchars($agent['full_name'] ?? 'N/A'); ?></p>
                        <p class="text-xs text-gray-500">Date: <?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>

                    <!-- Farmer Signature -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Farmer Signature</h3>
                        <canvas id="farmerSignaturePad" class="signature-pad" width="400" height="200"></canvas>
                        <div class="mt-2 flex gap-2">
                            <button type="button" onclick="clearFarmerSignature()"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                Clear
                            </button>
                        </div>
                        <input type="hidden" name="farmer_signature" id="farmerSignatureData">
                        <p class="text-xs text-gray-500 mt-2">Farmer will sign upon review</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-4">
                <a href="agent_dashboard.php"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-semibold">
                    Cancel
                </a>
                <button type="submit"
                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    Submit Agreement for Review
                </button>
            </div>
        </form>
    </div>

    <script>
        // Initialize signature pads
        const agentCanvas = document.getElementById('agentSignaturePad');
        const farmerCanvas = document.getElementById('farmerSignaturePad');
        const agentPad = new SignaturePad(agentCanvas);
        const farmerPad = new SignaturePad(farmerCanvas);

        function clearAgentSignature() {
            agentPad.clear();
        }

        function clearFarmerSignature() {
            farmerPad.clear();
        }

        // Form submission validation
        document.getElementById('agreementForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (agentPad.isEmpty()) {
                alert('Please provide agent signature');
                return false;
            }

            if (farmerPad.isEmpty()) {
                alert('Please provide farmer signature');
                return false;
            }

            // Save signature data
            document.getElementById('agentSignatureData').value = agentPad.toDataURL();
            document.getElementById('farmerSignatureData').value = farmerPad.toDataURL();

            // Submit form
            this.submit();
        });
    </script>
</body>

</html>
<?php
// Flush output buffer at the end
ob_end_flush();
?>