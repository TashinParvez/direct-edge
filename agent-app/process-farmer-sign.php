<?php
// process-farmer-sign.php
include '../include/connect-db.php';
header('Content-Type: application/json');

// Get JSON Input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image']) || !isset($data['ref'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$ref = $data['ref'];
$image_parts = explode(";base64,", $data['image']);

if (count($image_parts) < 2) {
    echo json_encode(['success' => false, 'message' => 'Invalid image data']);
    exit;
}

$image_base64 = base64_decode($image_parts[1]);

// Ensure directory exists
if (!file_exists('uploads/signatures')) {
    mkdir('uploads/signatures', 0777, true);
}

// Generate Filename
$file_name = 'uploads/signatures/farmer_signed_' . time() . '_' . uniqid() . '.png';

// Save File
if (file_put_contents($file_name, $image_base64)) {
    
    // Update Database: Set Signature URL AND Status to Active
    $sql = "UPDATE agent_farmer_agreements 
            SET farmer_signature_url = ?, 
                agreement_status = 'Active', 
                terms_accepted_at = NOW(),
                updated_at = NOW() 
            WHERE agreement_reference = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $file_name, $ref);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save signature image']);
}
?>