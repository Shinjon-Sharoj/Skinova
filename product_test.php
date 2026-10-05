<?php

require_once "Config/Database.php";

$sql = "SELECT * FROM products";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {
        echo $row["product_name"] . "<br>";
    }

} else {
    echo "No products found.";
}

?>