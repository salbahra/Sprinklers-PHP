<?php 
// Written by David B. Gustavson, dbg@SCIzzL.com , starting October 2012.

if(isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) {header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);exit();}

if (!file_exists("main.php")) return;

#Tell main we are calling it
define('Sprinklers', TRUE);

#Source required files
require_once "main.php";

#Update log files
$datetime=Date("Y-m-d H:i:s",time());
$settings = get_settings();
$rainSenseStatus = $settings["rs"];
$newSprinklerValveSettings=implode("",get_station_status());
$oldSprinklerValveSettings=explode("--",file_get_contents($log_previous));
if ($newSprinklerValveSettings!=$oldSprinklerValveSettings[0] || $rainSenseStatus!=$oldSprinklerValveSettings[1]) {
	file_put_contents ($log_file, $newSprinklerValveSettings."--".$datetime."--".$rainSenseStatus."\n",FILE_APPEND);
	file_put_contents ($log_previous, $newSprinklerValveSettings."--".$rainSenseStatus);
};

#Automatically turn off manual mode daily, if enabled
if ($auto_mm && date('H') == "00" && date('i') == "00") send_to_os("/cv?pw=&mm=0");;

#Automatic rain delay, every hour, if enabled
if ($auto_delay && date('i') == "00") weather_to_delay();
?>