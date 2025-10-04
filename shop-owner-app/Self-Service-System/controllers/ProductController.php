<?php
require_once 'models/Product.php';

class ProductController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function index()
    {
        if (!isset($_POST['user_name'])) {
            header('Location: /');
            exit;
        }
        $user_name = $_POST['user_name'];
        $productModel = new Product($this->db);
        $products = $productModel->getAll();
        require_once 'views/products.php';
        renderProducts($user_name, $products);
    }
}
