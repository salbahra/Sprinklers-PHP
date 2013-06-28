<?
#Start session
if(!isset($_SESSION)) session_start();

if(!defined('Sprinklers')) {

    #Tell main we are calling it
    define('Sprinklers', TRUE);

    #Required files
    require_once "main.php";
}

#Redirect if not authenticated or grabbing page directly
if (!is_auth() || !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {header('Location: '.$base_url); exit();}
?>
<script><?php include_once("js/main.js.php"); ?></script>

<div data-role="page" id="sprinklers">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <a data-icon="bars" data-iconpos="notext" href="#sprinklers-settings"></a>
        <a data-icon="gear" data-iconpos="notext" href="#os-settings" onclick="show_settings(); return false;">Settings</a>
        <h3><?php echo $webtitle; ?></h3>
    </div>
    <div data-role="content">
        <div id="weather">Loading Weather...</div>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">Program Control</li>
            <li><a href="#programs" onclick="get_programs(); return false;">Edit Programs</a></li>
            <li><a href="#manual" onclick="get_manual(); return false;">Manual Control</a></li>
            <li><a href="#raindelay">Rain Delay</a></li>
            <li><a href="#runonce" onclick="get_runonce(); return false;">Run-Once Program</a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">System Information</li>
            <li><a href="#status" onclick="get_status(); return false;">Current Status</a></li>
            <li><a href="#preview">Preview Programs</a></li>
            <li><a href="#logs" onclick="get_logs(); return false;">View Logs</a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">System Control</li>
            <li data-role="fieldcontain">
                <label for="en"><b>Operation</b></label>
                <select name="en" id="en" data-role="slider">
                    <option value="off">Off</option>
                    <option <?php echo is_en(); ?> value="on">On</option>
                </select>
            </li>
            <li data-role="fieldcontain">
                <label for="mm"><b>Manual Mode</b></label>
                <select name="mm" id="mm" data-role="slider">
                    <option value="off">Off</option>
                    <option <?php echo is_mm(); ?> value="on">On</option>
                </select>
            </li>
            <li><a href="#" onclick="rsn(); return false;">Stop All Stations</a></li>
            <li data-icon="alert"><a href="#" onclick="rbt(); return false;">Reboot OpenSprinkler</a></li>
        </ul>
    </div>
    <div data-role="panel" id="sprinklers-settings" data-position-fixed="true" data-theme="a">
        <ul data-role="listview" data-theme="a">
            <li>Logged in as: <?php echo $_SESSION["username"] ?></li>
            <li>
                <div class="ui-grid-a">
                    <div class="ui-block-a"><br>
                        <label for="autologin">Auto Login</label>
                    </div>
                    <div class="ui-block-b">
                        <select name="autologin" id="'.$page.'-autologin" data-role="slider">
                            <option value="off">Off</option>
                            <option value="on">On</option>
                        </select>
                    </div>
                </div>
            </li>
            <li data-icon="forward"><a href="#" onclick="export_config(); return false;">Export Configuration</a></li>
            <li data-icon="back"><a href="#" onclick="import_config(); return false;">Import Configuration</a></li>
            <li data-icon="delete"><a href="#" onclick="logout(); return false;">Logout</a></li>
            <li data-icon="info"><a href="#about">About</a></li>
        </ul>
    </div>
</div>

<div data-role="page" id="status">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Current Status</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
        <a href="#" onclick="get_status(); return false;" data-icon="refresh">Refresh</a>
    </div>
    <div data-role="content">
        <ul data-role="listview" data-inset="true" id="status_list">
        </ul>
    </div>
</div>

<div data-role="page" id="manual">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Manual Control</h3>
        <a href="#" onclick="gohome(); return false;" data-icon="back">Back</a>
    </div>
    <div data-role="content">
        <p style="text-align:center">With manual mode turned on, tap a station to toggle it.</p>
        <ul data-role="listview" data-inset="true">
            <li data-role="fieldcontain">
                <label for="mmm"><b>Manual Mode</b></label>
                <select name="mmm" id="mmm" data-role="slider">
                    <option value="off">Off</option>
                    <option <?php echo is_mm(); ?> value="on">On</option>
                </select>
            </li>
        </ul>
        <ul data-role="listview" data-inset="true" id="mm_list">
        </ul>
    </div>
</div>

<div data-role="page" id="runonce">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Run-Once Program</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
        <a href="#" onclick="submit_runonce(); return false;">Submit</a>
    </div>
    <div data-role="content" id="runonce_list">
    </div>
</div>

<div data-role="page" id="programs">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Programs</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
        <a href="#" onclick="add_program(); return false;" data-icon="plus">Add</a>
    </div>
    <div data-role="content" id="programs_list">
    </div>
</div>

<div data-role="page" id="addprogram">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Add Program</h3>
        <a href="#programs" onclick="get_programs(); return false;" data-icon="back">Back</a>
        <a href="#" onclick="submit_program('new'); return false;">Submit</a>
    </div>
    <div data-role="content" id="newprogram">
    </div>
</div>

<div data-role="dialog" id="raindelay" data-close-btn="none" data-overlay-theme="a" data-theme="c" class="ui-corner-all">
    <div data-role="header" data-theme="a" class="ui-corner-top">
        <h1>Rain Delay</h1>
    </div>
    <div data-role="content" data-theme="d">
        <form action="javascript:raindelay()">
            <p>To turn off use a value of 0.</p>
            <label for="delay">Duration (in hours):</label>
            <input type="number" name="delay" pattern="[0-9]*" id="delay" value="0">
            <input type="submit" data-mini="true" value="Submit" />
        </form>
        <a href="#sprinklers" onclick="gohome(); return false;" data-role="button" data-mini="true" data-theme="a">Cancel</a>
    </div>
</div>

<div data-role="page" id="logs">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Logs</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
        <a href="#" onclick="get_logs(); return false;" data-icon="refresh">Refresh</a>
    </div>
    <div data-role="content">
        <p style='text-align:center'>Viewing data for the last <?php global $timeViewWindow;echo strtolower($timeViewWindow); ?>.</p>
        <ul data-role="listview" data-inset="true" id="logs_list">
        </ul>
    </div>
</div>

<div data-role="page" id="os-settings">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>OS Settings</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
        <a href="#" onclick="submit_settings(); return false;">Submit</a>
    </div>
    <div data-role="content">
    </div>
</div>

<div data-role="page" id="preview">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Program Preview</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
    </div>
    <div data-role="content">
        <input style="text-align:center" type="date" name="preview_date" id="preview_date" />
        <div id="timeline"></div>
    </div>
</div>

<div data-role="page" id="about">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>About</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
    </div>
    <div data-role="content">
        <div data-role="collapsible-set" data-content-theme="d">
            <div data-role="collapsible" data-collapsed="false">
                <h3>Background</h3>
                <p>I, Samer Albahra, am a medical school graduate, currently doing a pathology residency at UTHSCSA. I enjoy making mobile applications in my spare time and was excited when I first discovered the OpenSprinkler, an open-source Internet based sprinkler system, which lacked a truly mobile interface.</p>
                <p>I decided to add a mobile front-end using jQuery Mobile. There were a few things I wanted to accomplish:</p>
                <ul><li>Large on/off buttons in manual mode</li><li>Easy slider inputs for any duration input</li><li>Compatibility between many/all devices</li><li>Easy feedback of current status</li><li>Easy program input/modification</li></ul>
                <p>Fortunately, I had a lot of feedback on Ray's forums and now have an application that has been tested across many devices and installed in many unique environments.</p>
                <p>I fully support every feature of the OpenSprinkler and also the OpenSprinkler Pi (using the interval program).</p>
                <p>Changelog can be viewed on <a target="_blank" href="https://github.com/salbahra/OpenSprinkler-Controller/commits/master">Github</a>.</p>
            </div>
            <div data-role="collapsible">
                <h3>Donate</h3>
                    <p style="text-align:center;overflow: visible;white-space: normal;">This web app has been developed by Samer Albahra. If you find it useful please donate to him by clicking the button below.</p><br>
                    <form style='text-align:center' action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="89M484QR2TCFJ">
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>
            </div>
        </div>
    </div>
</div>

<div data-role="dialog" id="sure" data-title="Are you sure?">
    <div data-role="content">
        <h3 class="sure-1" style="text-align:center"></h3>
        <p class="sure-2" style="text-align:center"></p>
        <a class="sure-do" data-role="button" data-theme="b" href="#">Yes</a>
        <a class="sure-dont" data-role="button" data-theme="c" href="#">No</a>
    </div>
</div>