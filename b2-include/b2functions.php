<?php

/* functions... */

function get_currentuserinfo() { // a bit like get_userdata(), on steroids
	global $HTTP_COOKIE_VARS,$user_login,$userdata,$user_level,$user_ID,$user_nickname,$user_email,$user_url,$user_pass_md5;
	// *** retrieving user's data from cookies and db - no spoofing
	$user_login = $HTTP_COOKIE_VARS["cafeloguser"];
	$userdata = get_userdatabylogin($user_login);
	$user_level = $userdata["user_level"];
	$user_ID=$userdata["ID"];
	$user_nickname=$userdata["user_nickname"];
	$user_email=$userdata["user_email"];
	$user_url=$userdata["user_url"];
	$user_pass_md5=md5($userdata["user_pass"]);
/*	$pref_usequicktags=$userdata["pref_usequicktags"];
	$pref_postnavigator=$userdata["pref_postnavigator"];
	$pref_showinactiveusers=$userdata["pref_showinactiveusers"];
	$pref_textarearows=$userdata["pref_textarearows"];
	$pref_confirm=$userdata["pref_confirm"];
	$pref_usespellchecker=$userdata["pref_usespellchecker"];
*/	// *** /retrieving
}



function dbconnect() {
	global $connexion, $server, $loginsql, $passsql, $base;
	$connexion = mysql_connect($server,$loginsql,$passsql) or die("Can't connect to the database server. MySQL said:<br />".mysql_error());
	$connexionbase = mysql_select_db("$base") or die("Can't connect to the database $base. MySQL said:<br />".mysql_error());
	return(($connexion && $connexionbase));
}

/***** Formatting functions *****/


function autobrize($content) {
	$content = preg_replace("/<br>\n/","\n",$content);
	$content = preg_replace("/<br \/>\n/","\n",$content);
	$content = preg_replace("/(\015\012)|(\015)|(\012)/","<br />\n",$content);
	return($content);
	}
function unautobrize($content) {
	$content = eregi_replace("<br />\n","\n",$content);
	$content = eregi_replace("<br>\n","\n",$content);   //for PHP versions before 4.0.5
	return($content);
	}


function format_to_edit($content) {
	global $autobr;
	$content = stripslashes($content);
	if ($autobr) { $content = unautobrize($content); }
	$content = htmlspecialchars($content);
	return($content);
	}
function format_to_post($content) {
	global $post_autobr,$comment_autobr;
	$content = addslashes($content);
	if ($post_autobr || $comment_autobr) { $content = autobrize($content); }
	return($content);
	}


function zeroise($number,$threshold) { // function to add leading zeros when necessary
	$l=strlen($number);
	if ($l<$threshold)
		for ($i=0; $i<($threshold-$l); $i=$i+1) { $number="0".$number;	}
	return($number);
	}


function backslashit($string) {
	$string = preg_replace('/([a-z])/i', '\\\\\1', $string);
	return $string;
}


function mysql2date($dateformatstring, $mysqlstring) {
	global $month, $weekday;
	$m = $mysqlstring;
	$i = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4)); 
	if ((!empty($month)) && (!empty($weekday))) {
		$datemonth = $month[date('m', $i)];
		$dateweekday = $weekday[date('w', $i)];
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit(substr($dateweekday, 0, 3)), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit(substr($datemonth, 0, 3)), $dateformatstring);
		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	if (!$j) {
# // for debug purposes
#		echo $i." ".$mysqlstring;
	}
	return $j;
	}

function date_i18n($dateformatstring, $unixtimestamp) {
	global $month, $weekday;
	$i = $unixtimestamp; 
	if ((!empty($month)) && (!empty($weekday))) {
		$datemonth = $month[date('m', $i)];
		$dateweekday = $weekday[date('w', $i)];
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit(substr($dateweekday, 0, 3)), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit(substr($datemonth, 0, 3)), $dateformatstring);
		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	return $j;
	}



function get_weekstartend($mysqlstring, $start_of_week) {
	$my = substr($mysqlstring,0,4);
	$mm = substr($mysqlstring,8,2);
	$md = substr($mysqlstring,5,2);
	$day = mktime(0,0,0, $md, $mm, $my);
	$weekday = date('w',$day);
#	echo $weekday;
	$i = 86400;
	while ($weekday > $start_of_week) {
		$weekday = date('w',$day);
		$day = $day - 86400;
		$i = 0;
	}
	$week['start'] = $day + 86400 - $i;
	$week['end']   = $day + 691199;
	return ($week);
}

