<?php
require_once 'models/Order.php';
require_once 'models/Product.php'; // Ensure Product model is included

class OrderController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function submitOrder()
    {
        if (!isset($_POST['user_name']) || !isset($_POST['products'])) {
            header('Location: /');
            exit;
        }
        $user_name = $_POST['user_name'];
        $selected_products = [];
        foreach ($_POST['products'] as $id => $quantity) {
            if ($quantity > 0) {
                $selected_products[] = ['id' => $id, 'quantity' => $quantity];
            }
        }
        if (empty($selected_products)) {
            header('Location: /products');
            exit;
        }
        $orderModel = new Order($this->db);
        $code = $orderModel->create($user_name, $selected_products);

        // Fetch product names for the selected products
        $productModel = new Product($this->db);
        $all_products = $productModel->getAll();
        $product_map = [];
        foreach ($all_products as $p) {
            $product_map[$p['id']] = $p['name'];
        }

        require_once 'views/order_confirmation.php';
        renderOrderConfirmation($code, $selected_products, $product_map); // Pass product_map
    }

    public function adminOrders()
    {
        $orderModel = new Order($this->db);
        if (isset($_POST['deliver'])) {
            $orderModel->markAsDelivered($_POST['deliver']);
        }
        $orders = $orderModel->getAll();
        require_once 'views/admin_orders.php';
        renderAdminOrders($orders);
    }
}
