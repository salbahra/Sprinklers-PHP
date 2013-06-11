<?php
ini_set('default_socket_timeout', 5);

if (file_exists("config.php")) header("Location: index.php");

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "new_config" && !file_exists("config.php")) {
    new_config();
    exit();
}

#New config setup
function new_config() {
    $config = "<?php\n";
    $needed = array("webtitle","os_ip","os_pw","timezone","timeViewWindow","pass_file","cache_file","log_file","log_previous");
    foreach ($needed as $key) {
        if (!isset($_REQUEST[$key])) fail();
        $data = $_REQUEST[$key];
        if ($key == "os_ip" && !isValidUrl("http://".$data)) {
            echo 2; exit();
        }
        if ($key == "pass_file") {
            if (!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) fail();
            $file = fopen($data, 'w');
            if (!$file) {
                fail();
            } else {
                $r = fwrite($file,$_REQUEST["username"].":".base64_encode(sha1($_REQUEST["password"])));
                if (!$r) fail();
                fclose($file);
            }
        }
        if ($key == "cache_file" || $key == "log_file" || $key == "log_previous") make_file($data);
        if ($key == "timezone") {
            $config .= "date_default_timezone_set('".$data."');\n";
        } else {
            $config .= "$".$key." = '".$data."';\n";            
        }
    }
    $file = fopen("config.php", 'w');
    if (!$file) fail();
    $r = fwrite($file,$config."?>");
    if (!$r) fail();

    $output = shell_exec('crontab -l');
    file_put_contents('/tmp/crontab.txt', $output.'* * * * * php '.dirname(__FILE__).'/watcher.php >/dev/null 2>&1'.PHP_EOL);
    exec('crontab /tmp/crontab.txt');

    echo 1;
}

function isValidUrl($url) {
    $header = get_headers($url, 1);
    $pos = stripos($header[0], "200 OK");
    if ($pos === false) return false;
    return true;
}

function make_file($data) {
    $file = fopen($data, "w");
    if (!$file) fail();
    fclose($file);    
}

function fail() {
    echo 0;
    exit();
}

?>

<!DOCTYPE html>
<html>
	<head>
    	<title>New Install</title> 
        <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
        <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1" media="(device-height: 568px)" />
        <meta content="yes" name="apple-mobile-web-app-capable">
        <meta name="apple-mobile-web-app-title" content="Sprinklers">
        <link rel="apple-touch-icon" href="img/icon.png">
    	<link rel="stylesheet" href="css/jquery.mobile-1.3.0.min.css" />
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <script src="js/jquery.mobile-1.3.0.min.js"></script>
        <script>
            function showerror(msg) {
                // show error message
                $.mobile.loading( 'show', {
                    text: msg,
                    textVisible: true,
                    textonly: true,
                    theme: 'a'
                });
            }
            function submit_config() {
                $.mobile.showPageLoadingMsg()
                $.get("install.php","action=new_config&"+$("#options").find(":input").serialize(),function(data){
                    if (data == 1) {
                        $.mobile.hidePageLoadingMsg()
                        showerror("Settings have been saved. Please wait while your redirected to the login screen!")
                        setTimeout(function(){location.reload()},2500);
                    } else {
                        $.mobile.hidePageLoadingMsg()
                        if (data == 2) {
                            showerror("Settings have NOT been saved. Check IP and Port settings and try again.")
                        } else {
                            showerror("Settings have NOT been saved. Check folder permissions and file paths then try again.")
                        }
                        setTimeout(function(){$.mobile.loading('hide')}, 2500);                    
                    }
                })
            }
        </script>
    </head> 
    <body>
        <div data-role="page" id="install" data-close-btn="none">
        	<div data-role="header" data-position="fixed">
                <h1>New Install</h1>
                <a href="javascript:submit_config()" class="ui-btn-right">Submit</a>
           </div>
        	<div data-role="content">
                <form action="javascript:submit_config()" method="post" id="options">
                    <ul data-inset="true" data-role="listview">
                        <li data-role="list-divider">Add New User</li>
                        <li>
                            <div data-role="fieldcontain">
                                <label for="username">Username:</label>
                                <input type="text" name="username" id="username" value="" />
                                <label for="password">Password:</label>
                                <input type="password" name="password" id="password" value="" />
                            </div>
                        </li>
                    </ul>
                    <ul data-inset="true" data-role="listview">
                        <li data-role="list-divider">Intial Configuration</li>
                        <li>
                            <div data-role="fieldcontain">
                                <label for="webtitle">Site Title:</label>
                                <input type="text" name="webtitle" id="webtitle" value="Sprinkler System" />
                                <label for="os_ip">Open Sprinkler IP:</label>
                                <input type="text" name="os_ip" id="os_ip" value="192.168.1.102" />
                                <label for="os_pw">Open Sprinkler Password:</label>
                                <input type="password" name="os_pw" id="os_pw" value="" />
                                <label for="timezone">Timezone:</label>
                                <input type="text" name="timezone" id="timezone" value="US/Central" />
                                <label for="pass_file">Pass File Location:</label>
                                <input type="text" name="pass_file" id="pass_file" value="/var/www/sprinklers/.htpasswd" />
                                <label for="cache_file">Cache File Location:</label>
                                <input type="text" name="cache_file" id="cache_file" value="/var/www/sprinklers/.cache" />
                            </div>
                        </li>
                    </ul>
                    <ul data-inset="true" data-role="listview">
                        <li data-role="list-divider">Log Configuration</li>
                        <li>
                            <div data-role="fieldcontain">
                                <label for="timeViewWindow">How Far Back to Log:</label>
                                <input type="text" name="timeViewWindow" id="timeViewWindow" value="7 days" />
                                <label for="log_file">Sprinkler Log File:</label>
                                <input type="text" name="log_file" id="log_file" value="/var/www/sprinklers/SprinklerChanges.txt" />
                                <label for="log_previous">Sprinkler Previous Status File:</label>
                                <input type="text" name="log_previous" id="log_previous" value="/var/www/sprinklers/SprinklerPrevious.txt" />
                            </div>
                        </li>
                    </ul>
                    <input type="submit" value="Submit" />
                </form>
            </div>
        </div>
    </body>
</html>
