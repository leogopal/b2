<?php

# pop3-2-b2 mail to blog
# v0.2 20020404

include("b2config.php");
include($b2inc."/b2vars.php");
include($b2inc."/class.POP3.php3");
include($b2inc."/b2functions.php");

dbconnect();

$use_cache = 1;
$autobr = get_settings("autobr");
$time_difference = get_settings("time_difference");

error_reporting(2037);

$pop3 = new POP3();


if(!$pop3->connect($mailserver_url, $mailserver_port)) {
	echo "Ooops $pop3->ERROR <br />\n";
		exit;
}

$Count = $pop3->login($mailserver_login,$mailserver_pass);
if( (!$Count) or ($Count == -1) ) {
	echo "<H1>Login Failed: $pop3->ERROR</H1>\n";
	exit;
}


// ONLY USE THIS IF YOUR PHP VERSION SUPPORTS IT!
//register_shutdown_function($pop3->quit());

echo "<p>$Count messages in the Inbox</p>";

for ($iCount=1; $iCount<=$Count; $iCount++) {


$MsgOne = $pop3->get($iCount);
#echo $iCount;
if( (!$MsgOne) or (gettype($MsgOne) != "array") ) {
	echo "oops, $pop3->ERROR<br />\n";
	exit;
}

$content = '';
$bodysignal = 0;
$loginsignal = 0;
$content_type = '';
$boundary = '';
$dmonths = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
while ( list ( $lineNum,$line ) = each ($MsgOne) ) {
	if (strlen($line) < 3) {
		$bodysignal = 1;
	}
	if ($bodysignal) {
		$content .= $line;
	} else {
		if (preg_match('/Content-Type: /', $line)) {
			$content_type = trim($line);
			$content_type = substr($content_type, 14, strlen($content_type)-14);
			$content_type = explode(';', $content_type);
			$content_type = $content_type[0];
#			if (preg_match('/</', $content_type)) {
#				$from = preg_match('/<(.+?)>/', $from, $email);
#				$from = substr($email[0], 1, strlen($email[0])-2);
#			} else {
#				$from = substr($from, 6, strlen($from)-6);
#			}
		}
		if (($content_type == 'multipart/alternative') && (preg_match('/boundary="/', $line)) && ($boundary == '')) {
			$boundary = trim($line);
			$boundary = explode('"', $boundary);
			$boundary = $boundary[1];
		}
		if (preg_match('/Subject: /', $line)) {
			$subject = trim($line);
			$subject = substr($subject, 9, strlen($subject)-9);
			if ($use_phoneemail) {
				$subject = explode($phoneemail_separator, $subject);
				$subject = trim($subject[0]);
			}
			if (!ereg($subjectprefix, $subject)) {
				echo $subject.' : ';
				echo 'no subject prefix<br />';
				continue;
			}
		}
		if (preg_match('/Date: /', $line)) { // 20 Mar 2002 20:32:37
			$ddate = trim($line);
			$ddate = str_replace('Date: ', '', $ddate); //substr($ddate, 6, strlen($ddate)-6);
			$j = substr($ddate,0,1);
			if ( ($j == "M") || ($j == "T") || ($j == "W") || ($j == "F") || ($j == "S") ) {
				$ddate = substr($ddate, 5, strlen($ddate)-5);
			}
			$ddate_H = substr($ddate, 12, 2);
			$ddate_i = substr($ddate, 15, 2);
			$ddate_s = substr($ddate, 18, 2);
			$ddate_m = substr($ddate, 3, 3);
			$ddate_d = substr($ddate, 0, 2);
			$ddate_Y = substr($ddate, 7, 4);
			for ($i=0; $i<12; $i++) {
				if ($ddate_m == $dmonths[$i]) {
					$ddate_m = $i+1;
				}
			}
			$ddate_U = mktime($ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y);
			$ddate_U = $ddate_U + ($time_difference * 3600);
			$post_date = date("Y-m-d H:i:s", $ddate_U);
		}
	}
}

$ddate_today = time() + ($time_difference * 3600);
$ddate_difference_days = ($ddate_today - $ddate_U) / 86400;

if ($ddate_difference_days > 2) {
#	echo 'too old<br />';
#	continue;
}

if (preg_match('/'.$subjectprefix.'/', $subject)) {

	echo "\n$iCount : $subject<br />\n";

#	$fp = fopen('b2mail.txt','a+');

	$subject = str_replace($subjectprefix, '', $subject);

	if ($content_type == 'multipart/alternative') {
		$content = explode('--'.$boundary, $content);
#		print_r($content);
#		$content = $content[count($content)-2];
		$content = $content[2];
#		echo $content;
		$content = explode('Content-Transfer-Encoding: quoted-printable', $content);
#		print_r($content);
		$content = trim($content[1]);
		$content = strip_tags($content, '<img><p><br>');
	}
	
	if ($use_phoneemail) {
		$btpos = strpos($content, $phoneemail_separator);
		if ($btpos) {
			$btpos_left  = $btpos+(strlen($phoneemail_separator));
			$btpos_right = strlen($content) - $btpos_left;
			$content = substr($content, $btpos_left, $btpos_right);
		}
	}

	$btpos = strpos($content, $bodyterminator);
	if ($btpos) {
		$content = substr($content, 0, $btpos);
	}

	$content = trim($content);

	$line = explode("\n", $content);
	$line = $line[0];
	if ($use_phoneemail) {
		$btpos = strpos($line, $phoneemail_separator);
		if ($btpos) {
			$content = trim(substr($content, $btpos+strlen($phoneemail_separator), strlen($content)));
			$line = trim(substr($line, 0, $btpos));
		}
	}

	echo "<p>$content_type : $boundary<br />".$content.'</p>';

	$line = explode(':', trim($line));
	$user_login = $line[0];
	$user_pass = $line[1];
	if ($use_phoneemail) {
		$user_pass = explode($phoneemail_separator, $user_pass);
		$user_pass = trim($user_pass[0]);
	}

	$sql = "SELECT ID, user_level FROM $tableusers WHERE user_login='$user_login' AND user_pass='$user_pass' ORDER BY ID DESC LIMIT 1";
	$result = mysql_query($sql);

#	fwrite($fp, "\n-----\n".$sql."\n*\n");

	if (!mysql_num_rows($result)) {

		echo "$user_login ? no user with this login...";
		fwrite($fp, "$user_login ? no user with this login... here's the content of the mail: \n");
		fclose($fp);
		continue;

	}

	$row = mysql_fetch_object($result);
	$user_level = $row->user_level;
	$post_author = $row->ID;

	if ($user_level > 0) {

		$post_title = xmlrpc_getposttitle($content);
		$post_category = xmlrpc_getpostcategory($content);

		if ($post_title == "") { $post_title = $subject; }
		if ($post_category == "") { $post_category = $default_category; }

		$debugmsg  = "<p>This is what I would have posted:</p>\n";
		$debugmsg .= "<p>";
		$debugmsg .= "Title: $post_title<br />\n";
		$debugmsg .= "Author: ID:$post_author Login:$user_login<br />\n";
		$debugmsg .= "Date: $post_date<br />\n";
		$debugmsg .= "Content: ".autobrize($content)."<br />\n";
		$debugmsg .= "</p>";

		if (!$thisisforfunonly) {
			$post_title = addslashes(trim($post_title));
			$content = addslashes(trim($content));
			$sql = "INSERT INTO $tableposts (post_author, post_date, post_content, post_title, post_category) VALUES ($post_author, '$post_date', '$content', '$post_title', $post_category)";
			$result = mysql_query($sql) or die("Couldn't add post: ".mysql_error());
#			fwrite($fp, $sql."\n*\n");
		}

#		fwrite($fp, $debugmsg);
		
		if(!$pop3->delete($iCount)) {
			echo "oops $pop3->ERROR <br />\n";
			$pop3->reset();
			exit;
		} else {
			echo "Message $iCount Deleted <br />\n";
		}

#		fclose($fp);

	} else {
#		echo "Sorry, $user_login doesn't have the right to post here yet.";
	}
	echo '<p>&nbsp;</p>';

}
}

$pop3->quit();

exit;

?>
