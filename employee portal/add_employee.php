<?php include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    if (empty($_POST['name'])) $errors[] = "Name required";
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required";
    if ($_POST['salary'] <= 0) $errors[] = "Salary must be positive";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO employees (name, email, position, salary, hire_date) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssds", $_POST['name'], $_POST['email'], $_POST['position'], $_POST['salary'], $_POST['hire_date']);
        if ($stmt->execute()) header("Location: index.php");
        else $errors[] = "Database error";
    }
}
?>
<!DOCTYPE html>
<html><head><title>Add Employee</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/css/bootstrap.min.css" rel="stylesheet">
<script>function validateForm(){let sal=document.getElementById('salary').value;if(sal<=0){alert('Salary must be >0');return false;}return true;}</script></head>
<body class="container mt-5"><div class="card shadow"><div class="card-header bg-success text-white"><h3>Add Employee</h3></div><div class="card-body">
<?php if(!empty($errors)) foreach($errors as $e) echo "<div class='alert alert-danger'>$e</div>"; ?>
<form method="POST" onsubmit="return validateForm()"><div class="mb-3"><label>Name*</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-3"><label>Email*</label><input type="email" name="email" class="form-control" required></div>
<div class="mb-3"><label>Position</label><input type="text" name="position" class="form-control"></div>
<div class="mb-3"><label>Salary*</label><input type="number" step="0.01" id="salary" name="salary" class="form-control" required></div>
<div class="mb-3"><label>Hire Date</label><input type="date" name="hire_date" class="form-control"></div>
<button type="submit" class="btn btn-primary">Save</button> <a href="index.php" class="btn btn-secondary">Cancel</a></form></div></div></body></html>