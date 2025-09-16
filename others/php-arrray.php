<?php
include '../include/connect-db.php'; // database connection


//====================================================================
//==========================  style 1  ===============================
//====================================================================


$sql = "SELECT warehouse_id, name, location, capacity_total, capacity_used, status, type
        FROM `warehouses` WHERE 1";

$result = mysqli_query($conn, $sql);

// Fetch all rows as numeric arrays
$allwarehouses = mysqli_fetch_all($result);

// Print all warehouses
echo "<pre>";
print_r($allwarehouses);
echo "</pre>";

// Print first row
echo "<pre>";
print_r($allwarehouses[0]);
echo "</pre>";

// Print first column of first row (warehouse_id)
echo $allwarehouses[0][0];



//====================================================================
//==========================  style 2  ===============================
//====================================================================

$sql = "SELECT warehouse_id, name, location, capacity_total, capacity_used, status, type
        FROM `warehouses` WHERE 1";

$result = mysqli_query($conn, $sql);

// Fetch all rows as associative arrays
$allwarehouses = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Print all warehouses
echo "<pre>";
print_r($allwarehouses);
echo "</pre>";

// Print first row
echo "<pre>";
print_r($allwarehouses[0]);
echo "</pre>";

// Print specific column of first row
echo $allwarehouses[0]['name'];


