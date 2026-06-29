<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM employees WHERE name LIKE '%$search%' OR position LIKE '%$search%' OR email LIKE '%$search%'";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html><head><title>Employee Records</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/css/bootstrap.min.css" rel="stylesheet"></head>
<body><div class="container mt-4"><div class="card shadow"><div class="card-header bg-dark text-white d-flex justify-content-between"><h3>👥 Employee Management</h3><div><a href="add_employee.php" class="btn btn-success">+ Add Employee</a> <a href="logout.php" class="btn btn-danger">Logout</a></div></div>
<div class="card-body"><form method="GET" class="mb-3"><div class="input-group"><input type="text" name="search" class="form-control" placeholder="Search by name, position, email..." value="<?= $search ?>"><button class="btn btn-primary">🔍 Search</button></div></form>
<table class="table table-hover table-responsive"><thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Email</th><th>Position</th><th>Salary</th><th>Hire Date</th><th>Actions</th></tr></thead>
<tbody><?php while($emp = $result->fetch_assoc()): ?><tr><td><?= $emp['id'] ?></td><td><?= htmlspecialchars($emp['name']) ?></td><td><?= $emp['email'] ?></td><td><?= $emp['position'] ?></td><td>$<?= number_format($emp['salary'],2) ?></td><td><?= $emp['hire_date'] ?></td>
<td><a href="edit_employee.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-warning">Edit</a> <a href="delete_employee.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a></td></tr><?php endwhile; ?></tbody></table></div></div></div></body></html>