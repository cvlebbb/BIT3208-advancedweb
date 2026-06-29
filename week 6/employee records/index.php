<?php
require_once 'config.php';
requireLogin();

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Optionally unlink user account's employee_id later, but for now just delete employee
    $deleteStmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $deleteStmt->bind_param("i", $id);
    $deleteStmt->execute();
    $deleteStmt->close();
    header("Location: index.php");
    exit();
}

// Search functionality
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM employees WHERE name LIKE ? OR email LIKE ? OR position LIKE ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$searchParam = "%$search%";
$stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Records Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>👥 Employee Records Management</h1>
            <div>
                <a href="add_employee.php" class="btn btn-primary">+ Add Employee</a>
                <a href="logout.php" class="btn" style="background:#6c757d; color:white; margin-left:0.5rem;">Logout</a>
            </div>
        </div>
        <div class="search-bar">
            <form method="GET" style="flex:1; display:flex; gap:0.5rem;">
                <input type="text" name="search" placeholder="Search by name, email, or position..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">🔍 Search</button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Position</th><th>Salary</th><th>Hire Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr><td colspan="7" style="text-align:center;">No employees found.</td></tr>
                    <?php else: ?>
                        <?php while ($emp = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $emp['id']; ?></td>
                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td>KSH<?php echo number_format($emp['salary'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($emp['hire_date'])); ?></td>
                            <td class="action-btns">
                                <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="?delete=<?php echo $emp['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this employee permanently?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>