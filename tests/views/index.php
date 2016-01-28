<?php view()->extend('base');?>
<?php view()->start('sidebar'); ?>

    <p>这里是新的sidebar.</p>
<?php view()->stop(); ?>

<?php view()->start('content'); ?>
    <p>This is my body content.</p>
<?php view()->stop(); ?>
extend before content
