<?php

require __DIR__.'/header.php';
$return = urlencode($_SERVER["REQUEST_URI"])

?>
<form action="public.php?return=<?php echo $return ?>"
	method="POST" id="loginForm" name="loginForm">

<input type="hidden" name="op" value="login">

<table height='100%' width='100%'><tr><td colspan='2'>
<h1><?php echo __("Not logged in") ?></h1></td></tr>

<tr><td align="right"><?php echo __("Login:") ?></td>
<td align="right"><input name="login"
	value="<?php echo $_SESSION["fake_login"] ?>"></td></tr>
	<tr><td align="right"><?php echo __("Password:") ?></td>
	<td align="right"><input type="password" name="password"
	value="<?php echo $_SESSION["fake_password"] ?>"></td></tr>
<tr><td colspan='2'>
	<button type="submit">
		<?php echo __('Log in') ?></button>

	<button onclick="return window.close()">
		<?php echo __('Cancel') ?></button>
</td></tr>
</table>

</form>
