[OpenSprinkler-Controller](http://salbahra.github.io/OpenSprinkler-Controller)
========================

A mobile frontend for the OpenSprinkler irrigation device. Designed to allow manual control, program management (view, edit, delete and add), initiate a run-once program, view status, adjust rain delay, change OpenSprinkler settings and view logs. Screenshots available [here](http://albahra.com/journal/2013/06/opensprinkler-with-custom-web-app).

Instructions:
-------------

+ You first need a working OpenSprinkler setup

+ Install prerequisites as needed (example for Debian using Apache web server)
  + ```apt-get install apache2 php5 libapache2-mod-php5``` 

+ Create the directory you wish to place the files in (ex. /var/www/sprinklers for http://yourwebsite/sprinklers)
  + ```mkdir -m 777 /var/www/sprinklers```

+ Download the files to your web directory using git (less steps but requires git to be installed)
  + ```git clone https://github.com/salbahra/OpenSprinkler-Controller.git /var/www/sprinklers```

+ OR download the zip file and extract the contents (more steps but also universal)
  + ```wget https://github.com/salbahra/OpenSprinkler-Controller/archive/master.zip```
  + ```unzip master.zip -d /var/www/sprinklers```
  + ```mv /var/www/sprinklers/OpenSprinkler-Controller-master/* /var/www/sprinklers/```

+ From there you may attempt to access the front end.

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/87d3c8783710e88024be2bf608fe8195 "githalytics.com")](http://githalytics.com/salbahra/OpenSprinkler-Controller)
