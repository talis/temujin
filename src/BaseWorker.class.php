<?php

abstract class BaseWorker
{
    protected $gearmanWorker;

    /**
     * @var GearmanJob
     */
    protected $job;

    /**
     * @var JobTracker
     */
    protected $jobTracker;

    protected function getVersion(){ throw new Exception("Implement This"); }
    protected function getFunctions(){ throw new Exception("Implement This"); }

    /**
     * @var array $log this stores log messages that you want to buffer in memory.
     */
    protected $log = array();

    /**
     * @param int $maxJobs the number of jobs this worker will process before exiting. If the value is 0 ( or less ) the worker will run indefinitely.
     * @throws Exception if GEARMAN_JOB_SERVERS constant is not defined
     */
    public function setupWorker($maxJobs=0)
    {
        if(!defined('GEARMAN_JOB_SERVERS'))
        {
            throw new Exception('GEARMAN_JOB_SERVERS constant is not defined');
        }

        register_shutdown_function(array($this, 'shutDownFunction'));
        
        $this->gearmanWorker = new GearmanWorker();
        $this->gearmanWorker->addOptions(GEARMAN_WORKER_GRAB_UNIQ);
        
        $servers = preg_split('/,/', GEARMAN_JOB_SERVERS, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($servers as $server)
        {
            $parts = preg_split('/:/', $server, -1, PREG_SPLIT_NO_EMPTY);
            $this->gearmanWorker->addServer($parts[0],$parts[1]);
        }

        $functions = $this->getFunctions();
        $version = $this->getVersion();
        $workerName = $this->getWorkerName();
        
        foreach($functions as $function)
        {
            echo "Adding function: {$workerName}_{$version}_{$function}\n";
            $this->gearmanWorker->addFunction( "{$workerName}_{$version}_{$function}", array($this, $function) );
        }

        $this->gearmanWorker->addFunction( "{$workerName}_{$version}_kill", array($this, 'kill') );        
        $this->gearmanWorker->addFunction( "{$workerName}_{$version}_functions", array($this, 'listFunctions') );

        echo "Starting $workerName ...\n";

        if($maxJobs <= 0) {
            echo "Worker will run indefinitely, max jobs is set to {$maxJobs}. Must be greater than 0 for limit to be enforced.\n";
            while( $this->gearmanWorker->work() )
            {
                echo "Waiting ...\n";
            }
        } else {
            echo "Worker will process {$maxJobs} jobs before exiting ...\n";
            for($i=0; $maxJobs > $i; ++$i)
            {
                $this->gearmanWorker->work();
                echo "\tprocessed job ".($i+1)."\n";
            }
            $this->endWorker("Processed {$maxJobs} jobs; Exiting\n");
        }
    }

    /**
     * Call this method to explicitly exit the worker. You must pass in a message.
     * @param string $message to print out when the worker exits
     */
    protected function endWorker($message)
    {
        exit($message);
    }

    /**
     * @return mixed the name of the worker
     */
    public function getWorkerName()
    {
        return get_class($this);
    }
    
    public function listFunctions($job)
    {
        $functions = implode(', ', $this->getFunctions());
        echo "$functions\n";
        return $functions;
    }
    
    public function kill($job)
    {
        $msg = unserialize($job->workload());
        $job->sendComplete("Done");
        echo "{$msg}\n";
        exit();
    }
	 
	public function shutDownFunction()
	{
        $error = error_get_last();

        if ($error['type'] == 1)
        {
            if(isset($this->job) && isset($this->jobTracker))
            {
                $this->jobTracker->setFailed($this->flushLog() . "\n\nFATAL ERROR:" . $error['message']);
                $this->job->sendFail();
            }
        }
	}

    /**
     * Get message from user call to log message, prepends date strings to it and add message to log array.
     * @param String $message
     */
    protected function log($message)
    {
        $date = date('c');
        $logMessage = "{$date} - " . $message;
        array_push($this->log, $logMessage);
    }

    /**
     * Empties member variable log and returns content as string
     * @return String
     */
    protected function flushLog()
    {
        $logText = implode("\n", $this->log);
        $this->log = array();
        return $logText;
    }
}
?>