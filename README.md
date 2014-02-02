[OpenSprinkler Controller](http://salbahra.github.io/OpenSprinkler-Controller)
========================

A mobile frontend for the OpenSprinkler irrigation device. Designed to allow manual control, program management (view, edit, delete and add), initiate a run-once program, view status, adjust rain delay, weather-based automatic rain delay, change OpenSprinkler settings and view logs. Screenshots available [here](http://albahra.com/journal/2013/06/opensprinkler-with-custom-web-app).

Overview:
---------

+ This application interfaces with the interval program on the OpenSprinkler which is the default software available. The application has been tested on firmware version 2.0.0 but should be compatible with 1.8.x and newer.

+ There is an authentication system in place and a guide on first run will assist in adding a new user along with any other required settings.

+ The provided interface does not rely on the javascript files hosted by Ray therefore will work on a locally hosted server even without an internet connection. However an internet connection (with a properly configured web server and port forwarding) will allow you to access the application from anywhere.

+ For current discussion about the project please refer to the [forum post](http://rayshobby.net/phpBB3/viewtopic.php?f=2&t=154). 

Video Tutorial:
---------------
[![Video Tutorial](https://img.youtube.com/vi/5pYHsMZSj6w/0.jpg)](https://www.youtube.com/watch?v=5pYHsMZSj6w)

Very well put together by Ray, thanks!

Screenshots:
------------

![Splash Screen](http://albahra.com/journal/wp-content/uploads/2013/07/startup-iphone5-retina-175x300.png) ![Home Screen](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.15.37-PM-169x300.png) ![Status Page](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.15.45-PM-169x300.png) ![Program Preview](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.19.52-PM-169x300.png) ![Log Viewer](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.50.54-PM-169x300.png) ![Program Editor](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.24.09-PM-169x300.png) ![Manual Program](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.24.18-PM-169x300.png) ![Rain Delay](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.24.44-PM-169x300.png) ![Run Once](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.24.54-PM-169x300.png) ![Settings](http://albahra.com/journal/wp-content/uploads/2014/02/iOS-Simulator-Screen-shot-Jan-26-2014-7.25.08-PM-169x300.png)


Raspberry Pi Users:
-------------------

+ The application should also operate on the OpenSprinkler for Raspberry Pi so long the Raspberry Pi has the interval program installed. More information is available on  [Ray's Blog Post](http://rayshobby.net/?p=6339).
  +  This is a seperate program that needs to be running on the Raspberry Pi.
  + Please rememeber this is a front end for a hardware device. In the case of the OpenSprinkler Pi, the hardware happens to be the Pi combined with the interval program software.

+ The application can be hosted on the Raspberry Pi itself removing any requirment for a server. You would need to install a web deamon and PHP on the Rasberry Pi. This can be done using the linux instructions posted below as the Raspberry Pi typically run Raspbian which is based on Debian.
  + In order to run both the interval program and the mobile web app on the same RPi you need to specify different ports for each application. The interval program defaults to port 8080 which is perfect since the typical HTTP port, 80, is used by Apache2. When defining the IP in the web app be sure to specify the port. Example: 127.0.0.1:8080

+ In order for the interval program to be 100% compatibile with the web app you must be using an interval program built on or after June 22, 2013.

Install Instructions:
---------------------

+ You first need a working OpenSprinkler setup that you can access via a browser
  + For further information please refer to the OpenSprinkler online user manual available on [Ray's Website](http://rayshobby.net/?page_id=192)

+ Install prerequisites as needed (example for Debian using Apache web server)
  + ```apt-get install apache2 php5 libapache2-mod-php5 git``` 

+ Create the directory you wish to place the files in (ex. /var/www/sprinklers for http://yourwebsite/sprinklers)
  + ```mkdir -m 777 /var/www/sprinklers```

+ Download the files to your web directory using git
  + ```git clone https://github.com/salbahra/OpenSprinkler-Controller.git /var/www/sprinklers```

+ From there you may attempt to access the front end which will guide you through the rest of the install process.

Update Instructions:
--------------------

+ Navigate to the web directory where the files are stored
  + ```cd /var/www/sprinklers```

+ Trigger a remote update using git
  + ```git pull```

PHP Safe Mode:
--------------

+ If PHP denies exec, crontab for watcher will need to be added manually. The installer will notify you of this. Example of how to do this:
  +  ```crontab -e```
  +  ```* * * * * cd /var/www/sprinklers; php /var/www/sprinklers/watcher.php >/dev/null 2>&1```

Synology Specific:
------------------

+ There is no crontab editor on Synology, you have to edit /etc/crontab file manually.

+ The cron line parameters (* * * * *) must be separated by tabs, example:
  + ```*  * * * * cd /volume1/web/sprinklers; php /volume1/web/sprinklers/watcher.php >/dev/null 2>&1```

+ Cron service has to be restarted manually after updating the crontab by running
  + ```/usr/syno/etc/rc.d/S04crond.sh stop; /usr/syno/etc/rc.d/S04crond.sh```

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/87d3c8783710e88024be2bf608fe8195 "githalytics.com")](http://githalytics.com/salbahra/OpenSprinkler-Controller)
