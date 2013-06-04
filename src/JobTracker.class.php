<?php
require_once 'DB.class.php';

class JobTracker {
    private $database;
    private $hostname;
    private $hostport;
    private $username;
    private $password;
    private $uniq;
    private $hand;
    private $functionName;
    
    // timing how long the job took
    private $mStart;
    private $mEnd;
    
    function __construct($uniq,$hand=null, $functionName=null)
    {
        if( !defined('JOB_TRACKER_DB_NAME') || !defined('JOB_TRACKER_DB_HOST') || !defined('JOB_TRACKER_DB_PORT') ||
            !defined('JOB_TRACKER_DB_USER') || !defined('JOB_TRACKER_DB_PASS') )
        {
             throw new Exception('You must define the following constants: JOB_TRACKER_DB_NAME, JOB_TRACKER_DB_HOST, JOB_TRACKER_DB_PORT, JOB_TRACKER_DB_USER and JOB_TRACKER_DB_PASSWORD');
        }
        
        $this->uniq = $uniq;
        $this->hand = $hand;
        $this->functionName = $functionName;
        $this->database = JOB_TRACKER_DB_NAME;//'gearman';
        $this->hostname = JOB_TRACKER_DB_HOST;//'localhost';
        $this->hostport = JOB_TRACKER_DB_PORT;//null;
        $this->username = JOB_TRACKER_DB_USER;//'root';
        $this->password = JOB_TRACKER_DB_PASS;//null;
    }
    
	/**
     * returns the JobTraker->uniq id
     */
    function getUniqId()
    {
    	return $this->uniq;    	
    }
    
    function setQueued($parent=null,$data=null,$DB=null)
    {
        $this->insert("QUEUED",$parent,$data,$DB);
    }

    function setInProgress()
    {
        $this->mStart = microtime();
        $this->update("IN_PROGRESS",null, $this->microtimeToDate($this->mStart));        
    }

    function setCompleted($msg=null)
    {
        $this->mEnd = microtime();
        $this->update("COMPLETED",$msg, null, $this->microtimeToDate($this->mEnd), $this->timeTaken($this->mStart, $this->mEnd));        
    }
    
    function setFailed($msg=null)
    {
        $this->mEnd = microtime();
        $this->update("FAILED",$msg, null, $this->microtimeToDate($this->mEnd), $this->timeTaken($this->mStart, $this->mEnd));
    }

    /**
     * Deprecated!
     * Do not call this method anymore, instead in your worker you set the final message you want recorded using either the
     * setCompleted() function or the setFailed() function.
     * @param $msg
     * @deprecated
     */
    function setMessage($msg)
    {
        $msg = addslashes(serialize($msg));
        $DB = DB::Open($this->database, $this->hostname, $this->hostport, $this->username, $this->password);
        $query = "update gearman_status_updates set message='{$msg}' where unique_key='{$this->uniq}';";
        $r = $DB->query($query, 0);
        $DB->close();
    }

    /**
     * Deprecated!
     * Do not call this method anymore, instead in your worker you set the final message you want recorded using either the
     * setCompleted() function or the setFailed() function. We no longer need a detailedLog field.
     * @param $log
     * @deprecated
     */
    function setDetailedLogText($log)
    {
        $msg = addslashes(serialize($log));
        $DB = DB::Open($this->database, $this->hostname, $this->hostport, $this->username, $this->password);
        $query = "update gearman_status_updates set log='{$msg}' where unique_key='{$this->uniq}';";
        $r = $DB->query($query, 0);
        $DB->close();
    }
    
    function delete()
    {
        $DB = DB::Open($this->database, $this->hostname, $this->hostport, $this->username, $this->password);
        $query = "delete from gearman_status_updates where unique_key='{$this->uniq}';";
        $r = $DB->query($query, 0);
        $DB->close();        
    }
    
