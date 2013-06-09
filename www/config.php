<?php

#Set timezone
date_default_timezone_set('US/Central');

#WebApp Title
$webtitle = "Sprinkler System";

#Set IP of OpenSprinkler
$os_ip = "192.168.1.102";

#Set OpenSprinkler password, leave blank for none
$os_pw = "";

#How far back to show logs ex. '24 hours' or '14 days'
$timeViewWindow='7 days';

#Password File
$pass_file='/var/sprinklers/.htpasswd';

#Cache File
$cache_file = "/var/sprinklers/.cache";

#Sprinkler Log File
$log_file = "/var/sprinklers/SprinklerChanges.txt";

#Set denied message
$denied = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\"><html><head><title>401 Authorization Required</title></head><body><h1>Authorization Required</h1><p>This server could not verify that you are authorized to access the document requested.  Either you supplied the wrong credentials (e.g., bad password), or your browser doesn't understand how to supply the credentials required.</p></body></html>";
?>