function convert_chars($content,$flag="html") { // html/unicode entities output, defaults to html
	$newcontent = "";

	global $convert_chars2unicode, $convert_entities2unicode, $leavecodealone, $use_htmltrans;
	global $b2_htmltrans, $b2_htmltranswinuni;

	### this is temporary - will be replaced by proper config stuff
	$convert_chars2unicode = 1;
	if (($leavecodealone) || (!$use_htmltrans)) {
		$convert_chars2unicode = 0;
	}
	###


	// converts HTML-entities to their display values in order to convert them again later

	$content = preg_replace("/<title>(.+?)<\/title>/","",$content);
	$content = preg_replace("/<category>(.+?)<\/category>/","",$content);
	
	$content = str_replace("&amp;","&#38;",$content);
	$content = strtr($content, $b2_htmltrans);

	for ($i=0; $i<strlen($content); $i=$i+1) {
		$j = substr($content,$i,1);
		$jnext = substr($content,$i+1,1);
		$jord = ord($j);
		if ($convert_chars2unicode) {
			switch($flag) {
				case "unicode":
	//				$j = str_replace("&","&#38;",$j);
					if (($jord>=128) || ($j == "&") || (($jord>=128) && ($jord<=159))) {
						$j = "&#".$jord.";";
					}
					break;
				case "html":
					if (($jord>=128) || (($jord>=128) && ($jord<=159))) {
						$j = "&#".$jord.";"; // $j = htmlentities($j);
					} elseif (($j == "&") && ($jnext != "#")) {
						$j = "&#38;";
					}
					break;
				case "xml":
					if ($jord>=128) {
						$j = "&#".$jord.";"; // $j = htmlentities($j);
	//					$j = htmlentities($j);
					} elseif (($j == "&") && ($jnext != "#")) {
						$j = "&#38;";
					}
					break;
			}
		}

		$newcontent .= $j;
	}

	// now converting: Windows CP1252 => Unicode (valid HTML)
	// (if you've ever pasted text from MSWord, you'll understand)

	$newcontent = strtr($newcontent, $b2_htmltranswinuni);

	// you can delete these 2 lines if you don't like <br /> and <hr />
	$newcontent = str_replace("<br>","<br />",$newcontent);
	$newcontent = str_replace("<hr>","<hr />",$newcontent);

	return($newcontent);
}

function convert_bbcode($content) {
	global $b2_bbcode, $use_bbcode;
	if ($use_bbcode) {
		$content = preg_replace($b2_bbcode["in"], $b2_bbcode["out"], $content);
	}
	$content = convert_bbcode_email($content);
	return ($content);
}

function convert_bbcode_email($content) {
	global $use_bbcode;
	$bbcode_email["in"] = array(
		'#\[email](.+?)\[/email]#eis',
		'#\[email=(.+?)](.+?)\[/email]#eis'
	);
	$bbcode_email["out"] = array(
		"'<a href=\"mailto:'.antispambot('\\1').'\">'.antispambot('\\1').'</a>'",		// E-mail
		"'<a href=\"mailto:'.antispambot('\\1').'\">\\2</a>'"
	);

	$content = preg_replace($bbcode_email["in"], $bbcode_email["out"], $content);
	return ($content);
}

function convert_gmcode($content) {
	global $b2_gmcode, $use_gmcode;
	if ($use_gmcode) {
		$content = preg_replace($b2_gmcode["in"], $b2_gmcode["out"], $content);
	}
	return ($content);
}

function convert_smilies($content) {
	global $b2smiliestrans, $smilies_directory, $use_smilies;
	if ($use_smilies) {
		foreach($b2smiliestrans as $smiley => $img) {
			$content = str_replace($smiley, "<img src='$smilies_directory/$img' alt='$smiley' />", $content);
		}
	}
	return ($content);
}

