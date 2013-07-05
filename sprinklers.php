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
        <a data-icon="gear" data-iconpos="notext" href="#settings">Settings</a>
        <h3 style="margin:0"><img height="40px" width="159px" src="img/logo.png" /></h3>
    </div>
    <div data-role="content">
        <div id="weather">Loading Weather...</div>
        <p id="showupdate" style="display:none;text-align:center">Update Available</p>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">Information</li>
            <li><a href="#status" onclick="get_status(); return false;">Current Status</a></li>
            <li><a href="#preview">Preview Programs</a></li>
            <li><a href="#logs" onclick="get_logs(); return false;">View Logs</a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">Program Control</li>
            <li><a href="#programs" onclick="get_programs(); return false;">Edit Programs</a></li>
            <li><a href="#manual" onclick="get_manual(); return false;">Manual Control</a></li>
            <li><a href="#raindelay">Rain Delay</a></li>
            <li><a href="#runonce" onclick="get_runonce(); return false;">Run-Once Program</a></li>
            <li><a href="#" onclick="rsn(); return false;">Stop All Stations</a></li>
        </ul>
    </div>
    <div id="footer-running" data-role="footer" data-position-fixed="true" data-position="fixed" data-theme="a">
        <img id="running-icon" width="11px" height="11px" src="img/running.png" />
        <p id="running-text"></p>
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

<div data-role="page" id="raindelay">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h1>Rain Delay</h1>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
    </div>
    <div data-role="content">
        <p style="text-align:center">Rain delay allows you to disable all programs for a set duration. You can manually set a rain delay or enable automatic rain delays.</p>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">Manual Rain Delay</li>
            <li>
                <p class="rain-desc">Enable manual rain delay by entering a value into the input below. To turn off a currently enabled rain delay use a value of 0.</p>
                <form action="javascript:raindelay()">
                    <div data-role="fieldcontain">
                        <label for="delay">Duration (in hours):</label>
                        <input type="number" name="delay" pattern="[0-9]*" id="delay" value="">
                    </div>
                    <input type="submit" value="Submit" />
                </form>
            </li>
        </ul>
        <ul data-role='listview' data-inset='true'>
            <li data-role='list-divider'>Automatic Rain Delay</li>
            <li>
                <p class="rain-desc">When automatic rain delay is enabled, the weather will be checked for rain every hour. If the weather reports any condition suggesting rain, a rain delay is automatically issued using the below set delay duration.</p>
                <form action="javascript:auto_raindelay()">
                    <div data-role='fieldcontain'>
                        <label for='auto_delay'>Auto Rain Delay</label>
                        <select name='auto_delay' id='auto_delay' data-role='slider'>
                            <option value='off'>Off</option>
                            <option value='on'>On</option>
                        </select>
                    </div>
                    <label for='auto_delay_duration'>Delay Duration (hours)</label>
                    <input type='number' pattern='[0-9]*' data-highlight='true' data-type='range' min='0' max='96' id='auto_delay_duration' />
                    <input type="submit" value="Submit" />
                </form>
            </li>
        </ul>
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
        <div id="logs_list">
        </div>
    </div>
</div>

<div data-role="page" id="settings">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Settings</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
    </div>
    <div data-role="content">
        <ul data-role="listview" data-inset="true">
            <li><a href="#" onclick="show_settings(); return false;">Device Options</a></li>
            <li><a href="#" onclick="show_stations(); return false;">Edit Stations</a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">System Control</li>
            <li data-role="fieldcontain">
                <label for="mm"><b>Manual Mode</b></label>
                <select name="mm" id="mm" data-role="slider">
                    <option value="off">Off</option>
                    <option <?php echo is_mm(); ?> value="on">On</option>
                </select>
            </li>
            <li data-role="fieldcontain">
                <label for="en"><b>Operation</b></label>
                <select name="en" id="en" data-role="slider">
                    <option value="off">Off</option>
                    <option <?php echo is_en(); ?> value="on">On</option>
                </select>
            </li>
            <li data-icon="alert"><a href="#" onclick="rbt(); return false;">Reboot OpenSprinkler</a></li>
        </ul>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">Automatically Disable Manual Mode</li>
            <li>
                <p class="rain-desc">Automatically disable manual mode at midnight. Use this option to turn off manual mode and ensure programs run even if you forget manual mode enabled.</p>
            </li>
            <li data-role="fieldcontain">
                <label for="auto_mm"><b>Enabled</b></label>
                <select name="auto_mm" id="auto_mm" data-role="slider">
                    <option value="off">Off</option>
                    <option <?php global $auto_mm; echo (($auto_mm) ? "selected" : "") ?> value="on">On</option>
                </select>
            </li>
        </ul>
    </div>
</div>

<div data-role="page" id="os-settings">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>OS Settings</h3>
        <a href="#settings" data-icon="back">Back</a>
        <a href="#" onclick="submit_settings(); return false;">Submit</a>
    </div>
    <div data-role="content">
        <ul data-role="listview" data-inset="true" id="os-settings-list">
        </ul>
    </div>
</div>

<div data-role="page" id="os-stations">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Stations</h3>
        <a href="#settings" data-icon="back">Back</a>
        <a href="#" onclick="submit_stations(); return false;">Submit</a>
    </div>
    <div data-role="content">
        <ul data-role="listview" data-inset="true" id="os-stations-list">
        </ul>
    </div>
</div>

<div data-role="page" id="preview">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Program Preview</h3>
        <a href="#sprinklers" onclick="gohome(); return false;" data-icon="back">Back</a>
    </div>
    <div data-role="content">
        <div style="white-space:nowrap;width:100%;text-align:center">
            <a href="#" onclick="changeday(-1); return false;"><img src="img/moveleft.png" /></a>
            <input style="text-align:center" type="date" name="preview_date" id="preview_date" />
            <a href="#" onclick="changeday(1); return false;"><img src="img/moveright.png" /></a>
        </div>
        <div id="timeline"></div>
        <div id="timeline-navigation" style="display:none;width:144px;margin:0 auto">
            <div class="timeline-navigation-zoom-in" onclick="timeline.zoom(0.4)" title="Zoom in"></div>
            <div class="timeline-navigation-zoom-out" onclick="timeline.zoom(-0.4)" title="Zoom out"></div>
            <div class="timeline-navigation-move-left" onclick="timeline.move(-0.2)" title="Move left"></div>
            <div class="timeline-navigation-move-right" onclick="timeline.move(0.2)" title="Move right"></div>
        </div>
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
                <?php
                    $data = file_get_contents(".git/FETCH_HEAD");
                    if ($data !== false) {
                        preg_match("/\w{40}/", $data, $commit);
                        echo "<p>Version: <span id='commit'>".$commit[0]."</span></p>";
                    }
                ?>
                <p>Changelog can be viewed on <a target="_blank" href="https://github.com/salbahra/OpenSprinkler-Controller/commits/master">Github</a>.</p>
            </div>
            <div data-role="collapsible">
                <h3>Donate</h3>
                    <p style="text-align:center;overflow: visible;white-space: normal;">This web app has been developed by Samer Albahra. If you find it useful please donate to him by clicking the button below.</p>
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