    function getChildStatuses()
    {
        $DB = DB::Open($this->database, $this->hostname, $this->hostport, $this->username, $this->password);
        $query = "select (select started from gearman_status_updates where  unique_key='{$this->uniq}') as 'STARTED',
                         (select finished from gearman_status_updates where  unique_key='{$this->uniq}') as 'FINISHED',
                         (select duration from gearman_status_updates where  unique_key='{$this->uniq}') as 'DURATION',        
                         (select count(*) from gearman_status_updates where parent_key='{$this->uniq}') as 'CHILD_JOBS', 
                         (select count(*) from gearman_status_updates where parent_key='{$this->uniq}' AND status='QUEUED') as 'QUEUED', 
                         (select count(*) from gearman_status_updates where parent_key='{$this->uniq}' AND status='IN_PROGRESS') as 'IN_PROGRESS', 
                         (select count(*) from gearman_status_updates where parent_key='{$this->uniq}' AND status='COMPLETED') as 'COMPLETED', 
                         (select count(*) from gearman_status_updates where parent_key='{$this->uniq}' AND status='FAILED') as 'FAILED';";
        $r = $DB->query($query, 2);
        $DB->close();
        return $r;
    }
    
    private function insert($status="QUEUED",$parent=null,$data=null,$DB=null)
    {
        $data = addslashes(serialize($data));
        $message = serialize("Job Created");
        $_DB = ($DB==null) ? DB::Open($this->database, $this->hostname, $this->hostport, $this->username, $this->password) : $DB;            
        $result = $_DB->query("insert into gearman_status_updates (unique_key, job_handle, function_name, data, status, message, parent_key) values('{$this->uniq}','{$this->hand}','{$this->functionName}','{$data}','{$status}','{$message}', '{$parent}');", 0);
        if ($DB==null)
        {
            $_DB->close();              
        }
    }
    
    private function update($status,$msg=null, $startTime=null, $endTime=null, $timeTaken=null)
    {
        $DB = DB::Open($this->database, $this->hostname, $this->hostport, $this->username, $this->password);
        
        $others = "";
        if($msg!=null) { 
            $msg = addslashes(serialize($msg)); 
            $others .= ",message='{$msg}'"; 
        }
        
        if($startTime!=null) { $others .= ",started='{$startTime}'"; }
        if($endTime!=null) { $others .= ",finished='{$endTime}'"; }
        if($timeTaken!=null) { $others .= ",duration='{$timeTaken}'"; }
                
        $query = "update gearman_status_updates set status='{$status}',job_handle='{$this->hand}'{$others} where unique_key='{$this->uniq}';";
        $r = $DB->query($query, 0);
        $DB->close();              
    }
    
    private function microtimeAsFloat($micro)
    {
        list($usec, $sec) = explode(" ", $micro);
        return ((float)$usec + (float)$sec);
    }
    
    private function microtimeToDate($micro)
    {
        list($microSec, $timeStamp) = explode(" ", $micro);
        return date('c', $timeStamp);// . (date('s', $timeStamp) + $microSec);
    }
    
    private function timeTaken($start, $end)
    {
        $start = $this->microtimeAsFloat($start);
        $end = $this->microtimeAsFloat($end);
        return round(($end-$start), 4);
    }
    
    // $jobs is array of array('uniq'=>$unique,
    //                         'handle'=>$handle,
    //                         'functionName'=>$functionName,
    //                         'data'=>$data,
    //                         'parent'=>$parent)
    public static function setQueuedForJobs($jobs)
    {
        $DB = DB::Open(JOB_TRACKER_DB_NAME, JOB_TRACKER_DB_HOST, JOB_TRACKER_DB_PORT, JOB_TRACKER_DB_USER, JOB_TRACKER_DB_PASS);
        foreach($jobs as $job)
        {
            $jt = new JobTracker($job['uniq'],$job['handle'],$job['functionName']);
            $jt->setQueued($job['parent'],$job['data'],$DB);
        }
        $DB->close();
    }
}

