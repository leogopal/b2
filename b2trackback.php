<?php

require("b2config.php");
require("$b2inc/b2vars.php");
require("$b2inc/b2functions.php");

dbconnect();

if (empty($HTTP_GET_VARS['tb_id'])) {
	die('blah');
}

$tb_id = $HTTP_GET_VARS['tb_id'];


if ((empty($HTTP_GET_VARS['__mode'])) && (!empty($HTTP_GET_VARS['url']))) {

	$url = addslashes($HTTP_GET_VARS['url']);
	$title = strip_tags($HTTP_GET_VARS['title']);
	$title = (strlen($title) > 255) ? substr($title, 0, 252).'...' : $title;
	$excerpt = strip_tags($HTTP_GET_VARS['excerpt']);
	$excerpt = (strlen($excerpt) > 255) ? substr($excerpt, 0, 252).'...' : $excerpt;
	$blog_name = strip_tags($HTTP_GET_VARS['blog_name']);
	$blog_name = (strlen($blog_name) > 255) ? substr($blog_name, 0, 252).'...' : $blog_name;

	$comment = '<trackback />';
	$comment .= "<a href=\"$url\">$title</a><br />$excerpt";

	$author = $blog_name;
	$email = '';
	$original_comment = $comment;
	$comment_post_ID = $HTTP_GET_VARS['tb_id'];
	$autobr = 1;

	$user_ip = $REMOTE_ADDR;
	$user_domain = gethostbyaddr($user_ip);
	$time_difference = get_settings("time_difference");
	$now = date("Y-m-d H:i:s",(time() + ($time_difference * 3600)));

	$comment = convert_chars($comment);
	$comment = format_to_post($comment);

	$comment_author = $author;
	$comment_author_email = $email;
	$comment_author_url = $url;

	$author = addslashes($author);

	$query = "INSERT INTO $tablecomments VALUES ('0','$comment_post_ID','$author','$email','$url','$user_ip','$now','$comment','0')";
	$result = mysql_query($query);
	if (!$result)
		die ("There is an error with the database, it can't store your comment...<br>Contact the <a href=\"mailto:$admin_email\">webmaster</a>");

	if ($comments_notify) {

		$notify_message  = "New comment on your post #$comment_post_ID.\r\n\r\n";
		$notify_message .= "author : $comment_author (IP: $user_ip , $user_domain)\r\n";
		$notify_message .= "e-mail : $comment_author_email\r\n";
		$notify_message .= "url    : $comment_author_url\r\n";
		$notify_message .= "comment: \n".stripslashes($original_comment)."\r\n\r\n";
		$notify_message .= "You can see all comments on this post there: \r\n";
		$notify_message .= "$siteurl/$blogfilename?p=$comment_post_ID&c=1\r\n\r\n";

		$postdata = get_postdata($comment_post_ID);
		$authordata = get_userdata($postdata["Author_ID"]);
		$recipient = $authordata["user_email"];
		$subject = "comment on post #$comment_post_ID \"".$postdata["Title"]."\"";

		@mail($recipient, $subject, $notify_message, "From: b2@$SERVER_NAME\r\n"."X-Mailer: b2 v0.6pre4 - PHP/" . phpversion());
		
	}

	header('Content-type: application/xml');
	echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?".">\n<response>\n<error>0</error>\n</response>";
	die();

} elseif (empty($HTTP_GET_VARS['__mode'])) {

	header('Content-type: application/xml');
	echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?".">\n<response>\n<error>1</error>\n";
	echo "<message>Tell me a lie. \nOr just a __mode or url parameter ?</message>\n";
	echo "</response>";

}

?>