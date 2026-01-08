<?php
// farmer-sign.php
// URL Example: yoursite.com/agent-app/farmer-sign.php?ref=AGR-2026-XXXX

include '../include/connect-db.php';

$ref = isset($_GET['ref']) ? $_GET['ref'] : '';

// Fetch Agreement Info
$query = "SELECT afa.*, f.full_name as farmer_name, u.full_name as agent_name 
          FROM agent_farmer_agreements afa
          JOIN farmers f ON afa.farmer_id = f.id
          JOIN users u ON afa.agent_id = u.user_id
          WHERE afa.agreement_reference = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $ref);
$stmt->execute();
$agreement = $stmt->get_result()->fetch_assoc();

if (!$agreement) {
    die("<div style='text-align:center; padding:50px;'><h2>Invalid or Expired Link</h2></div>");
}

$already_signed = !empty($agreement['farmer_signature_url']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Agreement - Farmer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">

    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-xl overflow-hidden">
        
        <div class="bg-green-700 p-6 text-white text-center">
            <h1 class="text-2xl font-bold">Review & Sign Agreement</h1>
            <p class="text-green-100 text-sm mt-1">Ref: <?php echo htmlspecialchars($agreement['agreement_reference']); ?></p>
        </div>

        <div class="p-8">
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 text-sm text-blue-800">
                <p><strong>Agent:</strong> <?php echo htmlspecialchars($agreement['agent_name']); ?></p>
                <p><strong>Farmer:</strong> <?php echo htmlspecialchars($agreement['farmer_name']); ?></p>
                <p class="mt-2 text-gray-600">Please review the terms and provide your signature below to activate the agreement.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase mb-2">Agent Signature</p>
                    <div class="border rounded bg-gray-50 h-40 flex items-center justify-center p-2">
                        <?php if($agreement['agent_signature_url']): ?>
                            <img src="<?php echo htmlspecialchars($agreement['agent_signature_url']); ?>" class="max-h-36 max-w-full">
                        <?php else: ?>
                            <span class="text-gray-400">Not Signed</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-bold text-green-600 uppercase mb-2">Your Signature</p>
                    
                    <?php if($already_signed): ?>
                        <div class="border rounded bg-green-50 h-40 flex items-center justify-center p-2 border-green-200">
                             <img src="<?php echo htmlspecialchars($agreement['farmer_signature_url']); ?>" class="max-h-36 max-w-full">
                        </div>
                        <p class="text-center text-green-600 text-xs mt-2 font-bold">ALREADY SIGNED</p>
                    <?php else: ?>
                        <canvas id="signCanvas" class="border-2 border-dashed border-green-500 rounded bg-white w-full h-40 cursor-crosshair touch-none"></canvas>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-400">Draw above</span>
                            <button onclick="signaturePad.clear()" class="text-red-500 text-xs font-bold hover:underline">CLEAR</button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <?php if(!$already_signed): ?>
                <button onclick="submitSign()" class="w-full mt-8 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow-lg transition transform hover:scale-[1.01]">
                    Confirm & Activate Agreement
                </button>
            <?php else: ?>
                <div class="mt-8 text-center text-gray-500 font-bold">
                    This agreement is Active.
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if(!$already_signed): ?>
    <script>
        const canvas = document.getElementById('signCanvas');
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        resizeCanvas();
        
        const signaturePad = new SignaturePad(canvas, {
            penColor: "rgb(0, 0, 0)"
        });

        function submitSign() {
            if(signaturePad.isEmpty()) {
                alert("Please draw your signature first!");
                return;
            }

            const data = {
                ref: '<?php echo $ref; ?>',
                image: signaturePad.toDataURL()
            };

            // Disable button
            document.querySelector('button').innerText = "Processing...";
            document.querySelector('button').disabled = true;

            fetch('process-farmer-sign.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert("Signed Successfully!");
                    window.location.reload();
                } else {
                    alert("Error: " + data.message);
                    document.querySelector('button').innerText = "Confirm & Activate Agreement";
                    document.querySelector('button').disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert("Something went wrong");
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>