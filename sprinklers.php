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
        <h3><?php echo $webtitle; ?></h3>
    </div>
    <div data-role="content">
        <p style="text-align:center" id="tip"></p>
        <ul data-role="listview" data-inset="true">
            <li data-role="list-divider">Main Menu</li>
            <li><a href="javascript:get_status()">Current Status</a></li>
            <li><a href="#manual">Manual Control</a></li>
            <li><a href="#runonce">Run-Once Program</a></li>
            <li><a href="javascript:get_programs()">View/Change Programs</a></li>
            <li><a href="#raindelay" data-rel="dialog">Rain Delay</a></li>
            <li><a href="javascript:get_logs()">View Log</a></li>
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
            <li><a href="javascript:rsn()">Stop All Stations</a></li>
        </ul>
    </div>
    <?php echo make_panel("sprinklers"); ?>
</div>

<div data-role="page" id="status">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Current Status</h3>
        <a href="javascript:gohome()" data-icon="back">Back</a>
        <a href="javascript:get_status()" data-icon="refresh">Refresh</a>
    </div>
    <div data-role="content">
        <ul data-role="listview" data-inset="true" id="status_list">
        </ul>
    </div>
</div>

<div data-role="page" id="manual">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Manual Control</h3>
        <a href="javascript:gohome()" data-icon="back">Back</a>
    </div>
    <div data-role="content">
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
            <?php echo make_list_manual(); ?>
        </ul>
    </div>
</div>

<div data-role="page" id="runonce">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Run-Once Program</h3>
        <a href="javascript:gohome()" data-icon="back">Back</a>
        <a href="javascript:submit_runonce()">Submit</a>
    </div>
    <div data-role="content" id="runonce_list">
        <?php echo make_runonce(); ?>
    </div>
</div>

<div data-role="page" id="programs">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Programs</h3>
        <a href="javascript:gohome()" data-icon="back">Back</a>
        <a href="javascript:add_program()" data-icon="plus">Add</a>
    </div>
    <div data-role="content" id="programs_list">
    </div>
</div>

<div data-role="page" id="addprogram">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Add Program</h3>
        <a href="javascript:get_programs()" data-icon="back">Back</a>
        <a href="javascript:submit_program('new')">Submit</a>
    </div>
    <div data-role="content" id="newprogram">
    </div>
</div>

<div data-role="page" id="raindelay" data-close-btn="none" data-overlay-theme="a" data-theme="c" class="ui-corner-all">
    <div data-role="header" data-theme="a" class="ui-corner-top">
        <h1>Rain Delay</h1>
    </div>
    <div data-role="content" data-theme="d">
        <form action="javascript:raindelay()">
            <p>To turn off use a value of 0.</p>
            <label for="delay">Duration (in hours):</label>
            <input type="number" name="delay" pattern="[0-9]*" id="delay" value="0">
            <input type="submit" value="Submit" data-mini="true">
        </form>
    <a href="javascript:gohome()" data-role="button" data-mini="true" data-theme="a">Cancel</a>
    </div>
</div>

<div data-role="page" id="logs">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>Logs</h3>
        <a href="javascript:gohome()" data-icon="back">Back</a>
        <a href="javascript:get_logs()" data-icon="refresh">Refresh</a>
    </div>
    <div data-role="content">
        <ul data-role="listview" data-inset="true" id="logs_list">
        </ul>
    </div>
</div>

<div data-role="page" id="os-settings">
    <div data-theme="a" data-role="header" data-position="fixed" data-tap-toggle="false">
        <h3>OS Settings</h3>
        <a href="javascript:gohome()" data-icon="back">Back</a>
        <a href="javascript:submit_settings()">Submit</a>
    </div>
    <div data-role="content">
    </div>
</div>