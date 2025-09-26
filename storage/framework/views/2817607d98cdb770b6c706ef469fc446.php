<!DOCTYPE html>
<html
  lang="<?php echo e(config()->get('app.locale')); ?>"
  class="light-style layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="<?php echo e(config()->get('app.locale') == 'ar' ? 'rtl' : 'ltr'); ?>"
  data-theme="theme-default"
  data-assets-path="<?php echo e(asset('admin/assets')); ?>/"
  data-template="vertical-menu-template-starter">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>MOTORS</title>

    <meta name="description" content="" />

    <?php echo $__env->make('admin.includes.styles', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <?php echo $__env->make('admin.includes.aside', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

            <?php echo $__env->make('admin.includes.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
            <!-- / Content -->


            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <form action="<?php echo e(route('admin.logout')); ?>" method="post" id="logout_form">
        <?php echo csrf_field(); ?>
    </form>
    <?php echo $__env->make('admin.includes.scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  </body>
</html>
<?php /**PATH /home/azsystems-motors/htdocs/motors.azsystems.tech/public/resources/views/admin/layouts/app.blade.php ENDPATH**/ ?>