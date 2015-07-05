<?php 

require __DIR__.'/header.php';
$title = htmlspecialchars($_REQUEST["title"]);
$url = htmlspecialchars($_REQUEST["url"]);

?>
<table height='100%' width='100%'><tr><td colspan='2'>
<h1><?php echo __("Share with Tiny Tiny RSS") ?></h1>
</td></tr>

<form id='share_form' name='share_form'>

<input type="hidden" name="op" value="sharepopup">
<input type="hidden" name="action" value="share">

<?php if(!empty($_REQUEST["xdebug"])): ?>
<input type="hidden" name="xdebug" value="<?php echo htmlspecialchars($_REQUEST["xdebug"]); ?>">
<?php endif; ?>

<tr><td align='right'><?php echo __("Title:") ?></td>
<td width='80%'><input name='title' value="<?php echo $title ?>"></td></tr>
<tr><td align='right'><?php echo __("URL:") ?></td>
<td><input name='url' value="<?php echo $url ?>"></td></tr>
<tr><td align='right'><?php echo __("Labels:") ?></td>
<td><input name='labels' id="labels_value"
	placeholder='Alpha, Beta, Gamma' value="">
</td></tr>

<tr><td>
	<div class="autocomplete" id="labels_choices"
		style="display : block"></div></td></tr>

<script type='text/javascript'>document.forms[0].title.focus();</script>

<script type='text/javascript'>
	new Ajax.Autocompleter('labels_value', 'labels_choices',
   "backend.php?op=rpc&method=completeLabels",
   { tokens: ',', paramName: "search" });
</script>

<tr><td colspan='2'>
	<div style='float : right' class='insensitive-small'>
	<?php echo __("Shared article will appear in the Published feed.") ?>
	</div>
	<button type="submit"><?php echo __('Share') ?></button>
	<button onclick="return window.close()"><?php echo __('Cancel') ?></button>
	</div>

</form>
</td></tr></table>
</body></html>
