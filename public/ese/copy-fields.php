<?php
include 'routs.php';

$message = "";
$fields = [];
$source_category_id = "";
$target_category_id = "";

// --- معالجة النسخ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'copy') {
    $source_category_id = intval($_POST['source_category']);
    $target_category_id = intval($_POST['target_category']);
    $selected_fields = isset($_POST['selected_fields']) ? array_map('intval', $_POST['selected_fields']) : [];

    if (!$source_category_id || !$target_category_id || empty($selected_fields)) {
        $message = "<div class='alert alert-danger'>يرجى اختيار الفئتين واختيار حقل واحد على الأقل.</div>";
    } elseif ($source_category_id == $target_category_id) {
        $message = "<div class='alert alert-danger'>لا يمكن نسخ الحقول إلى نفس الفئة!</div>";
    } else {
        foreach ($selected_fields as $field_id) {
            // التأكد أن هذا الحقل ينتمي للفئة المصدر
            $check_sql = "SELECT id, field_ar, field_en FROM category_fields WHERE id = ? AND category_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $field_id, $source_category_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                $check_stmt->close();
                continue;
            }

            $row = $check_result->fetch_assoc();
            $field_ar = $row['field_ar'];
            $field_en = $row['field_en'];
            $check_stmt->close();

            // إدراج الحقل الجديد في الفئة المستهدفة
            $insert_sql = "INSERT INTO category_fields 
                           (category_id, field_ar, field_en, created_at, updated_at)
                           VALUES (?, ?, ?, NOW(), NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iss", $target_category_id, $field_ar, $field_en);
            $insert_stmt->execute();
            $new_field_id = $insert_stmt->insert_id;
            $insert_stmt->close();

            // الآن نسخ القيم المرتبطة بهذا الحقل
            $value_sql = "SELECT value_ar, value_en, field_type FROM category_field_values WHERE category_field_id = ?";
            $value_stmt = $conn->prepare($value_sql);
            $value_stmt->bind_param("i", $field_id);
            $value_stmt->execute();
            $values_result = $value_stmt->get_result();

            while ($value_row = $values_result->fetch_assoc()) {
                $value_ar = $value_row['value_ar'];
                $value_en = $value_row['value_en'];
                $field_type = $value_row['field_type'];

                $insert_value_sql = "INSERT INTO category_field_values 
                                     (category_field_id, value_ar, value_en, field_type, created_at, updated_at)
                                     VALUES (?, ?, ?, ?, NOW(), NOW())";
                $insert_value_stmt = $conn->prepare($insert_value_sql);
                $insert_value_stmt->bind_param("isss", $new_field_id, $value_ar, $value_en, $field_type);
                $insert_value_stmt->execute();
                $insert_value_stmt->close();
            }
            $value_stmt->close();
        }

        $message = "<div class='alert alert-success'>✅ تم نسخ الحقول المختارة بنجاح!</div>";
    }
}

// --- معالجة عرض الحقول ---
$show_fields = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'load_fields') {
    $source_category_id = intval($_POST['source_category']);
    $target_category_id = intval($_POST['target_category'] ?? '');
    if ($source_category_id > 0) {
        $sql = "SELECT id, field_ar, field_en FROM category_fields WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $source_category_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $fields[] = $row;
        }
        $stmt->close();
        $show_fields = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نسخ الحقول بين الفئات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .form-check-input:checked { background-color: #0d6efd; border-color: #0d6efd; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container bg-white shadow rounded p-4">
    <h2 class="mb-4 text-center">🔁 نسخ الحقول بين الفئات</h2>

    <?= $message; ?>

    <form method="post">
        <input type="hidden" name="action" value="load_fields">

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">اختر الفئة المصدر:</label>
                <select name="source_category" class="form-control" required>
                    <option value="">اختر الفئة</option>
                    <?php
                    $cats = $conn->query("SELECT id, name_ar FROM categories");
                    while ($cat = $cats->fetch_assoc()):
                        $selected = ($cat['id'] == $source_category_id) ? 'selected' : '';
                        echo "<option value='{$cat['id']}' $selected>{$cat['name_ar']} ({$cat['id']})</option>";
                    endwhile;
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">اختر الفئة الهدف:</label>
                <select name="target_category" class="form-control" required>
                    <option value="">اختر الفئة</option>
                    <?php
                    $cats = $conn->query("SELECT id, name_ar FROM categories");
                    while ($cat = $cats->fetch_assoc()):
                        $selected = ($cat['id'] == $target_category_id) ? 'selected' : '';
                        echo "<option value='{$cat['id']}' $selected>{$cat['name_ar']} ({$cat['id']})</option>";
                    endwhile;
                    ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mb-3">عرض الحقول</button>
    </form>

    <?php if ($show_fields && count($fields) > 0): ?>
        <form method="post">
            <input type="hidden" name="action" value="copy">
            <input type="hidden" name="source_category" value="<?= $source_category_id ?>">
            <input type="hidden" name="target_category" value="<?= $target_category_id ?>">

            <div class="mb-3">
                <label class="form-label">اختر الحقول التي تريد نسخها:</label>
                <div class="row">
                    <?php foreach ($fields as $field): ?>
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_fields[]" value="<?= $field['id'] ?>" id="field<?= $field['id'] ?>">
                                <label class="form-check-label" for="field<?= $field['id'] ?>">
                                    <?= htmlspecialchars($field['field_ar']) ?> (<?= htmlspecialchars($field['field_en']) ?>)
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-success">💾 نسخ الحقول المختارة</button>
        </form>
    <?php elseif ($show_fields): ?>
        <div class="alert alert-warning">لا توجد أي حقول في هذه الفئة.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
