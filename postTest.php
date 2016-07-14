<?php
$data = file_get_contents("php://input");

echo "ISSET - " . (isset($data)? "TRUE":"FALSE");
echo "<br>";
var_dump($data);
echo "<br>";
$data = json_decode($data);


var_dump($data);
echo "<br>";

echo "ISSET - " . (isset($data)? "TRUE":"FALSE");