function antispambot($emailaddy) {
	$emailNOSPAMaddy = '';
	srand ((float) microtime() * 1000000);
	for ($i = 0; $i < strlen($emailaddy); $i = $i + 1) {
		if (floor(rand(0,1))) {
			$emailNOSPAMaddy .= "&#".ord(substr($emailaddy,$i,1)).";";
		} else {
			$emailNOSPAMaddy .= substr($emailaddy,$i,1);
		}
	}
	$emailNOSPAMaddy = str_replace('@','&#64;',$emailNOSPAMaddy);
	return $emailNOSPAMaddy;
}

function make_clickable($text) { // original function: phpBB, extended here for AIM & ICQ
    $ret = " " . $text;
    $ret = preg_replace("#([\n ])([a-z]+?)://([^, <>{}\n\r]+)#i", "\\1<a href=\"\\2://\\3\" target=\"_blank\">\\2://\\3</a>", $ret);
    $ret = preg_replace("#([\n ])aim:([^,< \n\r]+)#i", "\\1<a href=\"aim:goim?screenname=\\2\\3&message=Hello\">\\2\\3</a>", $ret);
    $ret = preg_replace("#([\n ])icq:([^,< \n\r]+)#i", "\\1<a href=\"http://wwp.icq.com/scripts/search.dll?to=\\2\\3\">\\2\\3</a>", $ret);
    $ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,< \n\r]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\" target=\"_blank\">www.\\2.\\3\\4</a>", $ret);
    $ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([^,< \n\r]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);
    $ret = substr($ret, 1);
    return($ret);
}


