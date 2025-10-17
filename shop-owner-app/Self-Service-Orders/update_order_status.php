<?php
include '../../include/connect-db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['orderId'], $data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$orderId = (int)$data['orderId'];
$status = $data['status'];

// Only allow specific statuses
$allowedStatuses = ['In queue', 'Running', 'Done', 'Cancelled'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update the database
$sql = "UPDATE self_service_orders SET status = ? WHERE order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $orderId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
