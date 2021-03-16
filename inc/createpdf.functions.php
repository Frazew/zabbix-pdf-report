<?php
// FUNCTIONS

function fromto($stime,$period) {
  global $debug;
  // Convert $stime to ISO date for Zabbix 4.0+
  // Sample $stime : 20181019212650
  $from = date("Y-m-d+H:i:s", strtotime($stime));
  $start = strtotime($stime);
  $add = "+$period seconds";
  $end = strtotime($add,$start);
  $to   = date("Y-m-d+H:i:s", $end);
/*
  if ( $debug ) {
    echo "<br>From set to: $from, Add set to: $add, Start: $start, End: $end, To set to: $to<br/>";
  }
*/
  return array('from' => $from, 'to' => $to);
}

function tempdir($dir=false,$prefix='zabbix_report_') {
	$tempfile=tempnam($dir,$prefix);
	if (file_exists($tempfile)) { unlink($tempfile); }
	$old_umask = umask(0);
	mkdir($tempfile,0775);
	umask($old_umask);
	if (is_dir($tempfile)) { return $tempfile; }
}

function cleanup_name($name,$key="") {
	global $debug;

	if (( strpos($name, '$1') > 0 ) and (strlen($key)>0)) {
		$opval=preg_replace("#.*\[#","",$key); $opval=preg_replace("#,.*$#","",$opval); $opval=preg_replace("#\]$#","",$opval);
		$name = preg_replace('#\$1#',$opval,$name);
		if ($debug) { echo "Opval: $opval - New name: $name\n<p>"; }
	}
	if (( strpos($name, '$2') > 0 ) and (strlen($key)>0)) {
		$opval=preg_replace("#.*\[#","",$key); $opval=preg_replace("#^.*,#","",$opval); $opval=preg_replace("#\]$#","",$opval);
		$name = preg_replace('#\$2#',$opval,$name);
		if ($debug) { echo "Opval2: $opval - New name: $name\n<p>"; }
	}
	if ( strpos($name, "{") > 0 ) {
		$name=preg_replace("#{.*$#","",$name);
		if ($debug) { echo "Modified name: $name<BR/>\n"; }
	}
	return $name;
}

// sorts multiarray by a subarray value while preserving all keys, also preserves original order when the sorting values match
function maSort($ma = '', $sortkey = '', $sortorder = 1) { // sortorder: 1=asc, 2=desc
  if ($ma && is_array($ma) && $sortkey) { // confirm inputs
    foreach ($ma as $k=>$a) $temp["$a[$sortkey]"][$k] = $a; // temp ma with sort value, quotes convert key to string in case numeric float
    if ($sortorder == 2) { // descending
      krsort($temp);
    } else { // ascending
      ksort($temp);
    }
    $newma = array(); // blank output multiarray to add to
    foreach ($temp as $sma) $newma += $sma; // add sorted arrays to output array
    unset($ma, $sma, $temp); // release memory
    return $newma;
  }
}

function z_sum($arr,$col,$debugme=false) {
	$max = count($arr);
	$c = 0;
	$sum = 0;
	if ($debugme) { echo "Max: $max<p>\n"; }
	while ( $c < $max ) {
		if ($debugme) { echo "sum : $sum - "; }
		$sum += $arr[$c][$col];
		$c += 1;
	}
	return $sum;
}

function GetGraphImageById ($graphs, $stime, $period = 3600, $width, $height, $filename) {
	global $z_server, $z_user, $z_pass, $z_tmp_cookies, $z_url_index, $z_url_graph, $z_url_api, $z_login_data;
	// file names
	$filename_cookie = tempnam($z_tmp_cookies,"zabbix_cookie_");
	//setup curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $z_url_index);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $z_login_data);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $filename_cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $filename_cookie);
	// login
	$output=curl_exec($ch);
	// get graph
	// TODO: foreach ($graphs as $graphid) { $filename....
        $graphtime=fromto($stime,$period);
		$graphid = $graphs;
		//$image_file = $z_tmpimg_path ."/".$trimmed_hostname ."_" .$graphid .".png";
		curl_setopt($ch, CURLOPT_URL, $z_url_graph ."?graphid=" . $graphid ."&profileIdx=web.graphs.filter&width=" . $width . "&height=" . $height ."&period=" . $period ."&stime=" .$stime . "&from=" . $graphtime['from'] . "&to=" . $graphtime['to'] . "&isNow=0");
		$output = curl_exec($ch);
		curl_close($ch);
		// delete cookie
		unlink($filename_cookie);
		$fp = fopen($filename, 'w');
		fwrite($fp, $output);
		fclose($fp);
	//}
}

