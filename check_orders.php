<?php
require 'includes/db.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total orders: " . $result['total'] . "\n";

    if ($result['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM orders LIMIT 5");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample orders:\n";
        foreach ($orders as $order) {
            echo "ID: " . $order['id'] . ", Name: " . $order['customer_name'] . ", Total: " . $order['total'] . "\n";
        }
    } else {
        echo "No orders found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
