<?php
$servername = "localhost";
$username = "gbluser";
$password = "PineappleFunk40";
$dbname = "awesominds";
 
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
 
$sql = "SELECT game_id, game_score, high_score FROM score";
$result = mysqli_query($conn, $sql);
 
if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        echo "game_id: " . $row["game_id"]. " - Game Score: " . $row["game_score"]. " " . $row["high_score"]. "<br>";
    }
} else {
    echo "0 results";
}
 
mysqli_close($conn);
?>

