<?php
include 'routs.php'; 
$tables = [];
$sql = "SHOW TABLES";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
    <h2>Database Tables</h2>
    <ul class="list-group">
        <?php foreach($tables as $table): ?>
            <li class="list-group-item">
                <a href="rows.php?table=<?php echo urlencode($table); ?>">
                    <?php echo htmlspecialchars($table); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
<?php $conn->close(); ?>
