<?php $this->extend('content.tpl.php'); ?>

<?php $this->start('content'); ?>
<?php if (!empty($error)) { ?><div style="font-size:16px;color:red"><?php echo $error; ?></div><?php } ?>
<form method="post" action="<?php echo tico()->uri('/login'); ?>">
<label>Username</label>:<input type="text" name="username" />
<br />
<label>Password</label>:<input type="password" name="password" />
<br />
<button type="submit">Login</button>
</form>
<?php $this->end('content'); ?>
