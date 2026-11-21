<?php

if(isset($_GET["id"]) && !empty($_GET["id"])){
    $id = $_GET["id"];
    require_once('config.php');
    $query= "delete from achievements where id = '$id'";
    $result = mysqli_query($conn,$query);
   $i = $_SERVER['PHP_SELF'];
    echo "<script>window.location.href='$i?page=20';</script>";

}

?>