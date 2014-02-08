<?
#Start session
if(!isset($_SESSION)) session_start();

if(!defined('Sprinklers')) {

    #Tell main we are calling it
    define('Sprinklers', TRUE);

    #Required files
    require_once "main.php";
}
#Get data needed to render home page
$_SESSION["data"] = start_data();

#Redirect if not authenticated or grabbing page directly
if (!is_auth() || !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {header('Location: '.$base_url); exit();}
?>
<script><?php include_once("js/main.js.php"); ?></script>

<div data-role="page" id="sprinklers">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <a data-icon="bars" data-iconpos="notext" href="#sprinklers-settings"></a>
        <a data-icon="gear" data-iconpos="notext" href="#settings"><?php echo _("Settings"); ?></a>
        <h3 style="padding:0"><img height="40px" width="159px" src="img/logo.png" /></h3>
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
            <li><a href="#programs" data-onclick="get_programs();"><?php echo _("Edit Programs"); ?></a></li>
            <li><a href="#manual" data-onclick="get_manual();"><?php echo _("Manual Control"); ?></a></li>
            <li><a href="#raindelay"><?php echo _("Rain Delay"); ?></a></li>
            <li><a href="#runonce" data-onclick="get_runonce();"><?php echo _("Run-Once Program"); ?></a></li>
            <li><a href="#" data-onclick="rsn();"><?php echo _("Stop All Stations"); ?></a></li>
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
                        <select name="autologin" id="s-autologin" data-role="slider">
                            <option value="off"><?php echo _("Off"); ?></option>
                            <option value="on"><?php echo _("On"); ?></option>
                        </select>
                    </div>
                </div>
            </li>
            <li data-icon="forward"><a href="#" data-onclick="export_config();"><?php echo _("Export Configuration"); ?></a></li>
            <li data-icon="back"><a href="#" data-onclick="changeFromPanel(import_config);"><?php echo _("Import Configuration"); ?></a></li>
            <li data-icon="delete"><a href="#" data-onclick="changeFromPanel(logout);"><?php echo _("Logout"); ?></a></li>
            <li data-icon="info"><a href="#" data-onclick="changeFromPanel(show_about);"><?php echo _("About"); ?></a></li>
        </ul>
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
                <select name="mmm" id="mmm" data-role="slider">
                    <option value="off"><?php echo _("Off"); ?></option>
                    <option <?php echo $_SESSION["data"]["mm"]; ?> value="on"><?php echo _("On"); ?></option>
                </select>
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

<div data-role="page" id="raindelay">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h1><?php echo _("Rain Delay"); ?></h1>
        <a href="#sprinklers" data-onclick="gohome();" data-icon="back"><?php echo _("Back"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <p style="text-align:center"><?php echo _("Rain delay allows you to disable all programs for a set duration. You can manually set a rain delay or enable automatic rain delays."); ?></p>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider"><?php echo _("Manual Rain Delay"); ?></li>
            <li>
                <p class="rain-desc"><?php echo _("Enable manual rain delay by entering a value into the input below. To turn off a currently enabled rain delay use a value of 0."); ?></p>
                <form action="javascript:raindelay()">
                    <div class="ui-field-contain">
                        <label for="delay"><?php echo _("Duration (in hours):"); ?></label>
                        <input type='number' pattern='[0-9]*' data-highlight='true' data-type='range' value='0' min='0' max='96' id='delay' />
                    </div>
                    <input type="submit" value="<?php echo _("Submit"); ?>" data-theme="b" />
                </form>
            </li>
        </ul>
        <ul data-role='listview' data-inset='true'>
            <li data-role='list-divider'><?php echo _("Automatic Rain Delay"); ?></li>
            <li>
                <p class="rain-desc"><?php echo _("When automatic rain delay is enabled, the weather will be checked for rain every hour. If the weather reports any condition suggesting rain, a rain delay is automatically issued using the below set delay duration."); ?></p>
                <form action="javascript:auto_raindelay()">
                    <div data-role='fieldcontain'>
                        <label for='auto_delay'><?php echo _("Auto Rain Delay"); ?></label>
                        <select name='auto_delay' id='auto_delay' data-role='slider'>
                            <option value='off'><?php echo _("Off"); ?></option>
                            <option value='on'><?php echo _("On"); ?></option>
                        </select>
                    </div>
                    <label for='auto_delay_duration'><?php echo _("Delay Duration (hours)"); ?></label>
                    <input type='number' pattern='[0-9]*' data-highlight='true' data-type='range' min='0' max='96' id='auto_delay_duration' />
                    <input type="submit" value="<?php echo _("Submit"); ?>" data-theme="b" />
                </form>
            </li>
        </ul>
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
            <li><a href="#" data-onclick="clear_logs();"><?php echo _("Clear Logs"); ?></a></li>
            <li><a href="#" data-onclick="show_settings();"><?php echo _("Device Options"); ?></a></li>
            <li><a href="#" data-onclick="show_stations();"><?php echo _("Edit Stations"); ?></a></li>
            <li><a href="#" data-onclick="show_users();"><?php echo _("User Management"); ?></a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider"><?php echo _("System Control"); ?></li>
            <li class="ui-field-contain">
                <label for="mm"><b><?php echo _("Manual Mode"); ?></b></label>
                <select name="mm" id="mm" data-role="slider">
                    <option value="off"><?php echo _("Off"); ?></option>
                    <option <?php echo $_SESSION["data"]["mm"]; ?> value="on"><?php echo _("On"); ?></option>
                </select>
            </li>
            <li class="ui-field-contain">
                <label for="en"><b><?php echo _("Operation"); ?></b></label>
                <select name="en" id="en" data-role="slider">
                    <option value="off"><?php echo _("Off"); ?></option>
                    <option <?php echo $_SESSION["data"]["en"]; ?> value="on"><?php echo _("On"); ?></option>
                </select>
            </li>
            <li data-icon="alert"><a href="#" data-onclick="rbt();"><?php echo _("Reboot OpenSprinkler"); ?></a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider"><?php echo _("Automatically Disable Manual Mode"); ?></li>
            <li>
                <p class="rain-desc"><?php echo _("Automatically disable manual mode at midnight. Use this option to turn off manual mode and ensure programs run even if you forget manual mode enabled."); ?></p>
            </li>
            <li class="ui-field-contain">
                <label for="auto_mm"><b><?php echo _("Enabled"); ?></b></label>
                <select name="auto_mm" id="auto_mm" data-role="slider">
                    <option value="off"><?php echo _("Off"); ?></option>
                    <option <?php global $auto_mm; echo (($auto_mm) ? "selected" : "") ?> value="on"><?php echo _("On"); ?></option>
				</select>
            </li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider"><?php echo _("Local Assets"); ?></li>
            <li>
                <p class="rain-desc"><?php echo _("Assets are javascript and CSS libraries that power the mobile web app. Choose between local assets or content distibution network (CDN) hosted assets."); ?></p>
            </li>
            <li class="ui-field-contain">
                <label for="local_assets"><b><?php echo _("Enabled"); ?></b></label>
                <select name="local_assets" id="local_assets" data-role="slider">
                    <option value="off"><?php echo _("Off"); ?></option>
                    <option <?php global $local_assets; echo (($local_assets) ? "selected" : "") ?> value="on"><?php echo _("On"); ?></option>
                </select>
            </li>
        </ul>
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

<div data-role="page" id="user-control">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("User Control"); ?></h3>
        <a href="#settings" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#add-user" data-icon="plus"><?php echo _("Add"); ?></a>
    </div>
    <div class="ui-content" role="main" id="user-control-list">
    </div>
</div>

<div data-role="page" id="add-user">
    <div data-theme="b" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3><?php echo _("Add User"); ?></h3>
        <a href="#user-control" data-icon="back"><?php echo _("Back"); ?></a>
        <a href="#" data-onclick="add_user();"><?php echo _("Submit"); ?></a>
    </div>
    <div class="ui-content" role="main">
        <ul data-inset="true" data-role="listview">
            <li data-role="list-divider"><?php echo _("Add New User"); ?></li>
            <li>
                <div class="ui-field-contain">
                    <label for="name"><?php echo _("Username:"); ?></label>
                    <input autocapitalize="off" autocorrect="off" type="text" id="name" value="" />
                    <label for="pass"><?php echo _("Password:"); ?></label>
                    <input type="password" id="pass" value="" />
                    <a data-role="button" href="#" data-onclick="add_user();" data-theme="a"><?php echo _("Submit"); ?></a>
                </div>
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
            <a onclick="changeday(-1);" class="ui-btn ui-btn-icon-notext ui-icon-carat-l"></a>
            <input style="text-align:center" type="date" name="preview_date" id="preview_date" />
            <a onclick="changeday(1);" class="ui-btn ui-btn-icon-notext ui-icon-carat-r"></a>
        </div>
        <div id="timeline"></div>
        <div id="timeline-navigation" style="display:none;width:144px;margin:0 auto">
            <div class="timeline-navigation-zoom-in" onclick="timeline.zoom(0.4)" title="<?php echo _("Zoom in"); ?>"></div>
            <div class="timeline-navigation-zoom-out" onclick="timeline.zoom(-0.4)" title="<?php echo _("Zoom out"); ?>"></div>
            <div class="timeline-navigation-move-left" onclick="timeline.move(-0.2)" title="<?php echo _("Move left"); ?>"></div>
            <div class="timeline-navigation-move-right" onclick="timeline.move(0.2)" title="<?php echo _("Move right"); ?>"></div>
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
            <div data-role="collapsible" data-collapsed="false">
                <h3>Donate</h3>
                    <p style="text-align:center;overflow: visible;white-space: normal;">This web app has been developed by Samer Albahra. If you enjoy it please donate by clicking the button below.</p>
                    <form style='text-align:center' action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="89M484QR2TCFJ">
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>
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

<div data-role="page" data-dialog="true" id="sure" data-title="Are you sure?">
    <div class="ui-content" role="main">
        <h3 class="sure-1" style="text-align:center"></h3>
        <p class="sure-2" style="text-align:center"></p>
        <a class="sure-do" data-role="button" data-theme="b" href="#"><?php echo _("Yes"); ?></a>
        <a class="sure-dont" data-role="button" data-theme="a" href="#"><?php echo _("No"); ?></a>
    </div>
</div>
