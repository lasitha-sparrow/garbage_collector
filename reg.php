<?php
session_start();
$user = $_POST["email"];
$password = $_POST["pass"];
$connection = mysqli_connect("localhost", 'root', '','maps');
$login = mysqli_query($connection, "INSERT INTO `maps`.`user`(`username`,`password`) VALUES ('$user','$password');");
$id = "";
if ($login != null) {
    $id = mysqli_fetch_assoc($login)["id"];
}

if (!empty($id)) {
    $_SESSION["logSession"] = "logged";
    header("Location:map.php");
} else {
    header("Location:index.php?error=error");
}