<?php

/* customize these as you wish */

$calendarmonthdisplay = 1;	// set this to 0 if you don't want to display the month name
$calendarmonthformat = 'F Y';
$calendarmonthstart = '<caption class="b2calendarmonth">';
$calendarmonthend = '</caption>';

$calendartablestart = '<table class="b2calendartable" summary="Monthly calendar with links to each day\'s posts">';
$calendartableend = '</table>';

$calendarrowstart = '<tr class="b2calendarrow">';
$calendarrowend = '</tr>';

$calendarheaderdisplay = 1;	// set this to 0 if you don't want to display the "Mon Tue Wed..." header
$calendarheadercellstart = '<th class="b2calendarheadercell" abbr="$abbr">';	// please leave $abbr there !
$calendarheadercellend = '</th>';
$calendarheaderabbrlenght = 3;	// lenght of the shortened weekday

$calendarcellstart = '<td class="b2calendarcell">';
$calendarcellend = '</td>';

$calendaremptycellstart = '<td class="b2calendaremptycell">';
$calendaremptycellend = '</td>';

$calendaremptycellcontent = '&nbsp;';


/* stop customizing (unless you really know what you're doing) */


include('b2config.php');
require_once("$b2inc/b2template.functions.php");
require_once("$b2inc/b2functions.php");
require_once("$b2inc/b2vars.php");
dbconnect();

if (isset($calendar) && ($calendar != '')) {
	$thisyear = substr($calendar,0,4);
	$thismonth = substr($calendar,4,2);
} else {
	if (isset($m) && ($m != '')) {
		$calendar = substr($m,0,6);
		$thisyear = substr($m,0,4);
		if (strlen($m) < 6) {
			$thismonth = '01';
		} else {
			$thismonth = substr($m,4,2);
		}
	} else {
		$thisyear = date('Y');
		$thismonth = date('m');
	}
}

// original arrow hack by Alex King
$archive_link_m = $siteurl.'/'.$blogfilename.$querystring_start.'m'.$querystring_equal;
$ak_date = mktime(0,0,0,$thismonth,1,$thisyear);
$ak_previous_month = date("m", $ak_date - ((date("t", $ak_date) * 86400) + 86400));
$ak_next_month = date("m", $ak_date + ((date("t", $ak_date) * 86400) + 86400));
$ak_first_post = mysql_query("SELECT MONTH(MIN(post_date)), YEAR(MIN(post_date)) FROM $tableposts");
$ak_first_post = mysql_fetch_array($ak_first_post);
// using text links by default
$ak_previous_month_dim = '<span>&lt;</span>&nbsp;&nbsp;';
$ak_previous_month_active = '<a href="'.$archive_link_m.$thisyear.$ak_previous_month.'" style="text-decoration: none;">&lt;</a>&nbsp;&nbsp;';
$ak_next_month_dim = '&nbsp;&nbsp;<span>&gt;</span>';
$ak_next_month_active = '&nbsp;&nbsp;<a href="'.$archive_link_m.$thisyear.$ak_next_month.'" style="text-decoration: none;">&gt;</a>';
$ak_previous_month_link = (mktime(0,0,0,$ak_previous_month,0,$thisyear) < mktime(0,0,0,$ak_first_post[0],0,$ak_first_post[1])) ? $ak_previous_month_dim : $ak_previous_month_active;
$ak_next_month_link = (mktime(0,0,0,$ak_next_month,0,$thisyear)> mktime()) ? $ak_next_month_dim : $ak_next_month_active;

$end_of_week = (($start_of_week + 7) % 7);

$calendarmonthwithpost = 0;
while($calendarmonthwithpost == 0) {
	$arc_sql="SELECT DISTINCT YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) FROM $tableposts WHERE MONTH(post_date) = '$thismonth' AND YEAR(post_date) = '$thisyear' ORDER BY post_date DESC";
	$querycount++;
	$arc_result=mysql_query($arc_sql) or die($arc_sql."<br />".mysql_error());
	if (mysql_num_rows($arc_result) > 0) {
		$daysinmonthwithposts = '-';
		while($arc_row = mysql_fetch_array($arc_result)) {
			$daysinmonthwithposts .= $arc_row["DAYOFMONTH(post_date)"].'-';
		}
		$calendarmonthwithpost = 1;
	} elseif ($calendar != '') {
		$daysinmonthwithposts = '';
		$calendarmonthwithpost = 1;
	} else {
		$thismonth = zeroise(intval($thismonth)-1,2);
		if ($thismonth == '00') {
			$thismonth = '12';
			$thisyear = ''.(intval($thisyear)-1);
		}
	}
}