function GetItemImageById ($graphs, $stime, $period = 3600, $width, $height, $filename) {
	global $z_server, $z_user, $z_pass, $z_tmp_cookies, $z_url_index, $z_item_graph, $z_url_api, $z_login_data;
	// file names
	$filename_cookie = tempnam($z_tmp_cookies,"zabbix_cookie_");
	//setup curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $z_url_index);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $z_login_data);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $filename_cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $filename_cookie);
	// login
	$output=curl_exec($ch);
	// get graph
	// TODO: foreach ($graphs as $graphid) { $filename....
        $graphtime=fromto($stime,$period);
		$graphid = $graphs;
		//$image_file = $z_tmpimg_path ."/".$trimmed_hostname ."_" .$graphid .".png";
		curl_setopt($ch, CURLOPT_URL, $z_item_graph ."?itemids[$graphid]=" .$graphid ."&profileIdx=web.graphs.filter&width=" .$width ."&height=" .$height ."&period=" .$period ."&stime=" .$stime . "&from=" . $graphtime['from'] . "&to=" . $graphtime['to'] . "&isNow=0");
		$output = curl_exec($ch);
		curl_close($ch);
		// delete cookie
		unlink($filename_cookie);
		$fp = fopen($filename, 'w');
		fwrite($fp, $output);
		fclose($fp);
	//}
}

function secondsToTime($seconds) {
    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
}

function percent($value) {
  return floor($value*100) . " %";
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
    // $bytes /= pow(1024, $pow);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function formatBits($bits, $precision = 2) {
    $units = array('bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps');

    $bits = max($bits, 0);
    $pow = floor(($bits ? log($bits) : 0) / log(1000));
    $pow = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
    $bits /= pow(1000, $pow);
    //$bits /= (1 << (10 * $pow));

    return round($bits, $precision) . ' ' . $units[$pow];
}

function updown($value) {
	switch ($value) {
		case 0: $ret='DOWN'; break;
		case 1: $ret='UP'; break;
		case 2: $ret='DOWN'; break;
		default: $ret='Unknown (' + $value + ')'; break;
	}
	return $ret;
}

