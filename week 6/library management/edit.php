<?php
include 'config.php';
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM books WHERE id=$id");
$books = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title']; $author = $_POST['author']; $category = $_POST['category'];
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=? WHERE id=?");
    $stmt->bind_param("sssii", $title, $author, $category, $id);
    if ($stmt->execute()) header("Location: index.php");
}
?>
<!DOCTYPE html>
<html>
<head><title>Edit Book</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container mt-5">
<div class="card shadow">
    <div class="card-header bg-warning"><h3>Edit book</h3></div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" value="<?= $books['title'] ?>" required></div>
            <div class="mb-3"><label>Author</label><input type="text" name="author" class="form-control" value="<?= $books['author'] ?>" required></div>
            <div class="mb-3"><label>Category</label><input type="text" name="category" class="form-control" value="<?= $books['category'] ?>" required></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>
</body>
</html>