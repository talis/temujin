#!/usr/bin/php
<?php
require_once '../src/Manager.class.php';

function usage($argv)
{
    echo "Usage: php {$argv[0]} -h <workerhome> -a (start|stop|status) -w <worker name> -v <version> -n <number of workers> -s <optional list of gearman servers>\n";
    echo "e.g:\n";
    echo "    php {$argv[0]} -a status \n";
    echo "    php {$argv[0]} -h /tmp/workerhome/ -a start -w TextWorker -v 1 -n 3 -s 127.0.0.1:4730 \n";
    echo "    php {$argv[0]} -a stop -w TextWorker -v 1 \n";
    die();
}

$gearman_servers = null;
$options = getopt("a:w:v:n:s:h:");
if(count($options)==0 || !isset($options['a']))
{
    usage($argv);
}


$action = $options['a'];

if( isset($options['s'])) {
    define('GEARMAN_JOB_SERVERS', $options['s']);
} else {
    
    $gearman_servers = getenv('GEARMAN_JOB_SERVERS');
    if($gearman_servers == false)
    {
        echo "GEARMAN_JOB_SERVERS is not defined.\n";
        echo "You must configure this as either as  environment variable, or specify it on command line using the -s switch.\n";
        usage($argv);
    }
    define('GEARMAN_JOB_SERVERS', $gearman_servers);
}

$parts = preg_split('/:/', GEARMAN_JOB_SERVERS, -1, PREG_SPLIT_NO_EMPTY);
$manager = new Manager($parts[0], $parts[1]);

switch($action)
{
    case "status":
        echo("Current Status:\n");
        print_r($manager->status());
        die();
    case "start":
        if(!isset($options['w']) || !isset($options['v']))
        {
            die("Error: You must specify a Worker and Version to start\n");
        }
        $workerName = $options['w'];
        $version = $options['v'];
        $numWorkers = isset($options['n']) ? $options['n'] : 1;
        
        if(is_numeric($numWorkers))
        {
            if($numWorkers < 1) { $numWorkers = 1; }
        }
        
        if(isset($options['h']) && !empty($options['h']))
        {
            $manager->launchWorkers($workerName, $numWorkers, $options['h']);
        }
        
        $manager->launchWorkers($workerName, $numWorkers);
        die();
    case "stop":
        if(!isset($options['w']) || !isset($options['v']))
        {
            die("Error: You must specify a Worker and Version to start\n");
        }
        $workerName = $options['w'];
        $version = $options['v'];

        $manager->killWorkers($workerName, $version);
        die();
    default:
        echo ("Did not recognise action\n");
        die();
}
$manager->disconnect();
?>
