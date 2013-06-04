<?php
abstract class BaseClient
{
    /**
     * @var GearmanClient
     */
    protected $gearmanClient;

    protected $fails = array();
    
    /**
     * @var array $log this stores log messages that you want to buffer in memory.
     */
    protected $log = array();
    
    public function setupClient($completedCallbackFn=null)
    {
        if(!defined('GEARMAN_JOB_SERVERS'))
        {
            throw new Exception('GEARMAN_JOB_SERVERS constant is not defined');
        }
        
        $this->gearmanClient = new GearmanClient();
        
        $servers = preg_split('/,/', GEARMAN_JOB_SERVERS, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($servers as $server)
        {
            $parts = preg_split('/:/', $server, -1, PREG_SPLIT_NO_EMPTY);
            $this->gearmanClient->addServer($parts[0],$parts[1]);
        }
        
        $this->gearmanClient->setCreatedCallback(array($this,"taskCreated"));
        $this->gearmanClient->setExceptionCallback(array($this,"taskException"));
        $this->gearmanClient->setFailCallback(array($this,"taskFail"));
        if ($completedCallbackFn)
        {
            $this->gearmanClient->setCompleteCallback($completedCallbackFn);
        }
    }    

    // todo: do something more intelligent with these fns...
    function taskCreated($task)
    {
        // $uniq = $task->unique();
        // $hand = $task->jobHandle();
        // echo "Job {$hand}:{$uniq} was queued\n";
    }

    function taskException($task)
    {
        $uniq = $task->unique();
        $hand = $task->jobHandle();
        echo "Job {$hand}:{$uniq} had exception\n";
    }

    function taskFail($task)
    {
        $uniq = $task->unique();
        $hand = $task->jobHandle();
        echo "Job {$hand}:{$uniq} failed\n";
        $fails[] = $task->data();
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