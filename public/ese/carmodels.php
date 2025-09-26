<?php
include 'routs.php';

$message = "";
$source_category_id = "";
$target_category_id = "";

// جلب الفئات
$categories = [];
$res = $conn->query("SELECT id, name_ar, name_en FROM categories ORDER BY name_ar");
while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
}

// دالة لجلب كل شركات الفئة (القيم)
function get_makes($conn, $category_id) {
    $makes = [];
    $stmt = $conn->prepare("SELECT id FROM category_fields WHERE category_id = ? AND (field_en LIKE '%make%' OR field_ar LIKE '%شركة%')");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $field_ids = [];
    while ($row = $res->fetch_assoc()) {
        $field_ids[] = $row['id'];
    }
    $stmt->close();
    if (count($field_ids) == 0) return [];
    $in = implode(',', array_fill(0, count($field_ids), '?'));
    $types = str_repeat('i', count($field_ids));
    $sql = "SELECT id, value_ar, value_en, category_field_id FROM category_field_values WHERE category_field_id IN ($in)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$field_ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $makes[] = $row;
    }
    $stmt->close();
    return $makes;
}

// دالة تحقق قبل التنفيذ
function validate_categories($conn, $source_category_id, $target_category_id) {
    if (!$source_category_id || !$target_category_id) {
        return "يرجى اختيار الفئتين.";
    }
    if ($source_category_id == $target_category_id) {
        return "لا يمكن النسخ لنفس الفئة!";
    }
    // تأكد من وجود شركات في الفئة المصدر والهدف
    $source_makes = get_makes($conn, $source_category_id);
    $target_makes = get_makes($conn, $target_category_id);
    if (count($source_makes) == 0) {
        return "لا توجد شركات في الفئة المصدر.";
    }
    if (count($target_makes) == 0) {
        return "لا توجد شركات في الفئة الهدف.";
    }
    // تأكد من وجود شركات متطابقة
    $matches = 0;
    foreach ($source_makes as $src) {
        foreach ($target_makes as $tgt) {
            if (
                trim(mb_strtolower($src['value_ar'])) === trim(mb_strtolower($tgt['value_ar'])) ||
                trim(mb_strtolower($src['value_en'])) === trim(mb_strtolower($tgt['value_en']))
            ) {
                $matches++;
                break;
            }
        }
    }
    if ($matches == 0) {
        return "لا توجد شركات متطابقة بين الفئتين.";
    }
    return true;
}

// تنفيذ النسخ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'copy_all') {
    $source_category_id = intval($_POST['source_category']);
    $target_category_id = intval($_POST['target_category']);

    // تحقق أولاً
    $validation = validate_categories($conn, $source_category_id, $target_category_id);
    if ($validation !== true) {
        $message = "<div class='alert alert-danger'>$validation</div>";
    } else {
        $source_makes = get_makes($conn, $source_category_id);
        $target_makes = get_makes($conn, $target_category_id);

        // بناء مصفوفة للشركات الهدف (بحث سريع بالاسم)
        $target_map = [];
        foreach ($target_makes as $tgt) {
            $target_map[mb_strtolower(trim($tgt['value_ar']))] = $tgt['id'];
            $target_map[mb_strtolower(trim($tgt['value_en']))] = $tgt['id'];
        }

        $copied = 0; $skipped = 0;
        foreach ($source_makes as $src) {
            // ابحث عن شركة مطابقة في الفئة الهدف
            $match_id = null;
            if (isset($target_map[mb_strtolower(trim($src['value_ar']))])) {
                $match_id = $target_map[mb_strtolower(trim($src['value_ar']))];
            } elseif (isset($target_map[mb_strtolower(trim($src['value_en']))])) {
                $match_id = $target_map[mb_strtolower(trim($src['value_en']))];
            }
            if (!$match_id) {
                $skipped++;
                continue; // لا يوجد شركة مطابقة في الهدف
            }
            // جلب كل الموديلات لهذه الشركة في الفئة المصدر
            $sql = "SELECT value_ar, value_en FROM car_models WHERE category_field_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $src['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                // أدخل الموديل في الشركة المطابقة في الفئة الهدف
                $insert_sql = "INSERT INTO car_models (category_field_id, value_ar, value_en, created_at, updated_at)
                               VALUES (?, ?, ?, NOW(), NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iss", $match_id, $row['value_ar'], $row['value_en']);
                $insert_stmt->execute();
                $insert_stmt->close();
                $copied++;
            }
            $stmt->close();
        }
        $message = "<div class='alert alert-success'>✅ تم نسخ $copied موديل بنجاح! <br> تم تخطي $skipped شركة لعدم وجود شركة مطابقة في الفئة الهدف.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نسخ كل موديلات السيارات بين الفئات تلقائياً</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container bg-white shadow rounded p-4">
    <h2 class="mb-4 text-center">🚗 نسخ كل موديلات السيارات بين الفئات تلقائياً</h2>

    <?= $message; ?>

    <form method="post">
        <input type="hidden" name="action" value="copy_all">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">اختر فئة المصدر:</label>
                <select name="source_category" class="form-control" required>
                    <option value="">اختر الفئة</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $source_category_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name_ar']) ?> (<?= htmlspecialchars($cat['name_en']) ?>) [<?= $cat['id'] ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">اختر فئة الهدف:</label>
                <select name="target_category" class="form-control" required>
                    <option value="">اختر الفئة</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $target_category_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name_ar']) ?> (<?= htmlspecialchars($cat['name_en']) ?>) [<?= $cat['id'] ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-success">💾 نسخ كل الموديلات تلقائياً</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
