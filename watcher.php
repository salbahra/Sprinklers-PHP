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
$rainDelayStatus = $settings["rd"];
$newSprinklerValveSettings=implode("",get_station_status());
$oldSprinklerValveSettings=explode("--",readLastLine($log_file));
if ($newSprinklerValveSettings!=$oldSprinklerValveSettings[0] || (!isset($oldSprinklerValveSettings[2]) || $rainSenseStatus!=$oldSprinklerValveSettings[2]) || (!isset($oldSprinklerValveSettings[3]) || $rainDelayStatus!=$oldSprinklerValveSettings[3])) {
	file_put_contents ($log_file, $newSprinklerValveSettings."--".$datetime."--".$rainSenseStatus."--".$rainDelayStatus."\n",FILE_APPEND);
};

$tz = $settings["tz"] - 48;
$tz = (($tz>=0) ? "+" : "-").((abs($tz)/4)*60*60)+(((abs($tz)%4)*15/10).((abs($tz)%4)*15%10) * 60);

#Automatically turn off manual mode daily, if enabled
if ($auto_mm && date('H',time()+$tz) == "00" && date('i') == "00" && $settings["mm"] == 1 && intval($newSprinklerValveSettings) == 0) send_to_os("/cv?pw=&mm=0");

#Automatic rain delay, every hour, if enabled
if ($auto_delay && date('i') == "00") weather_to_delay();
?>