$daysinmonth = intval(date('t', mktime(0,0,0,$thismonth,1,$thisyear)));
$datestartofmonth = $thisyear.'-'.$thismonth.'-01';
$dateendofmonth = $thisyear.'-'.$thismonth.'-'.$daysinmonth;

// caution: offset bug inside
$calendarblah = get_weekstartend($datestartofmonth, $start_of_week);
if (mysql2date('w', $datestartofmonth) == $start_of_week) {
	$calendarfirst = $calendarblah['start']+1+3600;	//	adjust for daylight savings time
} else {
	$calendarfirst = $calendarblah['end']-604799+3600;	//	adjust for daylight savings time
}

$calendarblah = get_weekstartend($dateendofmonth, $end_of_week);
if (mysql2date('w', $dateendofmonth) == $end_of_week) {
	$calendarlast = $calendarblah['start']+1;
} else {
	$calendarlast = $calendarblah['end']+10000;
}

$beforethismonth = zeroise(intval($thismonth)-1,2);
$afterthismonth = zeroise(intval($thismonth)-1,2);

// here the offset bug is corrected
if ((intval(date('d', $calendarfirst)) > 1) && (intval(date('m', $calendarfirst)) == intval($thismonth))) {
	$calendarfirst = $calendarfirst - 604800;
}


// displays everything

echo $calendartablestart."\n";

if ($calendarmonthdisplay) {
	echo $calendarmonthstart;
	echo $ak_previous_month_link;
	echo date_i18n($calendarmonthformat, mktime(0, 0, 0, $thismonth, 1, $thisyear));
	echo $ak_next_month_link;
	echo $calendarmonthend."\n";
}

if ($calendarheaderdisplay) {
	echo $calendarrowstart."\n";

	for ($i = $start_of_week; $i<($start_of_week+7); $i = $i + 1) {
		echo str_replace('$abbr', $weekday[($i % 7)], $calendarheadercellstart);
		echo ucwords(substr($weekday[($i % 7)], 0, $calendarheaderabbrlenght));
		echo $calendarheadercellend;
	}

	echo $calendarrowend."\n";
}

echo $calendarrowstart."\n";

$newrow = 0;
$j = 0;
$k = 1;
for($i = $calendarfirst; $i<($calendarlast+86400); $i = $i + 86400) {
	if ($newrow == 1) {
		if ($k > $daysinmonth) {
			break;
		}
		echo $calendarrowend."\n";
		if (($i+86400) < ($calendarlast+86400)) {
			echo $calendarrowstart."\n";
		}
		$newrow = 0;
	}
	if (date('m',$i) != $thismonth) {
		echo $calendaremptycellstart;
		echo $calendaremptycellcontent;
		echo $calendaremptycellend;
	} else {
		$k = $k + 1;
		echo $calendarcellstart;
		$calendarblah = '-'.date('j',$i).'-';
		$calendarthereisapost = ereg($calendarblah, $daysinmonthwithposts);
		$calendartoday = (date('Ymd',$i) == date('Ymd', (time() + ($time_difference * 3600))));

		if ($calendarthereisapost) {
			echo '<a href="'.$siteurl.'/'.$blogfilename.$querystring_start.'m'.$querystring_equal.$thisyear.$thismonth.date('d',$i).'" class="b2calendarlinkpost">';
		}
		if ($calendartoday) {
			echo '<span class="b2calendartoday">';
		}
		echo date('j',$i);
		if ($calendartoday) {
			echo '</span>';
		}
		if ($calendarthereisapost) {
			echo '</a>';
		}
		echo $calendarcellend."\n";
	}
	$j = $j + 1;
	if ($j == 7) {
		$j = 0;
		$newrow = 1;
	}
}

echo $calendarrowend."\n";
echo $calendartableend;

?>
