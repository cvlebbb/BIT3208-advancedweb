<?php require_once 'config.php';
$id=$_GET['id'];
$res=$conn->query("SELECT * FROM books WHERE id=$id");
echo json_encode($res->fetch_assoc());
?>