<?php
if(!defined('Sprinklers')) {
    #Start session
    if(!isset($_SESSION)) session_start();

    #Tell main we are calling it
    define('Sprinklers', TRUE);

    #Required files
    require_once "main.php";
}

#Redirect if not authenticated or grabbing page directly
if (!is_auth() || !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {header('Location: '.$base_url); exit();}

#Get controller settings
$_SESSION["data"] = start_data();

#Include the main javascript file
echo "<script>";
include_once("js/main.js.php");
echo "</script>";
?>

<div data-role="page" id="sprinklers">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <a data-icon="bars" data-iconpos="notext" href="#sprinklers-settings"></a>
        <a data-icon="gear" data-iconpos="notext" href="#settings"><?php echo _("Settings"); ?></a>
        <h3 style="padding:0"><div class="logo"></div></h3>
    </div>
    <div class="ui-content" role="main" style="padding-top:0px">
        <div id="footer-running">
        </div>
        <div id="showupdate" class="red">
            <p style="margin:0;text-align:center"><?php echo _("Update Available"); ?></p>
        </div>
        <ul data-role="listview" data-inset="true" id="weather-list">
            <li data-role="list-divider"><?php echo _("Weather"); ?></li>
            <li><div id="weather"></div></li>
        </ul>
        <ul data-role="listview" data-inset="true" id="info-list">
            <li data-role="list-divider"><?php echo _("Information"); ?></li>
            <li><a href="#status" data-onclick="get_status();"><?php echo _("Current Status"); ?></a></li>
            <li><a href="#preview"><?php echo _("Preview Programs"); ?></a></li>
            <li><a href="#logs"><?php echo _("View Logs"); ?></a></li>
        </ul>
        <ul data-role="listview" data-inset="true" id="program-control-list">
            <li data-role="list-divider"><?php echo _("Program Control"); ?></li>
            <li><a href="#raindelay" data-onclick="open_popup('#raindelay');"><?php echo _("Change Rain Delay"); ?></a></li>
            <li><a href="#programs" data-onclick="get_programs();"><?php echo _("Edit Programs"); ?></a></li>
            <li><a href="#manual" data-onclick="get_manual();"><?php echo _("Manual Control"); ?></a></li>
            <li><a href="#runonce" data-onclick="get_runonce();"><?php echo _("Run-Once Program"); ?></a></li>
            <li><a href="#" data-onclick="rsn();"><?php echo _("Stop All Stations"); ?></a></li>
        </ul>
    </div>
    <div data-role="popup" id="raindelay" data-overlay-theme="b">
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider"><?php echo _("Change Rain Delay"); ?></li>
            <li>
                <p class="rain-desc"><?php echo _("Enable manual rain delay by entering a value into the input below. To turn off a currently enabled rain delay use a value of 0."); ?></p>
                <form action="javascript:raindelay()">
                    <div class="ui-field-contain">
                        <label for="delay"><?php echo _("Duration (in hours):"); ?></label>
                        <input type="number" pattern="[0-9]*" data-highlight="true" data-type="range" value="0" min="0" max="96" id="delay" />
                    </div>
                    <input type="submit" value="<?php echo _("Submit"); ?>" data-theme="b" />
                </form>
            </li>
        </ul>
    </div>
    <div data-role="panel" id="sprinklers-settings" data-position-fixed="true" data-theme="b">
        <ul data-role="listview" data-theme="b">
            <li><?php echo _("Logged in as:"); ?> <?php echo $_SESSION["username"] ?></li>
            <li>
                <div class="ui-grid-a">
                    <div class="ui-block-a"><br>
                        <label for="autologin"><?php echo _("Auto Login"); ?></label>
                    </div>
                    <div class="ui-block-b">
                        <input type="checkbox" data-on-text="<?php echo _("On"); ?>" data-off-text="<?php echo _("Off"); ?>" data-role="flipswitch" name="autologin" id="s-autologin">
                    </div>
                </div>
            </li>
            <li data-icon="action"><a href="#" data-onclick="export_config();"><?php echo _("Export Configuration"); ?></a><a href="#" data-onclick="export_config(1);"></a></li>
            <li data-icon="cloud"><a href="#" data-onclick="import_config();"><?php echo _("Import Configuration"); ?></a></a><a href="#" data-onclick="getConfigFile()"></a></li>
            <li data-icon="delete"><a href="#" data-onclick="logout();"><?php echo _("Logout"); ?></a></li>
            <li data-icon="info"><a href="#" data-onclick="changeFromPanel(show_about);"><?php echo _("About"); ?></a></li>
        </ul>
        <input type="file" id="configInput" data-role="none" onchange="handleConfig(this.files)" style="visibility:hidden;position:absolute;top:-50;left:-50"/>
    </div>
</div>

<div data-role="page" id="forecast">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Forecast"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="get_forecast();" data-icon="refresh"><?php echo _("Refresh"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <ul data-role="listview" data-inset="true" id="forecast_list">
        </ul>
    </div>
</div>

<div data-role="page" id="status">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Current Status"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="get_status();" data-icon="refresh"><?php echo _("Refresh"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <p id="status_header"></p>
        <ul data-role="listview" data-inset="true" id="status_list">
        </ul>
        <p id="status_footer"></p>
    </div>
</div>

<div data-role="page" id="manual">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Manual Control"); ?></h3>
        <a href="#" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <p style="text-align:center"><?php echo _("With manual mode turned on, tap a station to toggle it."); ?></p>
        <ul data-role="listview" data-inset="true">
            <li class="ui-field-contain">
                <label for="mmm"><b><?php echo _("Manual Mode"); ?></b></label>
                <input type="checkbox" data-on-text="<?php echo _("On"); ?>" data-off-text="<?php echo _("Off"); ?>" data-role="flipswitch" name="mmm" id="mmm" <?php if ($_SESSION["data"]["mm"]) echo "checked"; ?>>
            </li>
        </ul>
        <ul data-role="listview" data-inset="true" id="mm_list">
        </ul>
    </div>
</div>

<div data-role="page" id="runonce">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Run-Once Program"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="submit_runonce();"><?php echo _("Submit"); ?></a>
    </div>
    <div class="ui-content" role="main" id="runonce_list">
    </div>
</div>

<div data-role="page" id="programs">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Programs"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#addprogram" data-onclick="add_program();" data-icon="plus"><?php echo _("Add"); ?></a>
    </div>
    <div class="ui-content" role="main" id="programs_list">
    </div>
</div>

<div data-role="page" id="addprogram">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Add Program"); ?></h3>
        <a href="#programs" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="submit_program('new');"><?php echo _("Submit"); ?></a>
    </div>
    <div class="ui-content" role="main" id="newprogram">
    </div>
</div>

<div data-role="page" id="logs">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Logs"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="get_logs();" data-icon="refresh"><?php echo _("Refresh"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <fieldset data-role="controlgroup" data-type="horizontal" data-mini="true" class="log_type">
            <input data-mini="true" type="radio" name="log_type" id="log_graph" value="graph" checked="checked" />
            <label for="log_graph"><?php echo _("Graph"); ?></label>
            <input data-mini="true" type="radio" name="log_type" id="log_table" value="table" />
            <label for="log_table"><?php echo _("Table"); ?></label>
        </fieldset>
        <div id="placeholder" style="display:none;width:100%;height:300px;"></div>
        <div id="zones">
        </div>
        <fieldset data-role="collapsible" data-mini="true" id="log_options">
            <legend><?php echo _("Options"); ?></legend>
            <fieldset data-role="controlgroup" data-type="horizontal" id="graph_sort" style="display:none;text-align:center">
              <p style="margin:0"><?php echo _("Grouping:"); ?></p>
              <input data-mini="true" type="radio" name="g" id="radio-choice-d" value="n" checked="checked" />
              <label for="radio-choice-d"><?php echo _("None"); ?></label>
              <input data-mini="true" type="radio" name="g" id="radio-choice-a" value="h" />
              <label for="radio-choice-a"><?php echo _("Hour"); ?></label>
              <input data-mini="true" type="radio" name="g" id="radio-choice-b" value="d" />
              <label for="radio-choice-b"><?php echo _("DOW"); ?></label>
              <input data-mini="true" type="radio" name="g" id="radio-choice-c" value="m" />
              <label for="radio-choice-c"><?php echo _("Month"); ?></label>
            </fieldset>
            <div class="ui-field-contain">
                <label for="log_start"><?php echo _("Start:"); ?></label>
                <input data-mini="true" type="date" id="log_start" />
                <label for="log_end"><?php echo _("End:"); ?></label>
                <input data-mini="true" type="date" id="log_end" />
            </div>
        </fieldset>
        <div id="logs_list">
        </div>
    </div>
</div>

<div data-role="page" id="settings">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Settings"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider" data-theme="b"><?php echo _("Device Settings"); ?></li>
            <li class="ui-field-contain">
                <label for="mm"><b><?php echo _("Manual Mode"); ?></b></label>
                <input type="checkbox" data-on-text="<?php echo _("On"); ?>" data-off-text="<?php echo _("Off"); ?>" data-role="flipswitch" name="mm" id="mm" <?php if ($_SESSION["data"]["mm"]) echo "checked"; ?>>
            </li>
            <li class="ui-field-contain">
                <label for="en"><b><?php echo _("Operation"); ?></b></label>
                <input type="checkbox" data-on-text="<?php echo _("On"); ?>" data-off-text="<?php echo _("Off"); ?>" data-role="flipswitch" name="en" id="en" <?php if ($_SESSION["data"]["en"]) echo "checked"; ?>>
            </li>
            <li><a href="#" data-onclick="show_settings();"><?php echo _("Device Options"); ?></a></li>
            <li><a href="#" data-onclick="show_stations();"><?php echo _("Edit Stations"); ?></a></li>
            <li data-icon="alert"><a href="#" data-onclick="rbt();"><?php echo _("Reboot OpenSprinkler"); ?></a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider" data-theme="b"><?php echo _("Mobile Application Settings"); ?></li>
            <li class="ui-field-contain">
                <label for="local_assets"><b><?php echo _("Local Assets"); ?></b></label>
                <input type="checkbox" data-on-text="<?php echo _("On"); ?>" data-off-text="<?php echo _("Off"); ?>" data-role="flipswitch" name="local_assets" id="local_assets" <?php global $local_assets; if ($local_assets) echo "checked"; ?>>
            </li>
            <li>
                <p class="rain-desc"><?php echo _("Assets are javascript and CSS libraries that power the mobile web app. Choose between local assets or content distibution network (CDN) hosted assets."); ?></p>
            </li>
            <li class="ui-field-contain">
                <label for="auto_mm"><b><?php echo _("Manual Auto-Off"); ?></b></label>
                <input type="checkbox" data-on-text="<?php echo _("On"); ?>" data-off-text="<?php echo _("Off"); ?>" data-role="flipswitch" name="auto_mm" id="auto_mm" <?php global $auto_mm; if ($auto_mm) echo "checked"; ?>>
            </li>
            <li>
                <p class="rain-desc"><?php echo _("Automatically disable manual mode at midnight. Use this option to turn off manual mode and ensure programs run even if you forget manual mode enabled."); ?></p>
            </li>
            <li data-icon="alert"><a href="#" data-onclick="clear_config();"><?php echo _("Clear Configuration"); ?></a></li>
            <li data-icon="alert"><a href="#" data-onclick="clear_logs();"><?php echo _("Clear Logs"); ?></a></li>
            <li><a href="#" data-onclick="show_localization();"><?php echo _("Localization"); ?></a></li>
            <li><a href="#" data-onclick="show_users();"><?php echo _("User Management"); ?></a></li>
            <li><a href="#" data-onclick="show_weather_settings();"><?php echo _("Weather Settings"); ?></a></li>
        </ul>
    </div>
    <div data-role="popup" data-overlay-theme="b" id="localization">
        <?php
            global $lang;
            $locals = get_available_languages();
            echo "<ul data-inset='true' data-role='listview' id='lang' data-language='".$lang."'><li data-role='list-divider' data-theme='b'>"._("Localization")."</li>";
            foreach ($locals as $l=>$local) {
                echo "<li data-icon='".(($lang == $l) ? "check" : "false")."'><a href='#' data-onclick='submit_localization(\"".$l."\");'>".$local."</a></li>";
            }
            echo "</ul>";
        ?>
    </div>
</div>

<div data-role="page" id="os-settings">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("OS Settings"); ?></h3>
        <a href="#settings" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="submit_settings();"><?php echo _("Submit"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <ul data-role="listview" data-inset="true" id="os-settings-list">
        </ul>
    </div>
</div>

<div data-role="page" id="weather-settings">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Weather Settings"); ?></h3>
        <a href="#settings" data-icon="back"><?php echo _("Back"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <ul data-role='listview' data-inset='true'>
            <li data-role='list-divider' data-theme='b'><?php echo _("Weather Provider"); ?></li>
            <li>
                <form action="javascript:submit_weather_settings()">
                    <label for='weather_provider'><?php echo _("Weather Provider"); ?>
                        <select data-mini='true' id='weather_provider'>
                            <option value='yahoo'><?php echo _("Yahoo!"); ?></option>
                            <option value='wunderground'><?php echo _("Wunderground"); ?></option>
                        </select>
                    </label>
                    <label for='wapikey'><?php echo _("Wunderground API Key"); ?><input data-mini='true' type='text' id='wapikey' /></label>
                    <input type="submit" value="<?php echo _("Submit"); ?>" />
                </form>
            </li>
        </ul>
        <ul data-role='listview' data-inset='true'>
            <li data-role='list-divider' data-theme='b'><?php echo _("Automatic Rain Delay"); ?></li>
            <li>
                <p class="rain-desc"><?php echo _("When automatic rain delay is enabled, the weather will be checked for rain every hour. If the weather reports any condition suggesting rain, a rain delay is automatically issued using the below set delay duration."); ?></p>
                <form action="javascript:auto_raindelay()">
                    <div data-role='fieldcontain'>
                        <label for='auto_delay'><?php echo _("Auto Rain Delay"); ?></label>
                        <input type="checkbox" data-on-text="<?php echo _("On"); ?>" data-off-text="<?php echo _("Off"); ?>" data-role="flipswitch" name="auto_delay" id="auto_delay">
                    </div>
                    <label for='auto_delay_duration'><?php echo _("Delay Duration (hours)"); ?></label>
                    <input type='number' pattern='[0-9]*' data-highlight='true' data-type='range' min='0' max='96' id='auto_delay_duration' />
                    <input type="submit" value="<?php echo _("Submit"); ?>" />
                </form>
            </li>
        </ul>
    </div>
</div>

<div data-role="page" id="user-control">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("User Management"); ?></h3>
        <a href="#settings" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="open_popup('#add-user');" data-icon="plus"><?php echo _("Add"); ?></a>
    </div>
    <div class="ui-content" role="main" id="user-control-list">
    </div>
    <div data-role="popup" id="add-user" data-overlay-theme="b">
        <ul data-inset="true" data-role="listview">
            <li data-role="list-divider" data-theme="b"><?php echo _("Add New User"); ?></li>
            <li>
                <form action="javascript:add_user()">
                    <label for="name"><?php echo _("Username:"); ?></label>
                    <input autocapitalize="off" autocorrect="off" type="text" id="name" value="" />
                    <label for="pass"><?php echo _("Password:"); ?></label>
                    <input type="password" id="pass" value="" />
                    <label for="pass-confirm"><?php echo _("Confirm Password:"); ?></label>
                    <input type="password" id="pass-confirm" value="" />
                    <input type="submit" value="<?php echo _("Submit"); ?>" />
                </form>
            </li>
        </ul>
    </div>
</div>

<div data-role="page" id="os-stations">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Edit Stations"); ?></h3>
        <a href="#settings" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="submit_stations();"><?php echo _("Submit"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <ul data-role="listview" data-inset="true" id="os-stations-list">
        </ul>
    </div>
</div>

<div data-role="page" id="preview">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Program Preview"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <div id="preview_header">
            <a onclick="changeday(-1);" class="ui-btn ui-btn-icon-notext ui-icon-carat-l btn-no-border"></a>
            <input style="text-align:center" type="date" name="preview_date" id="preview_date" />
            <a onclick="changeday(1);" class="ui-btn ui-btn-icon-notext ui-icon-carat-r btn-no-border"></a>
        </div>
        <div id="timeline"></div>
        <div data-role="controlgroup" data-type="horizontal" id="timeline-navigation">
            <a href="#" onclick="timeline.zoom(0.4)" class="ui-btn ui-corner-all ui-icon-plus ui-btn-icon-notext btn-no-border" title="<?php echo _("Zoom in"); ?>"></a>
            <a href="#" onclick="timeline.zoom(-0.4)" class="ui-btn ui-corner-all ui-icon-minus ui-btn-icon-notext btn-no-border" title="<?php echo _("Zoom out"); ?>"></a>
            <a href="#" onclick="timeline.move(-0.2)" class="ui-btn ui-corner-all ui-icon-carat-l ui-btn-icon-notext btn-no-border" title="<?php echo _("Move left"); ?>"></a>
            <a href="#" onclick="timeline.move(0.2)" class="ui-btn ui-corner-all ui-icon-carat-r ui-btn-icon-notext btn-no-border" title="<?php echo _("Move right"); ?>"></a>
        </div>
    </div>
</div>

<div data-role="page" id="about">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("About"); ?></h3>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <div data-role="collapsible-set">
            <div data-role="collapsible">
                <h3>Background</h3>
                <p>I, Samer Albahra, am a medical school graduate, currently doing a pathology residency at UTHSCSA. I enjoy making mobile applications in my spare time and was excited when I first discovered the OpenSprinkler, an open-source Internet based sprinkler system, which lacked a truly mobile interface.</p>
                <p>I decided to add a mobile front-end using jQuery Mobile. There were a few things I wanted to accomplish:</p>
                <ul><li>Large on/off buttons in manual mode</li><li>Easy slider inputs for any duration input</li><li>Compatibility between many/all devices</li><li>Easy feedback of current status</li><li>Easy program input/modification</li></ul>
                <p>Fortunately, I had a lot of feedback on Ray's forums and now have an application that has been tested across many devices and installed in many unique environments.</p>
                <p>I fully support every feature of the OpenSprinkler and also the OpenSprinkler Pi (using the interval program).</p>
                <p>Changelog can be viewed on <a target="_blank" href="https://github.com/salbahra/OpenSprinkler-Controller/commits/master">Github</a>.</p>
            </div>
            <div data-role="collapsible" data-collapsed="false" id="donate">
                <h3>Donate</h3>
                    <p>
                        This app has been developed by Samer Albahra. If you enjoy it please donate by clicking the button below.<br><br>
                        <a href="javascript:window.open('https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=89M484QR2TCFJ','_blank')"><img src="img/btn_donateCC_LG.gif" alt="PayPal - The safer, easier way to pay online!"></a>
                    </p>
            </div>
        </div>
        <p id='versions'>
            <?php
                echo "Firmware Version: ".$_SESSION["data"]["ver"];
                if (file_exists(".git/FETCH_HEAD")) {
                    $data = file_get_contents(".git/FETCH_HEAD");
                    if ($data !== false) {
                        preg_match("/\w{40}/", $data, $commit);
                        echo "<br>Mobile Version: <span id='commit' data-commit='".$commit[0]."'><a target='_blank' href='https://github.com/salbahra/OpenSprinkler-Controller/commit/".$commit[0]."'>".substr($commit[0], 0,7)."</a></span>";
                    }
                }
            ?>
         </p>
    </div>
</div>
