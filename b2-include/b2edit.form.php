<?php
echo $tabletop;

switch($action) {
	case "post":
		$submitbutton_text = "Blog this !";
		$toprow_title = "New Post";
		$form_action = "post";
		$form_extra = "";
		$colspan = 3;
		break;
	case "edit":
		$submitbutton_text ="Edit this !";
		$toprow_title = "Editing Post #".$postdata["ID"];
		$form_action = "editpost";
		$form_extra = "\" />\n<input type=\"hidden\" name=\"post_ID\" value=\"$post";
		$colspan = 2;
		break;
	case "editcomment":
		$submitbutton_text ="Edit this !";
		$toprow_title = "Editing Comment #".$commentdata["comment_ID"];
		$form_action = "editedcomment";
		$form_extra = "\" />\n<input type=\"hidden\" name=\"comment_ID\" value=\"$comment\" />\n<input type=\"hidden\" name=\"comment_post_ID\" value=\"".$commentdata["comment_post_ID"];
		$colspan = 3;
		break;
}

?>

<form name="post" action="b2edit.php" method="POST">
<input type="hidden" name="user_ID" value="<?php echo $user_ID ?>" />
<input type="hidden" name="action" value="<?php echo $form_action.$form_extra ?>" />

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td<?php 

if ($action != "editcomment") {

// this is for everything but comment editing
	?>>
	<table height="60" align="left" cellpadding="0" cellspacing="0">
		<td height="60" width="190">
		<b>Title :</b> <br />
		<input type="text" name="post_title" size="20" tabindex="1" style="width: 170px;" value="<?php echo $edited_post_title; ?>" />
		</td>
		<td>
		<b>Category : </b><br /><?php dropdown_categories(); ?>
		</td>
	</table>
	<?php

} else {
	
// this is for comment editing
	?> colspan="2">&nbsp;</td>
</tr>

<tr>
	<td>
	<b>Name :</b><br />
	<input type="text" name="newcomment_author" size="20" value="<?php echo format_to_edit($commentdata["comment_author"]) ?>" tabindex="1" /></td>
	<td>
	<b>E-mail :</b><br />
	<input type="text" name="newcomment_author_email" size="20" value="<?php echo format_to_edit($commentdata["comment_author_email"]) ?>" tabindex="2" /></td>
	<td>
	<b>URL :</b><br />
	<input type="text" name="newcomment_author_url" size="20" value="<?php echo format_to_edit($commentdata["comment_author_url"]) ?>" tabindex="3" />
	<?php
	
}

	?>
	</td>
</tr>
<tr>
<td colspan="<?php echo $colspan; ?>">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<td valign="bottom">
<?php
if ($action != "editcomment") {
	echo "<b>Post :</b>";
} else {
	echo "<br /><b>Comment :</b>";
}
?>
</td>
<td valign="bottom" align="right">
<?php if ($use_quicktags) include($b2inc."/b2quicktags.php"); ?>
</td>
</table>

<textarea rows="9" cols="40" style="width:100%" name="content" tabindex="4" wrap="virtual"><?php echo $content ?></textarea><br />

<input type="checkbox" class="checkbox" name="post_autobr" value="1" <?php
if ($autobr)
echo " checked" ?> tabindex="6" /> Auto-BR (converts line-breaks into &lt;br /> tags)<br />

<?php if ($use_preview) { ?>
<input type="button" value="preview" onclick="preview(this.form);" class="search" />
<?php } ?>

<input type="submit" name="submit" value="<?php echo $submitbutton_text ?>" class="search" style="font-weight: bold;" tabindex="5" /> 

<?php if ($use_spellchecker) { ?>
<!--<input type = "button" value = "Spell Check" onclick="var f=document.forms[0]; doSpell( 'en', f.post_content, '<?php echo $spellchecker_url ?>/sproxy.cgi', true);" class="search" tabindex="5" />-->
<input type="button" value="Check Spelling" onclick="DoSpell
('post','content','');" class="search" />
<?php } ?>

<?php if ( ($use_fileupload) && ($user_level >= $fileupload_minlevel) && ((ereg(" ".$user_login." ", $fileupload_allowedusers)) || (trim($fileupload_allowedusers)=="")) ) { ?>
<input type="button" value="upload a file/image" onclick="launchupload();" class="search" />
<?php } ?>

<?php
// if the level is 5+, allow user to edit the timestamp - not on 'new post' screen though
#if (($user_level > 4) && ($action != "post"))
if ($user_level > 4) {
	touch_time(($action=="edit"));
}
?>
<script language="JavaScript">
<!--
//	document.blog.post_content.focus();
//-->
</script>
</td>
</tr>
</table>
<?php echo $tablebottom ?>
</form>