<?php
include 'routs.php';

// جلب جميع الفئات
$categories = [];
$sql = "SELECT * FROM categories";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $categories[$row['id']] = $row;
    }
}

// جلب جميع الحقول الخاصة بكل فئة
$fields = [];
$sql2 = "SELECT * FROM category_fields";
$res2 = $conn->query($sql2);
if ($res2 && $res2->num_rows > 0) {
    while ($row = $res2->fetch_assoc()) {
        $fields[$row['category_id']][] = $row;
    }
}

// جلب جميع القيم الخاصة بكل حقل
$field_values = [];
$sql3 = "SELECT * FROM category_field_values";
$res3 = $conn->query($sql3);
if ($res3 && $res3->num_rows > 0) {
    while ($row = $res3->fetch_assoc()) {
        $field_values[$row['category_field_id']][] = $row;
    }
}

// جلب كل car_models مرتبة حسب category_field_id
$car_models = [];
$sql4 = "SELECT * FROM car_models";
$res4 = $conn->query($sql4);
if ($res4 && $res4->num_rows > 0) {
    while ($row = $res4->fetch_assoc()) {
        $car_models[$row['category_field_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شجرة الفئات والحقول والقيم والموديلات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .en-word { direction: ltr; display: inline-block; unicode-bidi: embed; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
        .empty-msg { color: #999; font-style: italic; }
        .field-btn { cursor: pointer; }
        .models-list { margin-right: 2rem; margin-top: 0.5rem;}
        .models-title { font-size: 0.95em; color: #0d6efd; margin-top: 0.5rem;}
    </style>
</head>
<body class="p-3 bg-light">
    <h2 class="mb-4">شجرة الفئات والحقول والقيم والموديلات (كل مستوى قابل للطي)</h2>
    <div class="accordion" id="categoriesAccordion">
        <?php foreach ($categories as $cat): ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingCat<?php echo $cat['id']; ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCat<?php echo $cat['id']; ?>" aria-expanded="false" aria-controls="collapseCat<?php echo $cat['id']; ?>">
                    <b><?php echo htmlspecialchars($cat['name_ar']); ?></b>
                    <span class="en-word">(<?php echo htmlspecialchars($cat['name_en']); ?>)</span>
                </button>
            </h2>
            <div id="collapseCat<?php echo $cat['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingCat<?php echo $cat['id']; ?>" data-bs-parent="#categoriesAccordion">
                <div class="accordion-body">
                    <?php if (!empty($fields[$cat['id']])): ?>
                        <div class="accordion" id="fieldsAccordion<?php echo $cat['id']; ?>">
                        <?php foreach ($fields[$cat['id']] as $field): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingField<?php echo $field['id']; ?>">
                                    <button class="accordion-button collapsed field-btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseField<?php echo $field['id']; ?>" aria-expanded="false" aria-controls="collapseField<?php echo $field['id']; ?>">
                                        <b><?php echo htmlspecialchars($field['field_ar']); ?></b>
                                        <span class="en-word">(<?php echo htmlspecialchars($field['field_en']); ?>)</span>
                                    </button>
                                </h2>
                                <div id="collapseField<?php echo $field['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingField<?php echo $field['id']; ?>" data-bs-parent="#fieldsAccordion<?php echo $cat['id']; ?>">
                                    <div class="accordion-body">
                                        <?php if (!empty($field_values[$field['id']])): ?>
                                            <ul>
                                            <?php foreach ($field_values[$field['id']] as $fv): ?>
                                                <li>
                                                    <?php echo htmlspecialchars($fv['value_ar']); ?>
                                                    <span class="en-word">(<?php echo htmlspecialchars($fv['value_en']); ?>)</span>
                                                    <?php if ($fv['field_type']): ?>
                                                        <span class="badge bg-secondary en-word"><?php echo htmlspecialchars($fv['field_type']); ?></span>
                                                    <?php endif; ?>

                                                    <?php
                                                    // عرض car_models إذا وجدت
                                                    if (!empty($car_models[$fv['id']])): ?>
                                                        <div class="models-title">الموديلات:</div>
                                                        <ul class="models-list">
                                                            <?php foreach ($car_models[$fv['id']] as $model): ?>
                                                                <li>
                                                                    <?php echo htmlspecialchars($model['value_ar']); ?>
                                                                    <span class="en-word">(<?php echo htmlspecialchars($model['value_en']); ?>)</span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <div class="models-list empty-msg">لا توجد موديلات.</div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <div class="empty-msg">لا توجد قيم لهذا الحقل.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-msg">لا توجد حقول لهذه الفئة.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
            <div class="alert alert-warning mt-3">لا توجد فئات في قاعدة البيانات.</div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
