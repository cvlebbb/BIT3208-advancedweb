<?php
require_once 'config.php';

$errors = [];
$book_id = $title = $author = $category = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = trim($_POST['book_id']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category = trim($_POST['category']);
    
    if (empty($book_id)) $errors[] = "Book ID required.";
    if (empty($title)) $errors[] = "Title required.";
    if (empty($author)) $errors[] = "Author required.";
    if (empty($category)) $errors[] = "Category required.";
    
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM books WHERE book_id = ?");
        $check->bind_param("s", $book_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "Book ID already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO books (book_id, title, author, category) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $book_id, $title, $author, $category);
            if ($stmt->execute()) {
                header("Location: index.php?msg=added");
                exit();
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Responsive styles (same as previous, or link to external CSS) */
        body{background:#f4f1ea;font-family:sans-serif;padding:1rem;}
        .container{max-width:600px;margin:0 auto;}
        .card{background:white;border-radius:28px;padding:2rem;}
        .form-group{margin-bottom:1rem;}
        label{font-weight:600;display:block;}
        input{width:100%;padding:0.7rem;border-radius:20px;border:1px solid #ccc;}
        .btn{padding:0.6rem 1.5rem;border-radius:40px;border:none;cursor:pointer;}
        .btn-primary{background:#2f6b47;color:white;}
        .error{color:#bc4a2c;margin-bottom:1rem;}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Add New Book</h2>
        <?php if(!empty($errors)): ?>
            <div class="error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Book ID</label><input type="text" name="book_id" value="<?=htmlspecialchars($book_id)?>" required></div>
            <div class="form-group"><label>Title</label><input type="text" name="title" value="<?=htmlspecialchars($title)?>" required></div>
            <div class="form-group"><label>Author</label><input type="text" name="author" value="<?=htmlspecialchars($author)?>" required></div>
            <div class="form-group"><label>Category</label><input type="text" name="category" value="<?=htmlspecialchars($category)?>" required></div>
            <button type="submit" class="btn btn-primary">Save Book</button>
            <a href="index.php" style="margin-left:1rem;">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>