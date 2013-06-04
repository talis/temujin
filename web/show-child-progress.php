<?php
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . dirname(__FILE__)
  . PATH_SEPARATOR . dirname(dirname(__FILE__)).'/src');

require_once 'DB.class.php';
require_once '../config/example.config.php';

date_default_timezone_set('Europe/London');

if (isset($_GET['parent'])) {
    $parent = $_GET['parent'];
    $result = get_result($parent);
    
    $completed = 0;
    $queued = 0;
    $inprogress = 0;
    while ($row = mysql_fetch_row($result)) {
        if ($row[0] == "COMPLETED")
        {
            $completed = $row[1];
        }
        else if ($row[0] == "IN_PROGRESS")
        {
            $inprogress = $row[1];
        }
        else if ($row[0] == "QUEUED")
        {
            $queued = $row[1];
        }
    }

    if (isset($_GET['ajax'])) {
        $results = array();
        $results["queued"] = $queued;
        $results["inprogress"] = $inprogress;
        $results["completed"] = $completed;
        echo json_encode($results);
    } else { ?>
    <html>
            <head><title>Gearman long-running-task monitor</title></head>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js" type="text/javascript"></script>
            <script src="/js/reload-child-data.js" type="text/javascript"></script>
            <body>
                <h1>Displaying progress for children of <?php echo $parent ?></h1>
                <div id="chart"><img src="http://chart.apis.google.com/chart?chs=440x220&cht=lxy&chco=3072F3,FF0000,FF9900&chds=0,100,6.667,100,0,100,5,100&chd=t:-1|<?php echo $queued ?>|-1|<?php echo $inprogress ?>|-1|<?php echo $completed ?>&chdl=Queued|In+Progress|Completed&chdlp=b&chls=2,4,1|1|1&chma=5,5,5,25"/></div>
                <table>
                    <thead>
                        <tr>
                            <th>Time (s)</th>
                            <th>Queued</th>
                            <th>In Progress</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody id="data">
                    <?php
                    echo "<tr><td class='time'>0</td><td class='queued'>$queued</td><td class='inprogress'>$inprogress</td><td class='completed'>$completed</td></tr>"
                    ?>
                    </tbody>
                </table>
            </body>
    </html>
    <?php }     
}
else
{ ?>
    <html>
            <head><title>Gearman long-running-task monitor</title></head>
            <body>
                <h1>Sorry!</h1>
                <p>You need to suppy a valid key for a parent job on the querystring</p>
            </body>
    </html>
    
<?php }

function get_result($parent) {
    $DB = DB::Open(JOB_TRACKER_DB_NAME, JOB_TRACKER_DB_HOST, JOB_TRACKER_DB_PORT, JOB_TRACKER_DB_USER, JOB_TRACKER_DB_PASS);
    $result = $DB->query("select status, count(status) from gearman_status_updates where parent_key='$parent' group by status;");
    return $result;
}

