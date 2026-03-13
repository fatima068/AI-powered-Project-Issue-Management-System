<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "project_issue_tracking";
$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully";

// //MySQLi Procedural
// mysqli_close($conn);

?>