<?php $__env->startSection('content'); ?>
<div class="container">
    <h2><?php echo e(App::getLocale() == 'ar' ? 'حقول الفئة: ' . $category->name_ar : 'Category Fields: ' . $category->name_en); ?></h2>

<a href="<?php echo e(route('categories.fields.create', $category->id)); ?>" class="btn btn-primary mb-3">
    <?php echo e(App::getLocale() == 'ar' ? 'إضافة حقل جديد' : 'Add New Field'); ?>

</a>

<div>
    <!-- عرض الحالة الحالية مع الأيقونة -->
    <p>
        <i class="fas <?php echo e($category->has_kilometers ? 'fa-check-circle' : 'fa-times-circle'); ?>" style="color: <?php echo e($category->has_kilometers ? 'green' : 'red'); ?>;"></i>
        <?php echo e(App::getLocale() == 'ar' ? 'حالة حقل الكيلومترات: ' : 'Kilometer Field Status: '); ?>

        <?php echo e($category->has_kilometers ? (App::getLocale() == 'ar' ? 'مفعّل' : 'Enabled') : (App::getLocale() == 'ar' ? 'معطّل' : 'Disabled')); ?>

    </p>

    <!-- الزر لتغيير الحالة -->
    <form action="<?php echo e(route('categories.toggleKilometers', $category->id)); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-primary">
            <?php echo e($category->has_kilometers ? (App::getLocale() == 'ar' ? 'إيقاف حقل الكيلومترات' : 'Disable Kilometer Field') : (App::getLocale() == 'ar' ? 'تفعيل حقل الكيلومترات' : 'Enable Kilometer Field')); ?>

        </button>
    </form>
</div>

 
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>اسم الحقل</th>
                <th>القيم</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $fixedFields = [
                    'Model Year' => 'سنة الموديل',
                    'Make' => 'الشركة المصنعة',
                ];
            ?>

            <?php $__currentLoopData = $fixedFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field_en => $field_ar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $field = $category->fields->where('field_en', $field_en)->first();
                ?>
                <tr>
                    <td style="<?php echo e($field_en == 'Make' ? 'color: black;' : ''); ?>">
                        <?php echo e(App::getLocale() == 'ar' ? $field_ar : $field_en); ?>

                    </td>
                    <td style="<?php echo e($field_en == 'Make' ? 'color: black;' : ''); ?>">
    <?php if($field && $field->values->count()): ?>
        <!-- فورم لإضافة موديل جديد تحت الحقل "Make" -->
        <?php if($field_en == 'Make'): ?>
        <form action="<?php echo e(route('categories.fields.store-car-model', $category->id)); ?>" method="POST" id="makeForm">
    <?php echo csrf_field(); ?>
    <div class="form-group">
        <label for="make"><?php echo e(__('اختر الشركة المصنعة')); ?></label>
        <select name="make_id" id="make" class="form-control" required>
            <option value=""><?php echo e(__('اختر الشركة')); ?></option>
            <?php $__currentLoopData = $field->values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value->id); ?>">
                    <?php echo e(App::getLocale() == 'ar' ? $value->value_ar : $value->value_en); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div id="modelFieldsContainer">
        <div class="modelField" id="modelField1">
            <label for="make_ar[]"><?php echo e(__('موديل 1')); ?></label>
            <input type="text" class="form-control form-control-sm mt-2" placeholder="<?php echo e(__('المودل عربي')); ?>" name="make_ar[]" required />
            <input type="text" class="form-control form-control-sm mt-2" placeholder="<?php echo e(__('المودل انجليزي')); ?>" name="make_en[]" required />
        </div>
    </div>

    <button type="button" class="btn btn-secondary mt-2" onclick="addModelField()"><?php echo e(__('إضافة موديل')); ?></button>
    <button type="submit" class="btn btn-primary mt-2"><?php echo e(__('حفظ')); ?></button>
</form>

<!-- Loading Spinner (Initially Hidden) -->
<div id="loadingSpinner" style="display:none;">
    <img src="https://cdnjs.cloudflare.com/ajax/libs/Font-Awesome/4.7.0/fonts/fontawesome-webfont.svg" alt="Loading..." />