function CreatePDF($hostarray) {
	global $stime, $timeperiod, $tmp_pdf_data, $z_tmpimg_path, $debug, $showdates;
	global $starttime, $endtime, $TriggersOn, $GraphsOn, $ItemGraphsOn, $ItemsOn, $items, $TrendsOn, $trends, $mygraphs, $myevents, $myitemgraphs;

	if ($debug) { echo "Time scope - starttime: $starttime, endtime: $endtime - stime: $stime<BR/><p>\n"; }

	foreach($hostarray as $key=>$host) {
		$hostid   = $hostarray[$key]['hostid'];
		$hostname = $hostarray[$key]['name'];
		$trimmed_hostname = rawurlencode($hostname);

		if ($debug) { echo "<b>$hostname(id:$hostid)</b></br>\n"; }
		$fh = fopen($tmp_pdf_data, 'a') or die("Can't open $tmp_pdf_data for writing!");

	// MMO: Insert trigger / alert / event / history just about here.

	// Latest status of specific items
	//	$ItemsOn = "yes";
	//	$TrendsOn = "yes";
		if ($ItemsOn == "yes" ) {
			$stringData = "1<System Status for ".$hostname.">\n\n";
			fwrite($fh,$stringData);
			$stringData="#C\n"; // Use CODE font
			fwrite($fh, $stringData);
			foreach($items as $item=>$type) {
				$sys = ZabbixAPI::fetch_array('item','get',array('output'=>array('itemid','name','key_','description','lastclock','lastvalue','units'), 'hostids'=>$hostid, 'search'=>array('name'=>"$item"), 'sortfield'=>'name'));
				$sys = maSort($sys,'key_');
				if (!empty($sys[0])) {
					if ($debug) { echo "<B>Search for: $item of type $type</B><br/>\n<p><pre>"; print_r($sys); echo "</pre></p>"; }
					if ($debug) { flush(); ob_flush(); flush(); }
					foreach($sys as $rec=>$val) {
						if ($debug) { echo "Item object: <BR/>\n<pre>"; print_r($val) ; echo "</pre>\n"; }
						$name=$val['name'];
						$key=$val['key_'];
						$value=$val['lastvalue'];
						$unit=$val['units'];
						$otype=$type;
						if ($type == 'bits') {
							$value=formatBits($val['lastvalue']);
							$type='string';
							//if ($debug) { echo "ifSpeed? We got here! - $value - " . $val['lastvalue'] . "\n<br/>"; }
						}
						if (($type == 'number') && ($unit == 'B')) {
							$value=formatBytes($value);
							$type='string';
						}
						$tstamp=$val['lastclock'];
						$id=$val['itemid'];
						switch ($type) {
							case 'bytes': $value=formatBytes($value); $type='string'; break;
							case 'seconds': $value=secondsToTime($value); break;
							case 'ms': if ($value < 8 ) { $value = round($value*1000,2) . " ms"; $tval = round($tval*1000,2) . " ms"; } else { $value = $value . " ms" ; $tval = $tval . " ms" ; } ; break;
							case 'number': $value = round($value,2) . " " . $unit ; break;
							case 'datetime': $value = date("Y-m-d H:i:s", $value); break;
							case 'updown': $value = updown($value); break;
							case 'percent': $value = percent(round($value,2)); break;
						}
						$name = cleanup_name($name,$key);
                                                if ($showdates) {
                                                        $dateval=date("Y-m-d H:i:s",$tstamp) . " - ";
                                                } else {
                                                        $dateval="";
                                                }

						$stringData=$dateval . "<b>" . $name . " :</b> " . $value . "\n";
						fwrite($fh, $stringData);
						$type=$otype;

					}
				}
			}
			$stringData="#c\n\n"; // Use normal font
			fwrite($fh, $stringData);
		}
	// Trends
		if ($TrendsOn == "yes" ) {
			$stringData = "1<Trends and metrics for ".$hostname.">\n\n";
			fwrite($fh,$stringData);
			$stringData="#C\n"; // Use CODE font
			fwrite($fh, $stringData);
			foreach($trends as $trend=>$type) {
				#$sys = ZabbixAPI::fetch_array('trend','get',array('output'=>array('itemid','name','key_','description','lastclock','lastvalue'), 'hostids'=>$hostid, 'search'=>array('key_'=>"uptime"), 'sortfield'=>'name'));
			$sys = ZabbixAPI::fetch_array('item','get',array('output'=>array('itemid','name','key_','description','lastclock','lastvalue','units'), 'hostids'=>$hostid, 'search'=>array('name'=>"$trend"), 'sortfield'=>'name'));
			$sys = maSort($sys,'key_');
				if (!empty($sys[0])) {
					if ($debug) { echo "<B>Trend item search for: $trend of type $type</B><br/>\n<p><pre>"; print_r($sys); echo "</pre></p>"; }
					if ($debug) { flush(); ob_flush(); flush(); }
					foreach($sys as $rec=>$val) {
						$name=$val['name'];
						$key=$val['key_'];
						$value=$val['lastvalue'];
						$unit=$val['units'];
						$otype=$type;
						$trend_obj = ZabbixAPI::fetch('trend','get',array('output'=>array('itemid','num','value_min','value_avg','value_max'), 'itemids'=>$val['itemid'], 'time_from'=>$starttime, 'time_till'=>$endtime));
//						if ($debug) { echo "<B>Trend data for $name:</B><BR>\n<pre>" ; print_r($trend_obj) ; echo "</pre></p>"; }
						$tval = z_sum($trend_obj,'value_avg',false)/count($trend_obj);
						if ($debug) { echo "<EM>Trend value for $name (avg): $tval</EM> (" . count($trend_obj) . " data points)<p>"; }
						if ($type == 'bits') {
							$value=formatBits($value);
							$type='string';
							//if ($debug) { echo "ifSpeed? We got here! - $value - " . $val['lastvalue'] . "\n<br/>"; }
						}
						if (($type == 'number') && ($unit == 'B')) {
							$value=formatBytes($value);
							$type='string';
						}
						$tstamp=$val['lastclock'];
						$id=$val['itemid'];
						switch ($type) {
							case 'seconds': $value=secondsToTime($value); $tval=secondsToTime(floor($tval)); break;
							case 'ms': if ($value < 8 ) { $value = round($value*1000,2) . " ms"; $tval = round($tval*1000,2) . " ms"; } else { $value = $value . " ms" ; $tval = $tval . " ms" ; } ; break;
							case 'number': $value = round($value,2) . " " . $unit; $tval = round($tval,2) . " " . $unit; break;
							case 'datetime': $value = date("Y-m-d H:m:s", $value); $tval=date("Y-m-d H:m:s", floor($tval)) ; break;
							case 'updown': $value = updown($value) ; $tval=percent(round($tval,2)); break;
							case 'percent': $value = percent(round($value,2)); $tval=percent(round($tval,2)) ; break;
						}
						$name = cleanup_name($name,$key);
                                                if ($showdates) {
                                                        $dateval=date("Y-m-d H:m:s",$tstamp) . " - ";
                                                } else {
                                                        $dateval="";
                                                }

						$stringData=$dateval . "<b>" . $name . " :</b> latest value: " . $value . "\n";
						fwrite($fh, $stringData);
						if (count($trend_obj) > 1) {
							$stringData=$dateval . "<b>" . $name . "</b> trend/SLA: " . $tval . " (" . count($trend_obj) . " data points)\n";
						} else {
							$stringData=$dateval . "<b>" . $name . "</b> trend/SLA: no trend data found\n";
						}
						fwrite($fh, $stringData);
						$type=$otype;

					}
				}
			}
			$stringData="#c\n\n"; // Use normal font
			fwrite($fh, $stringData);
		}

	// Events
		$has_event = false;
		if ($TriggersOn == "yes" ) {
			$events = ZabbixAPI::fetch_array('event','get',array('output'=>array('eventid','clock','acknowledged', 'name', 'value', 'object', 'objectid'),'select_acknowledges'=>'extend','selectHosts'=>'extend',
						'time_from'=>$starttime, 'time_till'=>$endtime, 'sortfield'=>'clock','hostids'=>$hostid));

			if ($debug) { echo "<pre>" ; print_r($alerts); echo "</pre><br/>\n"; }

				if (!empty($events[0])) {
					$events_dict = array();
					foreach($events as $eventkey=>$event) {
						if (preg_match($myevents, $event["name"])) {
							$events_dict[$event["name"]][] = $event["clock"];
							$has_event = true;
						}
					}

					if ($has_event) {
						$stringData = "1<Trigger data for ".$hostname.">\n\n";
						fwrite($fh,$stringData);
					}

					foreach($events_dict as $event_name => $event_times) {
						$stringData = "2<Trigger " . $event_name . ">\n\n";
						fwrite($fh, $stringData);
						$stringData = "Occurred " . sizeof($event_times) . " times.\n";
						fwrite($fh, $stringData);
						$stringData="#C\n"; // Use CODE font
	    				fwrite($fh, $stringData);
	    				$stringData = "<b>First time " . date("Y-m-d H:m:s",$event_times[0]) . ", last time " . date("Y-m-d H:m:s",$event_times[sizeof($event_times) - 1]) . "</b>\n";
						fwrite($fh, $stringData);
						$stringData="#c\n\n"; // Use normal font
						fwrite($fh, $stringData);
					}
				}
/*				fclose($fh); */
				if ($debug) { flush(); ob_flush(); flush(); }
												fwrite($fh,"\n");
	/*		$fh = fopen($tmp_pdf_data, 'a') or die("Can't open $tmp_pdf_data for writing!"); */
	/*		fclose($fh); */
			if ($debug) { flush(); ob_flush(); flush(); }
		}
	// MMO: Back to before
		if ( $GraphsOn == "yes" && !($TriggersOn && !$has_event) ) {
			$count = 0;
			if ( $TriggersOn == "yes"  or  $ItemsOn == "yes"  or $TrendsOn == "yes" ) {
				fwrite($fh,"#NP\n");
			}

			$stringData = "1<Graphs for ".$hostname.">\n\n";
			fwrite($fh, $stringData);
	/*		fclose($fh); */
			#$hostGraphs = ZabbixAPI::fetch_array('graph','get',array('output'=>'extend','hostids'=>$hostid))
			$hostGraphs = ZabbixAPI::fetch_array('graph','get',array('output'=>array('graphid','name'),'hostids'=>$hostid))
				or die('Unable to get graphs: '.print_r(ZabbixAPI::getLastError(),true));
			#var_dump($hostGraphs);
			asort($hostGraphs);

			if ($debug) { echo "<p><B>Graph selector :</B> $mygraphs<p>\n"; }
			if ($debug) { flush(); ob_flush(); flush(); }

			foreach($hostGraphs as $graphkey=>$graphs) {
				$graphid    = $hostGraphs[$graphkey]['graphid'];
				$graphname  = $hostGraphs[$graphkey]['name'];

				$graphname = cleanup_name($graphname);

				if (preg_match($mygraphs, $graphname)) {
					if (($debug) and ($mygraphs!="")) {
						echo "<B>$graphname (id:$graphid) matched the expression - including it.</B><BR/>\n";
						echo "<pre>" ; print_r($graphs); echo "</pre>\n";
					}
					$image_file = $z_tmpimg_path ."/".$trimmed_hostname ."_" .$graphid .".png";
//					if ($debug) { echo "$graphname(id:$graphid)</br>\n"; }
					$fh = fopen($tmp_pdf_data, 'a') or die("Can't open $tmp_pdf_data for writing!");
					$stringData = "2<$graphname>\n";
					fwrite($fh, $stringData);
					$stringData = "[" .$image_file ."]\n";
					fwrite($fh, $stringData);
					GetGraphImageById($graphid,$stime,$timeperiod,'750','150',$image_file);
					$count+=1;
		/*			fclose($fh); */
				} else {
					if (($debug) and ($mygraphs!="")) { echo "$graphname (id:$graphid) did not match the expression - skipping it.<BR/>\n"; }
				}
				if ( $count == 3 ) {
					fwrite($fh, "#NP\n");
					$count = 0;
				}
				if ($debug) { flush(); ob_flush(); flush(); }
			}
			if (strpos($stringData,'1<') === 0 ) {
				fwrite($fh, "No matching graphs found. Maybe tune the setting?\n");
			} else {
				fwrite($fh, "#NP\n");
			}
		}
		if ( $ItemGraphsOn == "yes" && !($TriggersOn && !$has_event) ) {
			$count = 0;
/*			if ( $TriggersOn == "yes" ) {
				fwrite($fh,"#NP\n");
			} */

			$stringData = "1<Item Graphs for ".$hostname.">\n\n";
			fwrite($fh, $stringData);
	/*		fclose($fh); */
			#$hostGraphs = ZabbixAPI::fetch_array('graph','get',array('output'=>'extend','hostids'=>$hostid))
			$hostGraphs = ZabbixAPI::fetch_array('item','get',array('output'=>array('itemid','name','key_'),'hostids'=>$hostid,'filter'=>array('value_type'=>array('0','3'))))
				or die('Unable to get graphs: '.print_r(ZabbixAPI::getLastError(),true));
			#var_dump($hostGraphs);
			asort($hostGraphs);

			if ($debug) { echo "<p><B>Items for graphing selector :</B> $myitemgraphs<p>\n"; }
			if ($debug) { flush(); ob_flush(); flush(); }

			foreach($hostGraphs as $graphkey=>$graphs) {
				$graphid    = $hostGraphs[$graphkey]['itemid'];
				$graphname  = $hostGraphs[$graphkey]['name'];
				$graphkey  = $hostGraphs[$graphkey]['key_'];

				$graphname = cleanup_name($graphname,$graphkey);

				if (preg_match($myitemgraphs, $graphname)) {
					if (($debug) and ($myitemgraphs!="")) {
						echo "<B>$graphname (id:$graphid) matched the expression - including it.</B><BR/>\n";
						echo "<pre>" ; print_r($graphs); echo "</pre>\n";
					}
					$image_file = $z_tmpimg_path ."/".$trimmed_hostname ."_" .$graphid .".png";
//					if ($debug) { echo "$graphname(id:$graphid)</br>\n"; }
					$fh = fopen($tmp_pdf_data, 'a') or die("Can't open $tmp_pdf_data for writing!");
					$stringData = "2<$graphname>\n";
					fwrite($fh, $stringData);
					$stringData = "[" .$image_file ."]\n";
					fwrite($fh, $stringData);
					GetItemImageById($graphid,$stime,$timeperiod,'750','150',$image_file);
		/*			fclose($fh); */
				} else {
					if (($debug) and ($myitemgraphs!="")) { echo "$graphname (id:$graphid) did not match the expression - skipping it.<BR/>\n"; }
				}
				if ( $count == 3 ) {
					fwrite($fh, "#NP\n");
					$count = 0;
				}
				if ($debug) { flush(); ob_flush(); flush(); }
			}
			if (strpos($stringData,'1<') === 0 ) {
				fwrite($fh, "No items found to graph. Maybe tune the setting?\n");
			} else {
				fwrite($fh, "#NP\n");
			}
		}
	if (strpos($stringData,'1<') === 0 ) {
		fwrite($fh, "#NP\n");
	}
	fclose($fh);
	}
}
?>
