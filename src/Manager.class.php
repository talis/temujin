<?php
require_once('ManagerClient.class.php');

class Manager
{
    protected $multiByteSupport = null;
    
    private $maxNumberOfInstancesForWorker = 5;
    
    public function __construct($host, $port, $timeout = 5)
    {
        $errCode    = 0;
        $errMsg     = '';
        $this->conn = @fsockopen($host, $port, $errCode, $errMsg, $timeout);
        if ($this->conn === false) {
            throw new Exception(
                'Could not connect to ' . $host . ':' . $port
            );
        }
        
        echo "Connected ...\n";
    }

    public function version()
    {
        $this->sendCommand('version');
        $res = fgets($this->conn, 4096);
        $this->checkForError($res);
        return trim($res);
    }
    
    public function status()
    {
        $this->sendCommand('status');
        $res = $this->recvCommand();

        $status = array();
        $tmp    = explode("\n", $res);
        foreach ($tmp as $t) {
            if (!$this->stringLength($t)) {
                continue;
            }

            list($func, $inQueue, $jobsRunning, $capable) = explode("\t", $t);

            $status[$func] = array(
                'in_queue' => $inQueue,
                'jobs_running' => $jobsRunning,
                'capable_workers' => $capable
            );
        }

        return $status;
    }

    public function getRequiredWorkers()
    {
        $requiredWorkers = array();
        $registeredFunctions = $this->status();
        foreach($registeredFunctions as $registeredFunction=>$stats)
        {
            $inQueue = $stats['in_queue'];
            $jobsRunning = $stats['jobs_running'];
            $capable = $stats['capable_workers'];

            // TODO: this check needs to be more sophisticated
            // at the moment its pretty naive
            if($inQueue > 0  && $capable==0)
            {
                list($_workerName, $_version, $_function) = preg_split("/_/",$registeredFunction, -1, PREG_SPLIT_NO_EMPTY);
                $key = $_workerName.'_'.$_version;
                if(!array_key_exists($key, $requiredWorkers))
                {
                    $requiredWorkers[$key] = ($inQueue > $this->maxNumberOfInstancesForWorker) ? $this->maxNumberOfInstancesForWorker : $inQueue; 
                }
            }
        }
        
        return $requiredWorkers;
    }

    public function getCurrentWorkers()
    {
        $registeredWorkers=array();
        $registeredFunctions = $this->status();
        foreach($registeredFunctions as $registeredFunction=>$stats)
        {
            $inQueue = $stats['in_queue'];
            $jobsRunning = $stats['jobs_running'];
            $capable = $stats['capable_workers'];
            
            if($capable > 0)
            {
                list($_workerName, $_version, $_function) = preg_split("/_/",$registeredFunction, -1, PREG_SPLIT_NO_EMPTY);
                $key = $_workerName.'_'.$_version;
                
                if(!array_key_exists($key, $registeredWorkers))
                {
                    $registeredWorkers[$key] = $capable;
                }
            }
        }
        return $registeredWorkers;
    }

    
    public function launchWorkers($workerName, $numWorkers=1, $workerHome=null)
    {
        if(!empty($workerName))
        {
            for($i=0; $i<$numWorkers; $i++)
            {
                echo "Launching ... $workerName\n";
                
                $path = "";
                if(!empty($workerHome))
                {
                    $path .= $workerHome;
                }
                
                $path .= "Start_{$workerName}.php";
                
                exec("nohup php {$path} > ~/nohup.out 2>&1 </dev/null &");
            }
        }
    }
    
    public function launchRequiredWorkers($workerHome=null)
    {
        $requiredWorkers = $this->getRequiredWorkers();
        
        foreach ($requiredWorkers as $key=>$instances)
        {
            $numberOfInstancesToStart = $instances;
            list($_workerName, $_version) = preg_split("/_/",$key, -1, PREG_SPLIT_NO_EMPTY);
            echo "Starting {$numberOfInstancesToStart} instances of {$_workerName} at version {$_version} ...\n";
            
            $this->launchWorkers($_workerName, $numberOfInstancesToStart, $workerHome);
        }
    }

    public function killWorkers($workerName, $version)
    {
        $currentWorkers = $this->getCurrentWorkers();
        $key = $workerName.'_'.$version;
        if(array_key_exists($key, $currentWorkers))
        {
            $numberOfInstancesToKill = $currentWorkers[$key];
            echo "Found {$numberOfInstancesToKill} instances of {$workerName} at version {$version} to kill ...\n";
            for($i=0; $i<$numberOfInstancesToKill; $i++)
            {
                $managerClient = new ManagerClient();
                $managerClient->killWorker($workerName, $version, 'Killed By Manager');
            }
        }
        
        // TODO: could wait here in a loop calling get current workers until all workers matching specified criteria
        // have been killed, and only return when successful?
    }

    public function disconnect()
    {
        if (is_resource($this->conn)) {
            fclose($this->conn);
        }
    }
    
    private function checkForError($data)
    {
        $data = trim($data); 
        if (preg_match('/^ERR/', $data)) {
            list(, $code, $msg) = explode(' ', $data);
            throw new Exception($msg, urldecode($code));
        }
    }
    
    private function sendCommand($cmd)
    {
        fwrite($this->conn, 
               $cmd . "\r\n", 
               $this->stringLength($cmd . "\r\n"));
    }
    
    private function recvCommand()
    {
        $ret = '';
        while (true) {
            $data = fgets($this->conn, 4096);
            $this->checkForError($data);
            if ($data == ".\n") {
                break;
            }

            $ret .= $data;
        }

        return $ret;
    }
    
    
    private function stringLength($value)
    {
        if (is_null($this->multiByteSupport)) {
            $this->multiByteSupport = intval(ini_get('mbstring.func_overload'));
        }

        if ($this->multiByteSupport & 2) { 
            return mb_strlen($value, '8bit');
        } else {
            return strlen($value);
        }
    }
        
    public function __destruct()
    {
        $this->disconnect();
        echo "Disconnected ...\n";
    }
    
}

// $parts = preg_split('/:/', GEARMAN_JOB_SERVERS, -1, PREG_SPLIT_NO_EMPTY);
// $manager = new Manager($parts[0], $parts[1]);
// echo "version:".$manager->version()."\n";
// print_r($manager->status());
// print_r($manager->getCurrentWorkers());
// print_r($manager->getRequiredWorkers());

//$manager->launchWorkers('TextWorker', 5, '../examples/');
//$manager->launchRequiredWorkers('../examples/');

//$manager->killWorkers('TextWorker', '1');

?>