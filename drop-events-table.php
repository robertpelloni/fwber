<?php
// drop-events-table.php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=fwber', 'fwber', 'Temppass0!');
$pdo->exec('DROP TABLE IF EXISTS events');
echo "Events table dropped successfully\n";
?>
