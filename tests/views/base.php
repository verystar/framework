<html>
    <head>
        <title>App Name</title>
    </head>
    <body>
        <?php view()->start('sidebar'); ?>
        这里是父类的sidebar
        <?php echo view()->yieldContnet(); ?>

        <?php echo $b;?>

        <div class="container">
            <?php echo view()->content('content'); ?>
        </div>
    </body>
</html>