<?php
class Order
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user_name, $products)
    {
        // Generate unique code, e.g., A12
        $letter = chr(rand(65, 90));  // A-Z
        $number = rand(10, 99);       // 10-99
        $code = $letter . $number;

        // Check uniqueness (simple, for demo)
        $check = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE code = ?");
        $check->execute([$code]);
        while ($check->fetchColumn() > 0) {
            $letter = chr(rand(65, 90));
            $number = rand(10, 99);
            $code = $letter . $number;
            $check->execute([$code]);
        }

        $products_json = json_encode($products);
        $insert = $this->db->prepare("INSERT INTO orders (user_name, products, code) VALUES (?, ?, ?)");
        $insert->execute([$user_name, $products_json, $code]);
        return $code;
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM orders WHERE status = 'pending'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsDelivered($id)
    {
        $update = $this->db->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
        $update->execute([$id]);
    }
}
