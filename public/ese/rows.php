<?php
include 'routs.php'; 

// Get table name from URL
$table = isset($_GET['table']) ? $_GET['table'] : '';
$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table); // sanitize

$rows = [];
$fields = [];
if ($table) {
    $sql = "SELECT * FROM `$table` LIMIT 50";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $fields = $result->fetch_fields();
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rows of <?php echo htmlspecialchars($table); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
    <a href="tables.php" class="btn btn-secondary mb-3">&larr; Back to Tables</a>
    <h2>Rows of <code><?php echo htmlspecialchars($table); ?></code></h2>
    <?php if ($rows): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <?php foreach($fields as $field): ?>
                            <th><?php echo htmlspecialchars($field->name); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $row): ?>
                        <tr>
                            <?php foreach($fields as $field): ?>
                                <td><?php echo htmlspecialchars($row[$field->name]); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No rows found or table is empty.</div>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
