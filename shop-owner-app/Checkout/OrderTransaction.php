<?php
class OrderTransaction
{
    private $conn;

    public function __construct()
    {
        // Prefer an existing global connection; otherwise include it (from project root /include/connect-db.php)
        if (!isset($GLOBALS['conn']) || !$GLOBALS['conn']) {
            $includePath = dirname(__DIR__, 2) . '/include/connect-db.php'; // ../.. from Checkout to project root
            if (file_exists($includePath)) {
                include_once $includePath;
            }
        }

        if (isset($GLOBALS['conn']) && $GLOBALS['conn']) {
            $this->conn = $GLOBALS['conn'];
        } else {
            die("Database connection failed.");
        }
    }

    // Build a query to fetch an order by transaction id
    public function getRecordQuery($transactionId)
    {
        $safeTranId = mysqli_real_escape_string($this->conn, $transactionId);
        return "SELECT * FROM orders WHERE tran_id = '" . $safeTranId . "' LIMIT 1";
    }

    // Build a query to update order status and payment_status by transaction id
    public function updateTransactionQuery($transactionId, $status)
    {
        $safeTranId = mysqli_real_escape_string($this->conn, $transactionId);
        $safeStatus = mysqli_real_escape_string($this->conn, $status);
        return "UPDATE orders SET payment_status = '" . $safeStatus . "' WHERE tran_id = '" . $safeTranId . "'";
    }
}
