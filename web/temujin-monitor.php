<?php
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . dirname(__FILE__)
  . PATH_SEPARATOR . dirname(dirname(__FILE__)).'/src');

require_once 'DB.class.php';

date_default_timezone_set('Europe/London');

define("GM_DATABASE_NAME", "gearman");
define("GM_DATABASE_HOST", "localhost");
define("GM_DATABASE_PORT", null);
define("GM_DATABASE_USER", "root");
define("GM_DATABASE_PASS", null);


function get_jobs($status=null) {
    $DB = DB::Open(GM_DATABASE_NAME, GM_DATABASE_HOST, GM_DATABASE_PORT, GM_DATABASE_USER, GM_DATABASE_PASS);
    
    $query = "";
    if(!empty($status))
    {
        $query = "select * from gearman_status_updates where status='{$status}' order by last_updated desc;";
    }
    else
    {
        $query = "select * from gearman_status_updates order by last_updated desc;";
    }
    
    $result = $DB->query($query);
    
    return $result;
}

function get_count($filter)
{
    $DB = DB::Open(GM_DATABASE_NAME, GM_DATABASE_HOST, GM_DATABASE_PORT, GM_DATABASE_USER, GM_DATABASE_PASS);

    $query = "select count(*) from gearman_status_updates where status='{$filter}';";
    $result = $DB->query($query,3);
    return $result;
}

$filter = null;
if(isset($_GET['filter']))
{
    $filter = $_GET['filter'];
}

$results_data = get_jobs($filter);
$queuedCount = get_count('QUEUED');
$inProgressCount = get_count('IN_PROGRESS');
$completedCount = get_count('COMPLETED');
$failedCount = get_count('FAILED');

?>
<html>
    <head>
        <title>Temujin - Gearman Monitor</title>
    </head>
    <body>
    <h2>Summary</h2>
    <table border='1'>
    <thead>
        <tr>
            <th>Status</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Queued</td><td><?php echo "$queuedCount"; ?></td></tr>
        <tr><td>In Progress</td><td><?php echo "$inProgressCount"; ?></td></tr>
        <tr><td>Completed</td><td><?php echo "$completedCount"; ?></td></tr>
        <tr><td>Failed</td><td><?php echo "$failedCount"; ?></td></tr>
    </tbody>
    </table>
    
    <h2>Queue</h2>
    <table border='1'>
        <thead>
            <tr>
                <th>Key</th>
                <th>Function Name</th>
                <th>Job Data</th>
                <th>Status</th>
                <th>Duration</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysql_fetch_row($results_data) ) { ?>
            <tr valign='top'>
              <td><?php echo "$row[0]"; ?></td>
              <td><?php echo "$row[2]"; ?></td>
              <td><?php 
               $data = unserialize($row[3]);
               if(is_array($data)) {
                   echo print_r($data,true);
               } else {
               echo htmlspecialchars($data); 
               }
              ?></td>
              <?php
              $rowStatus = $row[4];
              $bgcolor = "lightgray";
              
              if($rowStatus=="QUEUED") {
                  $bgcolor="lightgray";
              } 
              
              if($rowStatus == 'IN_PROGRESS') {
                  $bgcolor="lightsalmon";
              }
              
              if($rowStatus == 'COMPLETED') {
                  $bgcolor="lightgreen";
              }
              
              if($rowStatus == 'FAILED') {
                  $bgcolor="crimson";
              }
              
              ?>
              <td bgcolor='<?php echo "$bgcolor"; ?>'><?php echo "$rowStatus"; ?></td>
              <td><?php echo "$row[8]"; ?></td>
              <td><?php 
                    $message = unserialize($row[5]);
                    if(is_array($message))
                    {
                        echo '<pre>'.htmlspecialchars(print_r($message,true)).'</pre>';
                        
                    } else {
                        echo '<pre>'.htmlspecialchars($message).'</pre>';
                    }
              ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </body>
</html>
