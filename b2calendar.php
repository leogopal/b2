<?php

include_once('b2config.php');
include_once("$b2inc/b2functions.php");
include_once("$b2inc/b2vars.php");
dbconnect();


/* customize these as you wish */

$calendartablestart = '<table class="b2calendartable">';
$calendartableend = '</table>';

$calendarrowstart = '<tr class="b2calendarrow">';
$calendarrowend = '</tr>';

$calendarheadercellstart = '<td class="b2calendarheadercell">';
$calendarheadercellend = '</td>';

$calendarcellstart = '<td class="b2calendarcell">';
$calendarcellend = '</td>';

$calendaremptycellstart = '<td class="b2calendaremptycell">';
$calendaremptycellend = '</td>';

$calendaremptycellcontent = '&nbsp;';


/* stop customizing (unless you really know what you're doing) */



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

$calendarblah = get_weekstartend($datestartofmonth, $start_of_week);
if (mysql2date('w', $datestartofmonth) == $start_of_week) {
	$calendarfirst = $calendarblah['start']+1;
} else {
	$calendarfirst = $calendarblah['end']-600000;
}

$calendarblah = get_weekstartend($dateendofmonth, $end_of_week);
if (mysql2date('w', $dateendofmonth) == $end_of_week) {
	$calendarlast = $calendarblah['start']+1;
} else {
	$calendarlast = $calendarblah['end']+10000;
}

$beforethismonth = zeroise(intval($thismonth)-1,2);
$afterthismonth = zeroise(intval($thismonth)-1,2);



// displays everything

echo '<span class="b2calendarmonth">'.$month[$thismonth].' '.$thisyear.'</span>'."\n";

echo $calendartablestart."\n";
echo $calendarrowstart."\n";

for ($i = $start_of_week; $i<($start_of_week+7); $i = $i + 1) {
	echo $calendarheadercellstart;
	echo ucwords(substr($weekday[($i % 7)],0,3));
	echo $calendarheadercellend;
}

echo $calendarrowend."\n";
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
		if (($i+86400) < $calendarlast) {
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
			echo '<a href="'.$siteurl.'/'.$blogfilename.'?m='.$thisyear.$thismonth.date('d',$i).'" class="b2calendarlinkpost">';
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