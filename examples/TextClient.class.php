<?php
require_once "../config/example.config.php";
require_once "../src/BaseClient.class.php";
require_once "../src/JobTracker.class.php";
class TextClient extends BaseClient{

  protected $gearmanClient;

  public function __construct() 
  {
      $this->setupClient();
  }

  public function doReverse( $msg='' ) {
      $uniq = uniqid();
      
      $jt = new JobTracker($uniq);
      $jt->setQueued();    
      
      echo $this->gearmanClient->doBackground( 'TextWorker_1_reverse', serialize($msg), $uniq);
  }
  public function doAllCaps( $msg='' ) {
      $uniq = uniqid();
      
      $jt = new JobTracker($uniq);
      $jt->setQueued();    

      echo $this->gearmanClient->doBackground( 'TextWorker_1_allcaps', serialize($msg), $uniq);
  }
  
  public function kill($msg='')
  {
      $this->gearmanClient->doBackground( 'TextWorker_1_kill', serialize($msg));      
  }
  
  public function listFunctions()
  {
      $this->gearmanClient->do( 'TextWorker_1_functions', "");      
  }
  
}

$client = new TextClient();
//$client->listFunctions();
$client->doReverse('Wibble!');
//$client->doAllCaps('lowercase');
?>