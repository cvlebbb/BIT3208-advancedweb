<?php require_once 'config.php';
$result = $conn->query("SELECT * FROM books ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head><title>Library Books</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>
body{background:#f4f1ea;font-family:sans-serif;padding:1rem;}
.container{max-width:1200px;margin:0 auto;}
.card{background:white;border-radius:28px;padding:2rem;overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th,td{padding:1rem;text-align:left;border-bottom:1px solid #eee;}
.btn{padding:0.3rem 1rem;border-radius:30px;text-decoration:none;display:inline-block;margin:0.2rem;}
.btn-add{background:#2f6b47;color:white;margin-bottom:1rem;display:inline-block;}
.btn-edit{background:#d68b2c;color:white;}
.btn-delete{background:#bc4a2c;color:white;}
@media(max-width:600px){th,td{padding:0.5rem;font-size:0.8rem;}}
</style></head>
<body><div class="container"><div class="card"><a href="add.php" class="btn btn-add">+ Add Book</a><h2>📚 Book List</h2><table><thead><tr><th>ID</th><th>Book ID</th><th>Title</th><th>Author</th><th>Category</th><th>Actions</th></tr></thead><tbody><?php while($row=$result->fetch_assoc()):?><tr><td><?=$row['id']?></td><td><?=htmlspecialchars($row['book_id'])?></td><td><?=htmlspecialchars($row['title'])?></td><td><?=htmlspecialchars($row['author'])?></td><td><?=htmlspecialchars($row['category'])?></td><td><a href="edit.php?id=<?=$row['id']?>" class="btn btn-edit">Edit</a> <a href="delete.php?id=<?=$row['id']?>" class="btn btn-delete" onclick="return confirm('Delete?')">Delete</a></td></tr><?php endwhile;?></tbody></table></div></div></body></html>