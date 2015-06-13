[Sprinklers-PHP](http://salbahra.github.io/OpenSprinkler-Controller)
========================

**Warning**: This app is no longer being developed. Please use the new Javascript based app, found here: https://github.com/OpenSprinkler/OpenSprinkler-App

---

A mobile interface for the OpenSprinkler irrigation device. Designed to allow manual control, program management (view, edit, delete and add), initiation of a run-once program, viewing device status, adjusting rain delay, viewing logs, and changing of OpenSprinkler settings. Screenshots available below.

Overview:
---------

+ There is an authentication system in place and a guide on first run will assist in adding a new user along with any other required settings.

+ The application is written in PHP, Javascript, and HTML/CSS. This means a web server is required. Any web server supporting PHP should be supported. The default install instructions use Apache (default for most installs).

Install Instructions:
---------------------

```sh
#create directory with write permissions
mkdir -m 777 /var/www/sprinklers

#install files
git clone https://github.com/salbahra/OpenSprinkler-Controller.git /var/www/sprinklers

```
> If you don't have Git, you can download the [ZIP](https://github.com/salbahra/Sprinklers-PHP/archive/master.zip) file and extract to a local directory.

+ Now, visit the site using any browser (replacing IPAddr with the server IP): http://IPAddr/sprinklers
+ An installer will guide you through the rest of setup

> See the [Wiki - Install Instructions](https://github.com/salbahra/Sprinklers-PHP/wiki/Install-Instructions) for additional documentation.

Update Instructions:
--------------------

```sh
#change to the install directory
cd /var/www/sprinklers

#perform update
git pull
```
