<?php $this->extend('content.tpl.php'); ?>

<?php $this->start('content'); ?>
<p><b>LoginManager w/ Tico</b> Index page</p>
<?php if ($isLoggedIn) { ?>
<p>You are logged in as <b><?php echo $user->username; ?></b>, <a href="<?php echo tico()->uri('/logout'); ?>">logout</a></p>
<?php } else { ?>
<p>You are not logged in, <b><?php echo $user->username; ?></b>, <a href="<?php echo tico()->uri('/login'); ?>">login</a></p>
<?php } ?>
<?php $this->end('content'); ?>
