<?php 
// Written by David B. Gustavson, dbg@SCIzzL.com , starting October 2012.

if (!file_exists("config.php")) return;

#Include configuration
require_once("config.php");

$datetime=Date("Y-m-d H:i:s",time());
$newSprinklerValveSettings=file_get_contents('http://'.$os_ip.'/sn0');
$oldSprinklerValveSettings=file_get_contents($log_previous);
if ($newSprinklerValveSettings!=$oldSprinklerValveSettings) {
	file_put_contents ($log_file, $newSprinklerValveSettings."--".$datetime."\n",FILE_APPEND);
	file_put_contents ($log_previous, $newSprinklerValveSettings);
};
?>