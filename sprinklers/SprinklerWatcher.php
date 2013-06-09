<?php 
// Written by David B. Gustavson, dbg@SCIzzL.com , starting October 2012.
$os_ip = '192.168.1.102';

date_default_timezone_set('America/Chicago');
$datetime=Date("Y-m-d H:i:s",time());
$newSprinklerValveSettings=file_get_contents('http://'.$os_ip.'/sn0');
$oldSprinklerValveSettings=file_get_contents("/var/sprinklers/SprinklerPrevious.txt");
if ($newSprinklerValveSettings!=$oldSprinklerValveSettings) {
file_put_contents ("/var/sprinklers/SprinklerChanges.txt", $newSprinklerValveSettings."--".$datetime."\n",FILE_APPEND);
file_put_contents ("/var/sprinklers/SprinklerPrevious.txt", $newSprinklerValveSettings);
};
?>