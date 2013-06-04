<?php
require_once "../config/example.config.php";
require_once '../src/BaseWorker.class.php';
require_once '../src/JobTracker.class.php';
class TextWorker extends BaseWorker{

  private $version = "1";

  public function __construct() {
      $this->setupWorker();
  }

  public function getVersion()
  {
      return $this->version;
  }
  
  public function getFunctions()
  {
      return array( 'reverse', 'allcaps' );
  }

  public function reverse($job) {
      $tracker = new JobTracker($job->unique(),$job->handle());
      $tracker->setInProgress();
      
      $data = unserialize($job->workload());
      $result = strrev($data);
      echo("{$result}\n");
      
      $tracker->setCompleted($result);      
      return $result;
  }
  
  public function allcaps( $job ) {
      $tracker = new JobTracker($job->unique(),$job->handle());
      $tracker->setInProgress();
      
      $data = unserialize($job->workload());
      $result = strtoupper($data);
      echo("{$result}\n");
      
      $tracker->setCompleted($result);      
      return $result;
  }
  
}

$worker = new TextWorker();

?>