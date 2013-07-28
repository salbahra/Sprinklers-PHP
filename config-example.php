<?php

#Set IP of OpenSprinkler
$os_ip = "192.168.1.30";

#Set OpenSprinkler password
$os_pw = "opendoor";

#Force SSL
$force_ssl = 0;

#Password File
$pass_file="/var/www/sprinklers/.htpasswd";

#Cache File
$cache_file = "/var/www/sprinklers/.cache";

#Sprinkler Log File
$log_file = "/var/www/sprinklers/SprinklerChanges.txt";

#Enable/Disable Automatic Rain Delay Based on Weather
$auto_delay = 0;

#Rain Delay Default for Adverse Weather
$auto_delay_duration=24;

#Automatically turn off manual mode at midnight
$auto_mm=0
?>
