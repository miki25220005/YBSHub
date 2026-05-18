<?php
$connect=mysqli_connect('localhost','root','','YBSHub');
$res = mysqli_query($connect, 'DESCRIBE route_gate');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
