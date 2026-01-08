<?php
ob_start();
session_start();
include '../Include/SidebarAgent.php';
?>
<link rel="stylesheet" href="../Include/sidebar.css">

<?php
include '../include/connect-db.php';

$agent_id = $_SESSION['user_id'];
$farmer_id = isset($_GET['farmer_id']) ? intval($_GET['farmer_id']) : 0;

// Fetch Agent Info
$agent_query = "SELECT u.*, ai.* FROM users u 
                JOIN agent_info ai ON u.user_id = ai.agent_id 
                WHERE u.user_id = ? AND u.role = 'Agent'";
$agent_stmt = $conn->prepare($agent_query);
$agent_stmt->bind_param("i", $agent_id);
$agent_stmt->execute();
$agent = $agent_stmt->get_result()->fetch_assoc();

if (!$agent) { die("Error: Agent not found."); }

// Handle Form Submission (Final Step)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Security: Check if OTP was verified in session
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        die("Error: OTP Verification Failed. Please try again.");
    }

    $farmer_id = intval($_POST['farmer_id']);
    $commission_percentage = floatval($_POST['commission_percentage']);
    $exclusive_rights = isset($_POST['exclusive_rights']) ? 1 : 0;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $payment_terms = $_POST['payment_terms'];
    
    $agent_signature = $_POST['agent_signature'];
    $farmer_signature = $_POST['farmer_signature'];

    $agreement_reference = 'AGR-' . date('Y') . '-' . str_pad($agent_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($farmer_id, 4, '0', STR_PAD_LEFT) . '-' . time();

    if (!file_exists('uploads/signatures')) { mkdir('uploads/signatures', 0777, true); }

    // Save Signatures
    $agent_sig_path = 'uploads/signatures/agent_' . $agent_id . '_' . time() . '.png';
    file_put_contents($agent_sig_path, base64_decode(str_replace('data:image/png;base64,', '', $agent_signature)));

    $farmer_sig_path = 'uploads/signatures/farmer_' . $farmer_id . '_' . time() . '.png';
    file_put_contents($farmer_sig_path, base64_decode(str_replace('data:image/png;base64,', '', $farmer_signature)));

    // Insert into DB (Status: Active because OTP verified)
    $insert_query = "INSERT INTO agent_farmer_agreements 
                    (agent_id, farmer_id, agreement_reference, commission_percentage, 
                     payment_terms, exclusive_rights, start_date, end_date, 
                     agreement_status, agent_signature_url, farmer_signature_url, 
                     terms_accepted_at, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iisdsissss", $agent_id, $farmer_id, $agreement_reference, $commission_percentage, $payment_terms, $exclusive_rights, $start_date, $end_date, $agent_sig_path, $farmer_sig_path);

    if ($stmt->execute()) {
        // Reset OTP Session
        unset($_SESSION['otp_verified']);
        unset($_SESSION['current_otp']);
        
        header("Location: agreement_success.php?ref=" . $agreement_reference);
        exit();
    } else {
        $error_message = "Failed: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Agreement - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        .signature-pad { border: 2px solid #d1d5db; border-radius: 0.5rem; background-color: white; width: 100%; }
        /* Modal Styles */
        #otpModal { background-color: rgba(0,0,0,0.5); }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold mb-2">Partnership Agreement</h1>
            <p class="text-green-100">Secure On-Spot Signing</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-6"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" id="agreementForm">
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Party Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-green-700">Agent Details</h3>
                        <p>Name: <?php echo htmlspecialchars($agent['full_name']); ?></p>
                        <p>ID: <?php echo htmlspecialchars($agent['agent_id']); ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-green-700 mb-2">Select Farmer</h3>
                        <select name="farmer_id" id="farmerSelect" required class="w-full border rounded p-2 focus:ring-2 focus:ring-green-500">
                            <option value="">-- Choose Farmer --</option>
                            <?php
                            $farmers_query = "SELECT * FROM farmers WHERE agent_id = ?";
                            $farmers_stmt = $conn->prepare($farmers_query);
                            $farmers_stmt->bind_param("i", $agent_id);
                            $farmers_stmt->execute();
                            $res = $farmers_stmt->get_result();
                            while ($f = $res->fetch_assoc()) {
                                echo "<option value='{$f['id']}'>{$f['full_name']} ({$f['contact_number']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Terms & Duration</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Commission (%)</label>
                        <input type="number" name="commission_percentage" value="10.00" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" class="w-full border rounded p-2" required>
                    </div>
                    <div class="md:col-span-2">
                        <label>Payment Terms</label>
                        <textarea name="payment_terms" class="w-full border rounded p-2" rows="2" required>Payment processed within 7 days.</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" class="w-full border rounded p-2" required>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Digital Signatures</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Agent Signature <span class="text-red-500">*</span></h3>
                        <canvas id="agentSignaturePad" class="signature-pad" height="200"></canvas>
                        <button type="button" onclick="agentPad.clear()" class="text-red-500 text-sm mt-1">Clear</button>
                        <input type="hidden" name="agent_signature" id="agentSignatureData">
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Farmer Signature <span class="text-red-500">*</span></h3>
                        <canvas id="farmerSignaturePad" class="signature-pad" height="200"></canvas>
                        <button type="button" onclick="farmerPad.clear()" class="text-red-500 text-sm mt-1">Clear</button>
                        <input type="hidden" name="farmer_signature" id="farmerSignatureData">
                        <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle"></i> Farmer must sign here in person.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <button type="button" onclick="initiateSubmission()" class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-lg">
                    Verify OTP & Submit Agreement
                </button>
            </div>
        </form>
    </div>

    <div id="otpModal" class="fixed inset-0 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full shadow-2xl transform transition-all">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-mobile-alt text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Farmer Verification</h3>
                <p class="text-sm text-gray-500 mt-2">An OTP has been sent to the farmer's mobile number. Please enter it below to confirm submission.</p>
                
                <div class="mt-4">
                    <input type="text" id="otpInput" placeholder="Enter 6-digit OTP" class="text-center text-2xl tracking-widest w-full border-2 border-green-500 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                
                <div id="otpMessage" class="text-sm mt-2 font-semibold"></div>

                <div class="mt-6 flex gap-3">
                    <button onclick="closeOtpModal()" class="w-full px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancel</button>
                    <button onclick="verifyOtp()" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-bold">Confirm & Submit</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Resize Canvas Logic
        function resizeCanvas(c) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            c.width = c.offsetWidth * ratio;
            c.height = c.offsetHeight * ratio;
            c.getContext("2d").scale(ratio, ratio);
        }

        const agentCanvas = document.getElementById('agentSignaturePad');
        const farmerCanvas = document.getElementById('farmerSignaturePad');
        resizeCanvas(agentCanvas);
        resizeCanvas(farmerCanvas);

        const agentPad = new SignaturePad(agentCanvas);
        const farmerPad = new SignaturePad(farmerCanvas);

        // Step 1: Initiate Submission
        function initiateSubmission() {
            // Validation
            const farmerId = document.getElementById('farmerSelect').value;
            
            if (!farmerId) { alert('Please select a farmer.'); return; }
            if (agentPad.isEmpty()) { alert('Agent signature is missing.'); return; }
            if (farmerPad.isEmpty()) { alert('Farmer signature is missing.'); return; }

            // Show Loading State (Optional)
            
            // Send OTP via AJAX
            const formData = new FormData();
            formData.append('farmer_id', farmerId);

            fetch('send_otp.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Show OTP Modal
                    document.getElementById('otpModal').classList.remove('hidden');
                    document.getElementById('otpMessage').innerText = data.message;
                    document.getElementById('otpMessage').className = "text-sm mt-2 text-green-600";
                } else {
                    alert('Error sending OTP: ' + data.message);
                }
            })
            .catch(err => alert('System Error: ' + err));
        }

        // Step 2: Verify OTP
        function verifyOtp() {
            const otp = document.getElementById('otpInput').value;
            if (otp.length < 4) { alert('Please enter valid OTP'); return; }

            const formData = new FormData();
            formData.append('otp', otp);

            fetch('verify_otp.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Success! Submit the main form
                    document.getElementById('agentSignatureData').value = agentPad.toDataURL();
                    document.getElementById('farmerSignatureData').value = farmerPad.toDataURL();
                    
                    document.getElementById('agreementForm').submit();
                } else {
                    document.getElementById('otpMessage').innerText = "Invalid OTP. Try again.";
                    document.getElementById('otpMessage').className = "text-sm mt-2 text-red-600";
                }
            });
        }

        function closeOtpModal() {
            document.getElementById('otpModal').classList.add('hidden');
        }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>