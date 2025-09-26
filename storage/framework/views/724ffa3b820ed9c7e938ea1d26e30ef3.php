<?php $__env->startSection('content'); ?>

<div class="row">
    <div class="col-xl">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="text-center my-4"> <?php echo e(trans('main_trans.country')); ?></h3>
                <div class="text-right mb-3">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create apartments')): ?>
                    <a href="<?php echo e(route('countrys.create')); ?>" class="btn-bid-now" style="color:white; cursor:pointer">
                        <i class="fas fa-plus-circle"></i> <?php echo e(trans('web.add')); ?>

                    </a>
                    <?php endif; ?>
                </div>

            </div>

            <div class="card-body">


                <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>





            <button type="button" class=" button x-small  " data-toggle="modal" data-target="#exampleModal">
                <?php echo e(trans('Counters_trans.add_Grade')); ?>

            </button>




                <div class="table-responsive">
                    <table class="table table-bordered data-table" id="data-table">
                        <table id="datatable" class="table  table-hover table-sm table-bordered p-0" data-page-length="50"
                        style="text-align: center">
                        <thead>
                            <tr>
                                <th>id</th>
                                <th><?php echo e(trans('Counters_trans.Name')); ?></th>
                                                              <th><?php echo e(trans('Counters_trans.Currancy')); ?></th>

                                <th><?php echo e(trans('Counters_trans.image')); ?></th>
                                <th><?php echo e(trans('Counters_trans.Processes')); ?></th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php $__currentLoopData = $countrys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                <tr>
                                    <td><?php echo e($country->id); ?></td>
                                    <td><?php if(App::getLocale() == 'ar'): ?>
                                        <?php echo e($country->name_ar); ?>

                                    <?php else: ?>
                                        <?php echo e($country->name_en); ?>

                                    <?php endif; ?></td>
                                  
                                   <td><?php if(App::getLocale() == 'ar'): ?>
                                        <?php echo e($country->currency_ar); ?>

                                    <?php else: ?>
                                        <?php echo e($country->currency_en); ?>

                                    <?php endif; ?></td>


                                    <td>
                                        <img src="<?php echo e(asset('countrys/' . $country->image)); ?>" width="50" height="50">
                                    </td>

                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal"
                                            data-target="#edit<?php echo e($country->id); ?>"
                                            title="<?php echo e(trans('Counters_trans.Edit')); ?>"><i
                                                class="fa fa-edit"></i></button>
                                        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal"
                                            data-target="#delete<?php echo e($country->id); ?>"
                                            title="<?php echo e(trans('Counters_trans.Delete')); ?>"><i
                                                class="fa fa-trash"></i></button>
                                    </td>



                                </tr>

                                    <!-- edit_modal_Grade -->
                                    <div class="modal fade" id="edit<?php echo e($country->id); ?>" tabindex="-1" role="dialog"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title"
                                                        id="exampleModalLabel">
                                                        <?php echo e(trans('Counters_trans.edit_Grade')); ?>

                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- add_form -->
                                                    <form action="<?php echo e(route('countrys.update',$country->id)); ?>" method="post" enctype="multipart/form-data">
                                                        <?php echo e(method_field('patch')); ?>

                                                        <?php echo csrf_field(); ?>
                                                        <div class="row">
                                                            <div class="col">
                                                                <label for="name_ar"
                                                                    class="mr-sm-2"><?php echo e(trans('Counters_trans.name_ar')); ?>

                                                                    :</label>
                                                                <input id="name_ar" type="text" name="name_ar"
                                                                    class="form-control"
                                                                    value="<?php echo e($country->name_ar); ?>"
                                                                    required>
                                                                <input id="id" type="hidden" name="id"
                                                                    class="form-control" value="<?php echo e($country->id); ?>">
                                                            </div>
                                                            <div class="col">
                                                                <label for="name_en"
                                                                    class="mr-sm-2"><?php echo e(trans('Counters_trans.name_en')); ?>

                                                                    :</label>
                                                                <input type="text" class="form-control"
                                                                    value="<?php echo e($country->name_en); ?>"
                                                                    name="name_en" required>
                                                            </div>
                                                        </div>
                                                      
                                                      
                                                      <div class="row">
                                                            <div class="col">
                                                                <label for="currency_ar"
                                                                    class="mr-sm-2"><?php echo e(trans('Counters_trans.currency_ar')); ?>

                                                                    :</label>
                                                                <input id="currency_ar" type="text" name="currency_ar"
                                                                    class="form-control"
                                                                    value="<?php echo e($country->currency_ar); ?>"
                                                                    required>
                                                                <input id="id" type="hidden" name="id"
                                                                    class="form-control" value="<?php echo e($country->id); ?>">
                                                            </div>
                                                            <div class="col">
                                                                <label for="currency_en"
                                                                    class="mr-sm-2"><?php echo e(trans('Counters_trans.currency_en')); ?>

                                                                    :</label>
                                                                <input type="text" class="form-control"
                                                                    value="<?php echo e($country->currency_en); ?>"
                                                                    name="currency_en" required>
                                                            </div>
                                                        </div>

                                                        <br>

                                                            <div class="div_design">
                                                                <label for="">current image :</label>
                                                                <img src="<?php echo e(asset('countrys/' . $country->image)); ?>" width="50" height="50">
                                                            </div>
                                                            <br>
                                                            <div class="div_design">
                                                                <label for="">chance image :</label>
                                                                <input type="file" name="image" >
                                                            </div>

                                                        <br><br>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal"><?php echo e(trans('Counters_trans.Close')); ?></button>
                                                            <button type="submit"
                                                                class="btn btn-success"><?php echo e(trans('Counters_trans.Submit')); ?></button>
                                                        </div>
                                                    </form>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
    
                                    <!-- delete_modal_Grade -->
                                    <div class="modal fade" id="delete<?php echo e($country->id); ?>" tabindex="-1" role="dialog"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title"
                                                        id="exampleModalLabel">
                                                        <?php echo e(trans('Counters_trans.delete_Grade')); ?>

                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="<?php echo e(route('countrys.destroy',$country->id)); ?>" method="post">
                                                        <?php echo e(method_field('Delete')); ?>

                                                        <?php echo csrf_field(); ?>
                                                        <?php echo e(trans('Counters_trans.Warning_Grade')); ?>

                                                        <input id="id" type="hidden" name="id" class="form-control"
                                                               value="<?php echo e($country->id); ?>">
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                    data-dismiss="modal"><?php echo e(trans('Counters_trans.Close')); ?></button>
                                                            <button type="submit"
                                                                    class="btn btn-danger"><?php echo e(trans('Counters_trans.Submit')); ?></button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </table>
                        <?php echo e($countrys->links('pagination::bootstrap-5')); ?>



                    </div>
                </div>
            </div>
        </div>


        <!-- add_modal_Grade -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title" id="exampleModalLabel">
                            <?php echo e(trans('Counters_trans.add_Grade')); ?>

                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- add_form -->
                        <form action="<?php echo e(route('countrys.store')); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="row">
                                <div class="col">
                                    <label for="name_ar" class="mr-sm-2"><?php echo e(trans('Counters_trans.name_ar')); ?>

                                        :</label>
                                    <input id="name_ar" type="text" name="name_ar" class="form-control">
                                </div>
                                <div class="col">
                                    <label for="name_en" class="mr-sm-2"><?php echo e(trans('Counters_trans.name_en')); ?>

                                        :</label>
                                    <input id="name_en" type="text" class="form-control" name="name_en" required>
                                </div>
                            </div>
                          
                          <div class="row">
                                <div class="col">
                                    <label for="currency_ar" class="mr-sm-2"><?php echo e(trans('Counters_trans.currency_ar')); ?>

                                        :</label>
                                    <input id="currency_ar" type="text" name="currency_ar" class="form-control">
                                </div>
                                <div class="col">
                                    <label for="currency_en" class="mr-sm-2"><?php echo e(trans('Counters_trans.currency_en')); ?>

                                        :</label>
                                    <input id="currency_en" type="text" class="form-control" name="currency_en" required>
                                </div>
                            </div>


                            <br>

                            <div class="row">
                                <div class="div_design">
                                    <label for="image"> image :</label>
                                    <input type="file" name="image" required="">
                                </div>

                            </div>

                            <br><br>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal"><?php echo e(trans('Counters_trans.Close')); ?></button>
                        <button type="submit" class="btn btn-success"><?php echo e(trans('Counters_trans.Submit')); ?></button>
                    </div>
                    </form>

                </div>
            </div>
        </div>

    </div>


<?php $__env->stopSection(); ?>




<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/azsystems-motors/htdocs/motors.azsystems.tech/public/resources/views/pages/countrys/countrys.blade.php ENDPATH**/ ?>