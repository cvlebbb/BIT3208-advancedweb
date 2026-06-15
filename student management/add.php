<?php
include 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $course = $_POST['course'];
    $stmt = $conn->prepare("INSERT INTO students (name, email, course) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $course);
    if ($stmt->execute()) header("Location: index.php");
    else echo "Error: " . $conn->error;
}
?>
<!DOCTYPE html>
<html>
<head><title>Add Student</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container mt-5">
<div class="card shadow">
    <div class="card-header bg-success text-white"><h3>Add New Student</h3></div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label>Course</label><input type="text" name="course" class="form-control" required></div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>