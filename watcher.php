<?php 
// Written by David B. Gustavson, dbg@SCIzzL.com , starting October 2012.

if(isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) {header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);exit();}

if (!file_exists("config.php")) return;

#Include configuration
require_once("config.php");

date_default_timezone_set('UTC');

$datetime=Date("Y-m-d H:i:s",time());
preg_match("/,rs=(\d)/", file_get_contents("http://".$os_ip), $rainSenseStatus);
$rainSenseStatus = $rainSenseStatus[1];
preg_match("/\d+/", file_get_contents("http://".$os_ip."/sn0"), $newSprinklerValveSettings);
$newSprinklerValveSettings=$newSprinklerValveSettings[0];
$oldSprinklerValveSettings=explode("--",file_get_contents($log_previous));
if ($newSprinklerValveSettings!=$oldSprinklerValveSettings[0] || $rainSenseStatus!=$oldSprinklerValveSettings[1]) {
	file_put_contents ($log_file, $newSprinklerValveSettings."--".$datetime."--".$rainSenseStatus."\n",FILE_APPEND);
	file_put_contents ($log_previous, $newSprinklerValveSettings."--".$rainSenseStatus);
};
?>