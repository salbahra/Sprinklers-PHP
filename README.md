[OpenSprinkler-Controller](http://salbahra.github.io/OpenSprinkler-Controller)
========================

A mobile frontend for the OpenSprinkler irrigation device. Designed to allow manual control, program management (view, edit, delete and add), view status, adjust rain delay, and view logs.

Instructions:
-------------

+ You first need a working OpenSprinkler setup

+ Install prerequisites as needed
  + ```apt-get install apache2 php5 libapache2-mod-php5``` 

+ Download the files
  + ```git clone https://github.com/salbahra/OpenSprinkler-Controller.git```
  + ```mv opensprinkler-controller/sprinklers /var/```
  + ```mv opensprinkler-controller/www/sprinklers /var/www/```

+ Fill in the OpenSprinkler local IP, password and other settings.
  + ```nano /var/www/sprinklers/config.php```

+ Add the poller to crontab every 1 minutes for logging:
  + ```* * * * *     /usr/bin/php /var/sprinklers/SprinklerWatcher.php >/dev/null 2>&1```

+ Add a user to the configuration. There is no user management system yet, so this is done manually
  + First generate a hased version of your password
  + ```/usr/bin/php -r "echo base64_encode(sha1('PASSWORD'));"```
  + Then add it to the htpasswd file
  + ```nano /var/sprinklers/htpasswd```
  + Example:
  + ```username:ZGM3MjRhZjE4ZmJkZDRlNTkxODlmNWZlNzY4YTVmODMxMTUyNzA1MA==```

+ From there you may attempt to access the front end.

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/87d3c8783710e88024be2bf608fe8195 "githalytics.com")](http://githalytics.com/salbahra/OpenSprinkler-Controller)
