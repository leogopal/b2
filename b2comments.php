<?php // Do not delete these lines
$pagenow = basename($HTTP_SERVER_VARS["SCRIPT_FILENAME"]);
if ($pagenow == "b2comments.php")
	die ("please, do not load this page directly");
if (($withcomments) or ($c)) {

	$comment_author = (empty($HTTP_COOKIE_VARS["comment_author"])) ? "name" : $HTTP_COOKIE_VARS["comment_author"];
	$comment_author_email = (empty($HTTP_COOKIE_VARS["comment_author"])) ? "email" : trim($HTTP_COOKIE_VARS["comment_author_email"]);
	$comment_author_url = (empty($HTTP_COOKIE_VARS["comment_author"])) ? "url" : trim($HTTP_COOKIE_VARS["comment_author_url"]);

$queryc = "SELECT * FROM $tablecomments WHERE comment_post_ID = $id ORDER BY comment_date";
$resultc = mysql_query($queryc);
if ($resultc) {
?>



<!-- you can start editing here -->

<a name="comments"></a>
<p><strong><span style="color: #0099CC">::</span> comments</strong></p>

<?php // these lines are b2's motor, do not delete
while($rowc = mysql_fetch_object($resultc)) {
	$commentdata = get_commentdata($rowc->comment_ID);
?><a name="c<?php comment_ID() ?>"></a>
	
<!-- comment -->
<p>
<b><?php comment_author() ?> <?php comment_author_email_link("email", " - ", "") ?><?php comment_author_url_link("url", " - ", "") ?></b>
<br />
<?php comment_text() ?>
<br />
<?php comment_date() ?> @ <?php comment_time() ?>
</p>
<!-- /comment -->


<?php //end of the loop, don't delete
}

?>

<p><strong><span style="color: #0099CC">::</span> leave a comment</strong></p>


<!-- form to add a comment -->

<form action="b2comments.post.php" method="post">
	<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
	<input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($HTTP_SERVER_VARS["REQUEST_URI"]); ?>" />
	<input type="text" name="author" class="textarea" value="<?php echo $comment_author ?>" size="20" tabindex="1" /><br />
	<input type="text" name="email" class="textarea" value="<?php echo $comment_author_email ?>" size="20" tabindex="2" /><br />
	<input type="text" name="url" class="textarea" value="<?php echo $comment_author_url ?>" size="20" tabindex="3" /><br />
	<textarea cols="40" rows="4" name="comment" tabindex="4" class="textarea">comment</textarea><br />
	<input type="checkbox" name="comment_autobr" value="1" <?php
	if ($autobr)
	echo " checked=\"checked\"" ?> tabindex="6" /> Auto-BR (line-breaks become &lt;br> tags)<br />
	<input type="submit" name="submit" class="buttonarea" value="ok" tabindex="5" />

</form>

<!-- /form -->


<p><b><font color="#0099CC">::</font> <a href="javascript:history.go(-1)">return to the blog</a></b></p>

<?php // if you delete this the sky will fall on your head
}
} else {
	return false;
}
?>