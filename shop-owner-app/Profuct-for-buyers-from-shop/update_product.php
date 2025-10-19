<?php
session_start();
include '../../include/connect-db.php';

header('Content-Type: application/json');

// Get the action and data from POST
$action = $_POST['action'] ?? '';
$product_id = $_POST['product_id'] ?? 0;
$shop_id = $_POST['shop_id'] ?? 0;

// Validate inputs
if (empty($action) || empty($product_id) || empty($shop_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    if ($action === 'update') {
        $selling_price = $_POST['selling_price'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;

        // Validate numeric values
        if ($selling_price <= 0 || $quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid price or quantity']);
            exit();
        }

        // Update the shop_products table
        $sql = "UPDATE shop_products 
                SET selling_price = ?, quantity = ? 
                WHERE shop_id = ? AND product_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddii", $selling_price, $quantity, $shop_id, $product_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made or product not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }

        $stmt->close();
    } elseif ($action === 'delete') {
        // Delete the product from shop_products table
        $sql = "DELETE FROM shop_products WHERE shop_id = ? AND product_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $shop_id, $product_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