</div>

<script>
    let modelCount = 1; // العدّاد لتسلسل الموديلات

    // دالة لإضافة حقل موديل جديد
    function addModelField() {
        modelCount++; // زيادة العدّاد عند إضافة موديل جديد

        // إنشاء حقل جديد للموديل مع العنوان التسلسلي
        const modelFieldHTML = `
            <div class="modelField" id="modelField${modelCount}">
                <label for="make_ar[]"><?php echo e(__('موديل ')); ?>${modelCount}</label>
                <input type="text" class="form-control form-control-sm mt-2" placeholder="<?php echo e(__('المودل عربي')); ?>" name="make_ar[]" required />
                <input type="text" class="form-control form-control-sm mt-2" placeholder="<?php echo e(__('المودل انجليزي')); ?>" name="make_en[]" required />
            </div>
        `;

        // إضافة الحقل الجديد إلى حاوية الحقول
        document.getElementById('modelFieldsContainer').insertAdjacentHTML('beforeend', modelFieldHTML);
    }

    // منع ارسال الفورم بالطريقة التقليدية لإظهار مؤشر التحميل إذا كان هناك حاجة
    document.getElementById('makeForm').addEventListener('submit', function (event) {
        // إظهار مؤشر التحميل
        document.getElementById('loadingSpinner').style.display = 'block';
    });
</script>

<style>
    /* Basic styling for the loading spinner */
    #loadingSpinner {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        display: none;
    }

    /* Style for each model field */
    .modelField {
        margin-bottom: 15px;
    }
</style>


        <?php endif; ?>

        <ul>
    <?php $__currentLoopData = $field->values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <li style="<?php echo e($field->field_en == 'Make' ? 'color: black;' : ''); ?>">
    <p><?php echo e(App::getLocale() == 'ar' ? $value->value_ar : $value->value_en); ?></p>

    <?php if($field->field_en == 'Make' && $value->carModels->count()): ?>
        <ul>
            <?php $__currentLoopData = $value->carModels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $model): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="d-flex align-items-center">
    <span>
        <?php echo e(App::getLocale() == 'ar' ? $model->value_ar : $model->value_en); ?>

    </span>

    <!-- Delete button with X icon -->
    <form action="<?php echo e(route('carModel.delete', $model->id)); ?>" method="POST" style="display: inline;">
    <?php echo csrf_field(); ?>
    <?php echo method_field('DELETE'); ?>
    <button type="submit" class="btn btn-sm btn-danger p-1 ml-2" title="Delete" style="border-radius: 50%; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-times" style="font-size: 16px;"></i> <!-- Font Awesome X icon -->
    </button>
</form>

</li>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    <?php endif; ?>
</li>

    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul>

    <?php else: ?>
        <span class="text-muted"><?php echo e(__('لم يتم إضافة قيم بعد')); ?></span>
    <?php endif; ?>
</td>


                    <td>
                        <form action="<?php echo e(route('categories.fields.ensureExists', [$category->id])); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="field_en" value="<?php echo e($field_en); ?>">
                            <input type="hidden" name="field_ar" value="<?php echo e($field_ar); ?>">
                            <button type="submit" class="btn btn-warning btn-sm">تعديل</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php $__currentLoopData = $category->fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(!in_array($field->field_en, array_keys($fixedFields))): ?>
                    <tr>
                        <td><?php echo e(App::getLocale() == 'ar' ? $field->field_ar : $field->field_en); ?></td>
                        <td>
                            <ul>
                                <?php $__currentLoopData = $field->values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e(App::getLocale() == 'ar' ? $value->value_ar : $value->value_en); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </td>
                        <td>
                            <a href="<?php echo e(route('categories.fields.edit', [$category->id, $field->id])); ?>" class="btn btn-warning btn-sm">تعديل</a>
                            <form action="<?php echo e(route('categories.fields.destroy', [$category->id, $field->id])); ?>" method="POST" style="display:inline-block;">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/azsystems-motors/htdocs/motors.azsystems.tech/public/resources/views/categories/fields/show.blade.php ENDPATH**/ ?>