<?php
include 'config.php';
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM students WHERE id=$id");
$student = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name']; $email = $_POST['email']; $course = $_POST['course'];
    $stmt = $conn->prepare("UPDATE students SET name=?, email=?, course=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $course, $id);
    if ($stmt->execute()) header("Location: index.php");
}
?>
<!DOCTYPE html>
<html>
<head><title>Edit Student</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container mt-5">
<div class="card shadow">
    <div class="card-header bg-warning"><h3>Edit Student</h3></div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" value="<?= $student['name'] ?>" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?= $student['email'] ?>" required></div>
            <div class="mb-3"><label>Course</label><input type="text" name="course" class="form-control" value="<?= $student['course'] ?>" required></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>
</body>
</html>