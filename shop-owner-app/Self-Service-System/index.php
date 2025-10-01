<?php
require_once 'config.php';

// Simple router
$request = $_SERVER['REQUEST_URI'];

switch ($request) {
    case '/':
    case '/home':
        require_once 'controllers/HomeController.php';
        $controller = new HomeController();
        $controller->index();
        break;
    case '/products':
        require_once 'controllers/ProductController.php';
        $controller = new ProductController();
        $controller->index();
        break;
    case '/submit-order':
        require_once 'controllers/OrderController.php';
        $controller = new OrderController();
        $controller->submitOrder();
        break;
    case '/admin':
        require_once 'controllers/OrderController.php';
        $controller = new OrderController();
        $controller->adminOrders();
        break;
    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
