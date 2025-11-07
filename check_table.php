<?php
require 'includes/db.php';

try {
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Orders table columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

    echo "\nSample data:\n";
    $stmt = $pdo->query("SELECT * FROM orders LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        foreach ($sample as $key => $value) {
            echo "$key: $value\n";
        }
    } else {
        echo "No data found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
