<?php
require_once "BaseClient.class.php";
require_once "JobTracker.class.php";
class ManagerClient extends BaseClient{

  protected $gearmanClient;

  public function __construct() 
  {
      $this->setupClient();
  }
  
  public function killWorker($workerName, $version, $msg='')
  {
      $this->gearmanClient->doBackground( "{$workerName}_{$version}_kill", serialize($msg));      
  }
  
}
?>