function is_email($user_email) {
	$chars = "/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i";
	if(strstr($user_email, '@') && strstr($user_email, '.')) {
		if (preg_match($chars, $user_email)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


/***** // Formatting functions *****/



function get_lastpostdate() {
	global $tableposts,$cache_lastpostdate,$use_cache;
	if ((!isset($cache_lastpostdate)) OR (!$use_cache)) {
		$sql = "SELECT * FROM $tableposts ORDER BY post_date DESC LIMIT 1";
		$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
		$querycount++;
		$myrow = mysql_fetch_object($result);
		$lastpostdate = $myrow->post_date;
		$cache_lastpostdate = $lastpostdate;
//		echo $lastpostdate;
	} else {
		$lastpostdate = $cache_lastpostdate;
	}
	return($lastpostdate);
}

function user_pass_ok($user_login,$user_pass) {
	global $cache_userdata,$use_cache;
	if ((empty($cache_userdata["$user_login"])) OR (!$use_cache)) {
		$userdata = get_userdatabylogin($user_login);
	} else {
		$userdata = $cache_userdata["$user_login"];
	}
	return ($user_pass == $userdata["user_pass"]);
}

function get_userdata($userid) {
	global $tableusers,$querycount,$cache_userdata,$use_cache;
	if ((empty($cache_userdata[$userid])) OR (!$use_cache)) {
		$sql = "SELECT * FROM $tableusers WHERE ID = '$userid'";
		$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
		$myrow = mysql_fetch_array($result);
		$querycount++;
		$cache_userdata[$userid] = $myrow;
	} else {
		$myrow = $cache_userdata[$userid];
	}
	return($myrow);
}

function get_userdata2($userid) { // for team-listing
	global $tableusers,$row;
	$user_data["ID"] = $userid;
	$user_data["user_login"] = $row->user_login;
	$user_data["user_firstname"] = $row->user_firstname;
	$user_data["user_lastname"] = $row->user_lastname;
	$user_data["user_nickname"] = $row->user_nickname;
	$user_data["user_level"] = $row->user_level;
	$user_data["user_email"] = $row->user_email;
	$user_data["user_email"] = $row->user_email;
	$user_data["user_email"] = $row->user_email;
	$user_data["user_email"] = $row->user_email;
	$user_data["user_url"] = $row->user_url;
	return($user_data);
}

function get_userdatabylogin($user_login) {
	global $tableusers,$querycount,$cache_userdata,$use_cache;
	if ((empty($cache_userdata["$user_login"])) OR (!$use_cache)) {
		$sql = "SELECT * FROM $tableusers WHERE user_login = '$user_login'";
		$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
		if (!$result)	die($sql."<br /><br />".mysql_error());
		$myrow = mysql_fetch_array($result);
		$querycount++;
		$cache_userdata["$user_login"] = $myrow;
	} else {
		$myrow = $cache_userdata["$user_login"];
	}
	return($myrow);
}

function get_userid($user_login) {
	global $tableusers,$querycount,$cache_userdata,$use_cache;
	if ((empty($cache_userdata["$user_login"])) OR (!$use_cache)) {
		$sql = "SELECT ID FROM $tableusers WHERE user_login = '$user_login'";
		$result = mysql_query($sql) or die("No user with the login <i>$user_login</i>");
		$myrow = mysql_fetch_array($result);
		$querycount++;
		$cache_userdata["$user_login"] = $myrow;
	} else {
		$myrow = $cache_userdata["$user_login"];
	}
	return($myrow[0]);
}

function get_usernumposts($userid) {
	global $tableusers,$tablesettings,$tablecategories,$tableposts,$tablecomments,$querycount;
	$sql = "SELECT * FROM $tableposts WHERE post_author = $userid";
	$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
	$querycount++;
	return mysql_num_rows($result);
}

function get_settings($setting) {
	global $tablesettings,$querycount,$cache_settings,$use_cache;
	if ((empty($cache_settings)) OR (!$use_cache)) {
		$sql = "SELECT * FROM $tablesettings";
		$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
		$querycount++;
		$myrow = mysql_fetch_object($result);
		$cache_settings = $myrow;
	} else {
		$myrow = $cache_settings;
	}
	return($myrow->$setting);
}

function get_postdata($postid) {
	global $tableusers,$tablesettings,$tablecategories,$tableposts,$tablecomments,$querycount;
	$sql = "SELECT * FROM $tableposts WHERE ID = $postid";
	$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
	$querycount++;
	$myrow = mysql_fetch_object($result);
	$postdata = array (
		"ID" => $myrow->ID, 
		"Author_ID" => $myrow->post_author, 
		"Date" => $myrow->post_date, 
		"Content" => $myrow->post_content, 
		"Title" => $myrow->post_title, 
		"Category" => $myrow->post_category, 
		);
	return($postdata);
}

function get_postdata2($postid=0) { // less flexible, but saves mysql queries
	global $row;
	$postdata = array (
		"ID" => $row->ID, 
		"Author_ID" => $row->post_author,
		"Date" => $row->post_date,
		"Content" => $row->post_content,
		"Title" => $row->post_title,
		"Category" => $row->post_category,
#		"Notify" => $row->post_notifycomments,
#		"Clickable" => $row->post_make_clickable,
		"Karma" => $row->post_karma // this isn't used yet
		);
	return($postdata);
}

function get_commentdata($comment_ID,$no_cache=0) { // less flexible, but saves mysql queries
	global $rowc,$id,$commentdata,$tablecomments,$querycount;
	if ($no_cache) {
		$query="SELECT * FROM $tablecomments WHERE comment_ID = $comment_ID";
		$result=mysql_query($query);
		$querycount++;
		$myrow = mysql_fetch_array($result);
	} else {
		$myrow["comment_ID"]=$rowc->comment_ID;
		$myrow["comment_post_ID"]=$rowc->comment_post_ID;
		$myrow["comment_author"]=$rowc->comment_author;
		$myrow["comment_author_email"]=$rowc->comment_author_email;
		$myrow["comment_author_url"]=$rowc->comment_author_url;
		$myrow["comment_author_IP"]=$rowc->comment_author_IP;
		$myrow["comment_date"]=$rowc->comment_date;
		$myrow["comment_content"]=$rowc->comment_content;
		$myrow["comment_karma"]=$rowc->comment_karma;
	}
	return($myrow);
}

function get_catname($cat_ID) {
	global $tablecategories,$cache_catnames,$use_cache,$querycount;
	if ((!$cache_catnames) || (!$use_cache)) {
		$sql = "SELECT * FROM $tablecategories";
		$result = mysql_query($sql) or die("Oops, couldn't query the db for categories.");
		$querycount;
		while ($row = mysql_fetch_object($result)) {
			$cache_catnames[$row->cat_ID] = $row->cat_name;
		}
	}
	$cat_name = $cache_catnames[$cat_ID];
	return($cat_name);
}

function profile($user_login) {
	global $user_data;
	echo "<a href=\"#\" OnClick=\"javascript:window.open('b2profile.php?user=".$user_data["user_login"]."','Profile','toolbar=0,status=1,location=0,directories=0,menuBar=1,scrollbars=1,resizable=0,width=480,height=320,left=100,top=100');\">$user_login</a>";
}

function dropdown_categories($blog_ID=1) {
	global $postdata,$tablecategories,$mode,$querycount;
	$query="SELECT * FROM $tablecategories";
	$result=mysql_query($query);
	$querycount++;
	$width = ($mode=="sidebar") ? "100%" : "170px";
	echo "<select name=\"post_category\" style=\"width:".$width.";\" tabindex=\"2\">";
	while($row = mysql_fetch_object($result)) {
		echo "<option value=\"".$row->cat_ID."\"";
		if ($row->cat_ID == $postdata["Category"])
			echo " selected";
		echo ">".$row->cat_name."</option>";
	}
	echo "</select>";
}

function touch_time($edit=1) {
	global $month, $postdata;
	echo $postdata["Date"];
	echo "<br /><br /><input type=\"checkbox\" class=\"checkbox\" name=\"edit_date\" value=\"1\" />Edit timestamp ?<br />";
	
	$jj = ($edit) ? mysql2date("d", $postdata["Date"]) : date("d");
	$mm = ($edit) ? mysql2date("m", $postdata["Date"]) : date("m");
	$aa = ($edit) ? mysql2date("Y", $postdata["Date"]) : date("Y");
	$hh = ($edit) ? mysql2date("H", $postdata["Date"]) : date("H");
	$mn = ($edit) ? mysql2date("i", $postdata["Date"]) : date("i");
	$ss = ($edit) ? mysql2date("s", $postdata["Date"]) : date("s");

	echo "<input type=\"text\" name=\"jj\" value=\"$jj\" size=\"2\" maxlength=\"2\" />\n";
	echo "<select name=\"mm\">\n";
	for ($i=1; $i < 13; $i=$i+1) {
		echo "\t\t\t<option value=\"$i\"";
		if ($i == $mm)
		echo " selected";
		if ($i < 10) {
			$ii = "0".$i;
		} else {
			$ii = "$i";
		}
		echo ">".$month["$ii"]."</option>\n";
	}
	echo "</select>\n";
	echo "<input type=\"text\" name=\"aa\" value=\"$aa\" size=\"4\" maxlength=\"5\" />\n";
	echo "  @  ";
	echo "<input type=\"text\" name=\"hh\" value=\"$hh\" size=\"2\" maxlength=\"2\" />\n : ";
	echo "<input type=\"text\" name=\"mn\" value=\"$mn\" size=\"2\" maxlength=\"2\" />\n : ";
	echo "<input type=\"text\" name=\"ss\" value=\"$ss\" size=\"2\" maxlength=\"2\" />\n";
}

function gzip_compression() {
	global $gzip_compressed;
		if (!$gzip_compressed) {
		$phpver = phpversion(); //start gzip compression
		if($phpver >= "4.0.4pl1") {
			if(extension_loaded("zlib")) { ob_start("ob_gzhandler"); }
		} else if($phpver > "4.0") {
			if(strstr($HTTP_SERVER_VARS['HTTP_ACCEPT_ENCODING'], 'gzip')) {
				if(extension_loaded("zlib")) { $do_gzip_compress = TRUE; ob_start(); ob_implicit_flush(0); header("Content-Encoding: gzip");  }
			}
		} //end gzip compression - that piece of script courtesy of the phpBB dev team
		$gzip_compressed=1;
	}
}

function alert_error($msg) { // displays a warning box with an error message (original by KYank)
	global $$HTTP_SERVER_VARS;
	?>
	<html>
	<head>
	<script language="JavaScript">
	<!--
	alert("<?php echo $msg ?>");
	history.back();
	//-->
	</script>
	</head>
	<body>
	<!-- this is for non-JS browsers (actually we should never reach that code, but hey, just in case...) -->
	<?php echo $msg; ?><br />
	<a href="<?php echo $HTTP_SERVER_VARS["HTTP_REFERER"]; ?>">go back</a>
	</body>
	</html>
	<?php
	exit;
}

function alert_confirm($msg) { // asks a question - if the user clicks Cancel then it brings them back one page
	?>
	<script language="JavaScript">
	<!--
	if (!confirm("<?php echo $msg ?>")) {
	history.back();
	}
	//-->
	</script>
	<?php
}

function redirect_js($url,$title="...") {
	?>
	<script language="JavaScript">
	<!--
	function redirect() {
	window.location = "<?php echo $url; ?>";
	}
	setTimeout("redirect();", 100);
	//-->
	</script>
	<p>Redirecting you : <b><?php echo $title; ?></b><br />
	<br />
	If nothing happens, click <a href="<?php echo $url; ?>">here</a>.</p>
	<?php
	exit();
}

// functions to count the page generation time (from phpBB2)
// ( or just any time between timer_start() and timer_stop() )

	function timer_start() {
		global $timestart;
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$timestart = $mtime;
		return true;
	}

	function timer_stop($display=0,$precision=3) { //if called like timer_stop(1), will echo $timetotal
		global $timestart,$timeend;
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$timeend = $mtime;
		$timetotal = $timeend-$timestart;
		if ($display)
			echo number_format($timetotal,$precision);
		return($timetotal);
	}


// pings Weblogs.com
function pingWeblogs($blog_ID="1") {
	// original function by Dries Buytaert for Drupal
	global $use_weblogsping, $blogname,$siteurl,$blogfilename;

	if ((!(($blogname=="my weblog") && ($siteurl=="http://yourdomain.com") && ($blogfilename=="b2.php"))) && (!preg_match("/localhost\//",$siteurl)) && ($use_weblogsping)) {

		$client = new xmlrpc_client("/RPC2", "rpc.weblogs.com", 80);
		$message = new xmlrpcmsg("weblogUpdates.ping", array(new xmlrpcval($blogname), new xmlrpcval($siteurl."/".$blogfilename)));
		$result = $client->send($message);
		if (!$result || $result->faultCode()) {
			return(false);
		}
		return(true);
	} else {
		return(false);
	}
}

// pings CaféLog.com
function pingCafelog($cafelogID,$title='',$p='') {
	global $use_cafelogping, $blogname, $siteurl, $blogfilename;
	if ((!(($blogname=="my weblog") && ($siteurl=="http://yourdomain.com") && ($blogfilename=="b2.php"))) && (!preg_match("/localhost\//",$siteurl)) && ($use_cafelogping) && ($cafelogID != '')) {

		$client = new xmlrpc_client("/xmlrpc.php", "cafelog.tidakada.com", 80);
		$message = new xmlrpcmsg("b2.ping", array(new xmlrpcval($cafelogID), new xmlrpcval($title), new xmlrpcval($p)));
		$result = $client->send($message);
		if (!$result || $result->faultCode()) {
			return(false);
		}
		return(true);
	} else {
		return(false);
	}
}



// updates the RSS feed !
function rss_update($blog_ID, $num_posts="", $file="./b2rss.xml") {

	global $use_rss;
	global $admin_email,$blogname,$siteurl,$blogfilename,$blogdescription,$posts_per_rss,$rss_language;
	global $tableposts,$postdata,$row;

	if ($rss_language == '') {
		$rss_language = 'en';
	}

	if ($use_rss) {

		$num_posts = ($num_posts=="") ? $posts_per_rss : 5;

		$date_now = gmdate("D, d M Y H:i:s")." GMT";

		# let's build the rss file
		$rss = '';

		$rss .= '<?xml version="1.0"?'.">\n";
		$rss .= "<!-- generator=\"b2/0.6pre3\" -->\n";
		$rss .= "<rss version=\"0.92\">\n";
		$rss .= "\t<channel>\n";
		$rss .= "\t\t<title>".convert_chars(strip_tags(get_bloginfo("name")),"unicode")."</title>\n";
		$rss .= "\t\t<link>".convert_chars(strip_tags(get_bloginfo("url")),"unicode")."</link>\n";
		$rss .= "\t\t<description>".convert_chars(strip_tags(get_bloginfo("description")),"unicode")."</description>\n";
		$rss .= "\t\t<lastBuildDate>$date_now</lastBuildDate>\n";
		$rss .= "\t\t<docs>http://backend.userland.com/rss092</docs>\n";
		$rss .= "\t\t<managingEditor>$admin_email</managingEditor>\n";
		$rss .= "\t\t<webMaster>$admin_email</webMaster>\n";
		$rss .= "\t\t<language>$rss_language</language>\n";
		
		$sql = "SELECT * FROM $tableposts ORDER BY post_date DESC LIMIT $num_posts";
		$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());

		while($row = mysql_fetch_object($result)) {

			$id = $row->ID;
			$postdata=get_postdata2($id);

			$rss .= "\t\t<item>\n";
			$rss .= "\t\t\t<title>".convert_chars(strip_tags(get_the_title()),"unicode")."</title>\n";

//		we could add some specific RSS here, but not yet. uncomment if you wish, it's functionnal
//			$rss .= "\t\t\t<category>".convert_chars(strip_tags(get_the_category()),"unicode")."</category>\n";

			$content = stripslashes($row->post_content);
			$content = explode("<!--more-->",$content);
			$content = $content[0];
			$rss .= "\t\t\t<description>".convert_chars(strip_tags($content),"unicode")."</description>\n";

			$rss .= "\t\t\t<link>".htmlentities("$siteurl/$blogfilename?p=".$row->ID."&c=1")."</link>\n";
			$rss .= "\t\t</item>\n";

		}

		$rss .= "\t</channel>\n";
		$rss .= "</rss>";

		$f=@fopen("$file","w+");
		if ($f) {
			@fwrite($f,$rss);
			@fclose($f);

			return(true);
		} else {
			return(false);
		}
	} else {
		return(false);
	}
}


function xmlrpc_getposttitle($content) {
	global $post_default_title;
	if (preg_match("/<title>(.+?)<\/title>/is",$content,$matchtitle)) {
		$post_title = $matchtitle[0];
		$post_title = eregi_replace("<title>","",$post_title);
		$post_title = eregi_replace("</title>","",$post_title);
	} else {
		$post_title = $post_default_title;
	}
	return($post_title);
}
	
function xmlrpc_getpostcategory($content) {
	global $post_default_category;
	if (preg_match("/<category>(.+?)<\/category>/is",$content,$matchcat)) {
		$post_category = $matchcat[0];
		$post_category = eregi_replace("<category>","",$post_category);
		$post_category = eregi_replace("</category>","",$post_category);

	} else {
		$post_category = $post_default_category;
	}
	return($post_category);
}

function xmlrpc_removepostdata($content) {
	$content = preg_replace("/<title>(.+?)<\/title>/","",$content);
	$content = preg_replace("/<category>(.+?)<\/category>/","",$content);
	$content = trim($content);
	return($content);
}


/*
 balanceTags
 
 Balances Tags of string using a modified stack.
 
 @param text      Text to be balanced
 @return          Returns balanced text
 @author          Leonard Lin (leonard@acm.org)
 @version         v1.1
 @date            November 4, 2001
 @license         GPL v2.0
 @notes           
 @changelog       
             1.2  ***TODO*** Make better - change loop condition to $text
             1.1  Fixed handling of append/stack pop order of end text
                  Added Cleaning Hooks
             1.0  First Version
*/

function balanceTags($text) {
  $tagstack = array();
  $stacksize = 0;
  $tagqueue = '';
  $newtext = '';

	# b2 bug fix for comments - in case you REALLY meant to type '< !--'
	$text = str_replace("< !--","<    !--",$text);

 	while (preg_match("/<(\/?\w*)\s*([^>]*)>/",$text,$regex)) {
    $newtext = $newtext . $tagqueue;
    
		$i = strpos($text,$regex[0]);
		$l = strlen($tagqueue) + strlen($regex[0]);

    // clear the shifter
    $tagqueue = '';
   
    // Pop or Push
		if ($regex[1][0] == "/") { // End Tag
      $tag = strtolower(substr($regex[1],1));
      
      // if too many closing tags
      if($stacksize <= 0) { 
        $tag = '';
        //or close to be safe $tag = '/' . $tag;
      }
      // if stacktop value = tag close value then pop
      else if ($tagstack[$stacksize - 1] == $tag) { // found closing tag
        $tag = '</' . $tag . '>'; // Close Tag
        // Pop
        array_pop ($tagstack);
        $stacksize--;
      } else { // closing tag not at top, search for it
        for ($j=$stacksize-1;$j>=0;$j--) {
          if ($tagstack[$j] == $tag) {
            // add tag to tagqueue
            for ($k=$stacksize-1;$k>=$j;$k--){
               $tagqueue .= '</' . array_pop ($tagstack) . '>';
               $stacksize--;
            }
            break;
          }
        }
          $tag = '';
      }
    }	else { // Begin Tag
      $tag = strtolower($regex[1]);

      // Tag Cleaning
      if(checkTag($tag)) {
        // Push if not img or br or hr
        if($tag != 'br' && $tag != 'img' && $tag != 'hr') {
          $stacksize = array_push ($tagstack, $tag);
        }

        // Attributes
        // $attributes = $regex[2];
        $attributes = cleanAttributes($regex[2]);
        if($attributes) {
          $attributes = " " . $attributes;
        }

        $tag = "<$tag" . $attributes . ">";
      } else {
        $tag = '';
      }
    }

 		$newtext .= substr($text,0,$i) . $tag;
		$text = substr($text,$i+$l);
  }  

  // Clear Tag Queue
  $newtext = $newtext . $tagqueue;
  
  // Add Remaining text
  $newtext .= $text;

  // Empty Stack
  while($x = array_pop($tagstack)) {
    $newtext = $newtext . '</' . $x . '>'; // Add remaining tags to close      
  }

	# b2 fix for the bug with HTML comments
	$newtext = str_replace("< !--","<!--",$newtext);
	$newtext = str_replace("<    !--","< !--",$newtext);

  return $newtext;
}



function checkTag($tag) {
  $ok = 1;

  // the using ifs are 25% faster than declaring an array and using in_array()
  if ($tag == 'applet' || $tag == 'base' || $tag == 'body' || $tag == 'embed' || $tag == 'frame' || $tag == 'frameset' || $tag == 'html' || $tag == 'iframe' || $tag == 'layer' || $tag == 'meta' || $tag == 'object' || $tag == 'script' || $tag == 'style') {
    $ok = 0;
  }

  return $ok;
}

function cleanAttributes($attributes) {

	return($attributes);

	if (1==2) {

  $name = 1; // if we're in a name or a value
  $quote = 0; // quote open or not
  $new ='';
  $attr = '';
  $i = 0;
  $l = 0;
 
  while($attributes) {
    if($name) {
      $found = preg_match('/([^\s=]+)\s*([=]*)/i', $attributes, $regex);
      
      $attr .= strtolower($regex[1]);
  		$i = strpos($attributes,$regex[0]);
      
      //$new .= "[name]$attr" . '[/name]';
      
      // DEBUG
      //echo "<fieldset><legend>name</legend>attributes: [$attributes]<br>regex[1]: [$regex[1]]</fieldset><br>";

	  	$l = strlen($regex[0]);

      // strip quotes in attribute names
      $attr = str_replace('"', '', $attr);
      
      if($attr == 'style' || $attr == 'type' || preg_match('/^on/', $attr)) { // allow src and hrefs
        $attr = ' ';
      } else {
        if($regex[2]) {
          $attr .= '=';
          $name = 0;
        } else {
          if(substr($attributes,$i+$l)) $attr .= ' ';
        }
      }
    } else { //var
      $found = preg_match('/("?)([^\s"]+)("?)/i', $attributes, $regex);
      $attr = $regex[0];
  		$i = strpos($attributes,$regex[0]);
	  	$l = strlen($regex[0]);

      //DEBUG
      //echo "<fieldset><legend>var</legend>found: $found<br>attributes: [$attributes]<br>regex[2]: [$regex[2]]<br>";
      //print_r($regex);
      //echo "</fieldset><br>";
      
      if($regex[1]) $quote ? !$quote : $quote;
      if($regex[3]) $quote ? !$quote : $quote;

      if(preg_match('/javascript:/', $attr)) {
        $attr = '""';
      }

      // sets to name if closed quote
      $quote ? $name = 0 : $name = 1;
    }

 		$new .= substr($attributes,0,$i) . $attr;
    $attr = '';
		$attributes = substr($attributes,$i+$l);
  }

  //allow badly formed attributes?  i don't think so
  //$new .= $attributes;

  // open quote
  $quote ? $new .= '"' : $new ;

  return $new;
	}
}

?>