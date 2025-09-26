<?php $__env->startSection('content'); ?>
<div class="container">
    <h2 class="mb-4">إدارة الإعلانات</h2>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <label for="statusFilter" class="form-label">فلترة حسب الحالة:</label>
        <select id="statusFilter" class="form-select" onchange="filterAds()">
            <option value="">كل الإعلانات</option>
            <option value="pending" <?php echo e(request('status') == 'pending' ? 'selected' : ''); ?>>قيد المراجعة</option>
            <option value="approved" <?php echo e(request('status') == 'approved' ? 'selected' : ''); ?>>مقبول</option>
            <option value="rejected" <?php echo e(request('status') == 'rejected' ? 'selected' : ''); ?>>مرفوض</option>
        </select>
    </div>

    <script>
        function filterAds() {
            let status = document.getElementById('statusFilter').value;
            window.location.href = "<?php echo e(route('ads.management')); ?>?status=" + status;
        }
    </script>


    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>عنوان الإعلان</th>
                <th>الوصف</th>
                              <th>العنوان</th>

                <th>السعر</th>
                <th>رقم الهاتف</th>
                <th>المستخدم</th>
                              <th>كيلو متر</th>

                <th>الحالة</th>
                <th>الصورة الرئيسية</th>
                <th>الصور الفرعية</th>
                <th>الحقول والقيم</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $ads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($ad->id); ?></td>
                <td><?php echo e($ad->title); ?></td>
                <td><?php echo e(Str::limit($ad->description, 50)); ?></td>
              
                              <td><?php echo e($ad->address); ?></td>

                <td><?php echo e($ad->price); ?></td>
                <td><?php echo e($ad->phone_number); ?></td>
                <td><?php echo e($ad->user->first_name ?? 'غير معروف'); ?> <?php echo e($ad->user->last_name ?? 'غير معروف'); ?></td>
                              <td><?php echo e($ad->kilometer); ?></td>

                <td>
                    <?php if($ad->status == 'pending'): ?>
                        <span class="badge bg-warning">قيد المراجعة</span>
                    <?php elseif($ad->status == 'approved'): ?>
                        <span class="badge bg-success">مقبول</span>
                    <?php elseif($ad->status == 'rejected'): ?>
                        <span class="badge bg-danger">مرفوض</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($ad->main_image): ?>
                        <img src="<?php echo e(asset('/' . $ad->main_image)); ?>" width="80">
                    <?php else: ?>
                        <span>لا يوجد صورة</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $__currentLoopData = $ad->subImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <img src="<?php echo e(asset('/' . $image->image)); ?>" width="60">
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </td>
               <td>
    <ul>
        <?php $__currentLoopData = $ad->fieldValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fieldValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <?php
                    $locale = app()->getLocale();
$fieldName = $locale === 'ar' ? optional($fieldValue->field)->field_ar : optional($fieldValue->field)->field_en;
                    $fieldValueName = $locale === 'ar' ? optional($fieldValue->fieldValue)->value_ar : optional($fieldValue->fieldValue)->value_en;
                ?>
                <strong><?php echo e($fieldName ?? 'غير معروف'); ?>:</strong> <?php echo e($fieldValueName ?? 'غير معروف'); ?>

            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</td>

                <td>
                    <!-- زر الموافقة -->
                    <form action="<?php echo e(route('ads.updateStatus', $ad->id)); ?>" method="POST" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn btn-success btn-sm">موافقة</button>
                    </form>

                    <!-- زر الرفض -->
                    <form action="<?php echo e(route('ads.updateStatus', $ad->id)); ?>" method="POST" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-danger btn-sm">رفض</button>
                    </form>

                    <!-- زر الحذف -->
                    <form action="<?php echo e(route('ads.destroy', $ad->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإعلان؟');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-warning btn-sm">حذف</button>
                    </form>
                </td>

            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/azsystems-motors/htdocs/motors.azsystems.tech/public/resources/views/pages/ads/ads-management.blade.php ENDPATH**/ ?>