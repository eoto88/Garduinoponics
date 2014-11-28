<div class="row">
    <article class="col-sm-12 col-md-12 col-lg-6">
        <div class="widget-table widget">
            <header role="heading">
                <span class="widget-icon"><i class="fa fa-list-alt fa-fw "></i></span>
                <h2>Instances</h2>
            </header>
            <div class="widget-body">
                <table class="table table-bordered">
                    <tr><th><?php echo __('Title') ?></th><th><?php echo __('Type') ?></th><th><?php echo __('Code') ?></th></tr>
                    <?php
                    foreach ($instances as $instance) {
                        echo '<tr><td>' . $instance['title'] .'</td><td>' . $instance_types[$instance['type']] .'</td><td>' . $instance['code'] .'</td></tr>';
                    }
                    ?>
                </table>
            </div>
        </div>
    </article>
    <article class="col-sm-12 col-md-12 col-lg-6">
        <div class="widget-form widget">
            <header role="heading">
                <span class="widget-icon"><i class="fa fa-pencil fa-fw "></i></span>
                <h2><?php echo __('Instance form') ?></h2>
            </header>
            <div class="widget-body">
                <form role="form">
                    <div class="form-group">
                        <label for="title"><?php echo __('Title') ?></label>
                        <input type="text" class="form-control" id="title">
                     </div>
                    <div class="form-group">
                        <label for="code"><?php echo __('Code') ?></label>
                        <input type="text" class="form-control" id="code">
                    </div>
                    <div class="form-group">
                        <label for="type"><?php echo __('System type') ?></label>
                        <select id="type" class="form-control">
                            <?php foreach($instance_types as $key => $type) { ?>
                                <option value="<?php echo $key ?>"><?php echo $type ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </article>
</div>