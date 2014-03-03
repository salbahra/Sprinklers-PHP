<?php
#Start session
session_start();

#Tell main we are calling it
define('Sprinklers', TRUE);

#Source required files
require_once "main.php";

#Check if authenticated
is_auth();
?>

<!DOCTYPE html>
<html>
	<head>
    	<title><?php echo _("Sprinkler System"); ?></title>
    	<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
        <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1" media="(device-height: 568px)" />
    	<meta content="yes" name="apple-mobile-web-app-capable">
        <meta name="apple-mobile-web-app-title" content="Sprinklers">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    	<link rel="apple-touch-icon" href="img/icon.png">
        <?php
            if ($local_assets) {
                echo '<link rel="stylesheet" type="text/css" href="css/jquery.mobile.min.css" id="theme" />';
            } else {
                echo '<link rel="stylesheet" type="text/css" href="//code.jquery.com/mobile/1.4.0/jquery.mobile-1.4.0.min.css" id="theme" />';
            }
        ?>
        <link rel="stylesheet" href="css/main.css" />
        <link rel="shortcut icon" href="img/favicon.ico">
    </head>
    <body style="display:none">
        <div data-role="page" id="start"></div>

        <div data-role="popup" id="login" data-theme="a" data-dismissible="false">
            <div data-role="header" data-theme="b">
                <h1><?php echo _("Welcome"); ?></h1>
           </div>
            <div class="ui-content">
                <form action="javascript:dologin()" method="post">
                    <fieldset>
                        <label for="username" class="ui-hidden-accessible"><?php echo _("Username:"); ?></label>
                        <input autocapitalize="off" autocorrect="off" type="text" name="username" id="username" value="" placeholder="<?php echo _("username"); ?>" />
                        <label for="password" class="ui-hidden-accessible"><?php echo _("Password:"); ?></label>
                        <input type="password" name="password" id="password" value="" placeholder="<?php echo _("password"); ?>" />
                        <label><input type="checkbox" id="remember" name="remember" /><?php echo _("Remember Me"); ?></label>
                        <button type="submit" class="ui-btn ui-btn-b"><?php echo _("Sign in"); ?></button>
                    </fieldset>
                </form>
            </div>
        </div>

		<?php
            if ($local_assets) {
                echo '<script src="js/jquery.min.js"></script>';
                echo '<script>'; include_once("js/auth.js.php"); echo '</script>';
                echo '<script src="js/jquery.mobile.min.js"></script>';
                echo '<script src="js/jquery.flot.min.js"></script>';
            } else {
                echo '<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>';
                echo '<script>'; include_once("js/auth.js.php"); echo '</script>';
                echo '<script src="//code.jquery.com/mobile/1.4.0/jquery.mobile-1.4.0.min.js"></script>';
                echo '<script src="//cdnjs.cloudflare.com/ajax/libs/flot/0.8.1/jquery.flot.min.js"></script>';
            }
        ?>
        <script async src="js/async.js"></script>
    </body>
</html>
