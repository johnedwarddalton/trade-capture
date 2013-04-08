<?php
//   application/controllers/CaptureController.php


/**
 * 
 * Controller class for trade capture application
 * 
 * @author John Dalton
 * 
 * @package Trade_Capture
 *
 */
class CaptureController extends Zend_Controller_Action
{
	
	/**
	 * instance of class which is used to communicate with database 
	 * 
	 * @var TradeMapper
	 */
	protected $_tradeMapper = NULL;
	
	
	/**
	 * sets up the controller
	 * 
	 * @access public
	 */
    public function init()
    {
    	    	
    	//turn off views and rendering
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender(TRUE);
    	
		// redirect action if called via http
    	if (!Zend_Registry::get('cli')){
    		$this->getRequest()->setActionName('http');	
    	}
    }

    /**
     * sets the behaviour if called from http.
     * Currently returns a 404 error
     * 
     * @access public
     */
    public function httpAction()
    {
    	$response = $this->getResponse();
    	$response->setHttpResponseCode(404);
    }
    
    /**
     * default action
     * 
     * @access public
     */
    public function indexAction()
    {

    }

	/**
	 *   loops the download - we need more frequent updates than cron can offer
	 *   
	 *   @access public
	 */
    public function loopAction()
    {
	
    	$options = $this->_getConfigOptions();
    	$interval = $options['loop']['interval'];
    	$timeout = $options['loop']['timeout'];
    	
    	$start = time();
    	$response = $this->getResponse();
    	
    	while (time() < $start + $timeout){
    		$this->downloadAction();
    		if ($this->_getVerbose()){
    			$response->sendResponse();
    			$response->clearBody();	
    		}
    		sleep($interval);
    	}
    }
    
    /**
     * reads the RSS data and, if there is any new data, saves to the database
     * 
     * @access public
     */
    public function downloadAction()
    {	

    	// read the RSS feed and get a list of transactions
    	$transactions = $this->_processFeedData ($this->_getFeedData());
    	
    	//process each indivual transaction
    	foreach ($transactions as $transaction){
    		$this->_updateDatabase($transaction);
    	}
    }
    
    /**
     * reads the data from a CSV file and saves to database
     * 
     * @access public
     */
    public function uploadAction()
    {
    	// read the File feed and process each transaction
    	$this->_processFileData ();	 
    }

    /**
     * archives the data in order to keep main query table small
     * 
     * @access public
     */
    public function archiveAction()
    {
    	$options = $this->_getConfigOptions();
    	$archive_days = $options['archive']['days'];
 
    	$archiver = new Application_Model_TradeArchiver();
    	$num_archived = $archiver->archive($archive_days);
    	
    	if ($this->_getVerbose()){
    		$this->getResponse()
    		->appendBody($num_archived . ' trades archived' . PHP_EOL);
    	}
    }
    


    /**
     * reads the RSS feeds and extracts the 'description' field from each one.
     * Uses caching and http conditional GET to ensure that the feed is only
     * read if it has been updated
     * 
     * @return array 
     */
    protected function _getFeedData()
    {
    
    	//Get the  feed url from the configuration options
    	$options = $this->_getConfigOptions();
    	$feed_url = $options['feed']['url'];
    	$cache_dir = $options['cache']['directory'];
    	
    	// set cache - this allows us to check whether the feed has been updated

    	$cache = Zend_Cache::factory('Core','File', array('lifetime' => null),
    			array('cache_dir' => $cache_dir));
    	Zend_Feed_Reader::setCache($cache); 
    
    	// set Reader properties to allow Conditional GET Requests
    	Zend_Feed_Reader::useHttpConditionalGet();
    
    	// disable SSL peer  verification as it can cause problems from some isp's
    	$options = array ('ssl' => array ('verify_peer' => false, 'allow_self_signed' => true));
    	$adapter = new Zend_Http_Client_Adapter_Socket();
    	$adapter->setStreamContext( $options);
    	Zend_Feed_Reader::getHttpClient()->setAdapter($adapter);    		
    
    	// interrogate the RSS feed
    	try {
    		$rss_data = Zend_Feed_Reader::import($feed_url);
    	}
    	catch (Zend_Feed_Exception $e) {
    		// feed import failed
    		Zend_Registry::get('logger')->
    			    log('Exception importing feed: ' . $e->getMessage(), Zend_Log::WARN); 
    	}
    	catch (Zend_Http_Client_Exception $e) {
    		Zend_Registry::get('logger')->
    			    log('Error with URL: ' . $e->getMessage(), Zend_Log::WARN); 
    	}
    	catch (Exception $e) {
  		Zend_Registry::get('logger')->
    			    log('Unknown error when reading feed: ' . $e->getMessage(), Zend_Log::WARN); 
		}
    	
		$entries = array();
		
    	// response status will be 200 if new data, 304 if not modified
    	$last_response = Zend_Feed_Reader::getHttpClient()->getLastResponse();
   		
    	if ($last_response){
    		$response_status = $last_response->getStatus();
    	
    	
    		// Only process if new data
    		if (200 === $response_status){
    			foreach ($rss_data as $item){
    				$entry['description']=$item->getDescription();
    				$entries[]=$entry;
    			}
    			if ($this->_getVerbose()){
    				$this->getResponse()
    					->appendBody(new Zend_Date() . ': ' . count($entries) . ' new entries downloaded from rss feed' . PHP_EOL);
    			}	
    		} 
    		else{
    			if ($this->_getVerbose()){
    				$this->getResponse()->appendBody(new Zend_Date() . ': ' . 'No new data found' . PHP_EOL);
    			}
    		}
    	}
	
    	return $entries;
    }
    
    /**
     * reads a csv file, instead of the live feed.
     *
     * @return array
     * 
     * @access protected
     */
    protected function _processFileData()
    {
    
    	//Get the datafile name from the configuration options
    	$options = $this->_getConfigOptions();
    	$params = $this->getRequest()->getParams();
    	$data_dir = $options['data']['dir'];
    		if (isset ($params['file'])){
    			$data_file = $data_dir . $params['file'];
    		}
    		else {
    			$data_file = $data_dir . $options['data']['file'];
    		}
    	
    	$file_handle = fopen($data_file, "r");
    	$field_names = fgetcsv($file_handle);  //header row
		while ($entry = fgetcsv($file_handle) ) {
    		$feed_data = array_combine($field_names, $entry);
    		
    		if ($feed_data){
    			$feed_data['depository'] = 'DTCC';
    			$transaction = new Application_Model_Transaction();
    			$transaction->populateFromDescriptionData($feed_data);
    			$this->_updateDatabase($transaction);
    		}	
		}
		fclose($file_handle);
    }

    
    /**
     * processes each of the RSS feed entries and converts them
     * to transaction objects
     * 
     * @param array $entries
     * @return multitype:Application_Model_Transaction
     * 
     * @access protected
     */
    protected function _processFeedData( $entries)
    {
    	if (count($entries)){
    		$options = $this->_getConfigOptions();
    		$header_file = $options['data']['dir'] . $options['data']['header'];
    		//read the data fields from file provided by DTCC  http:/dtcc.com
    		$header = fopen($header_file, 'r');
    		$field_names=fgetcsv($header);
    		fclose($header);  		
    	}
    	
    	//process the "description" field from each rss "item"
    	$transactions = array();
    	foreach ($entries as $entry){
    		// 'description' field is a CSV string so parse it 
    		$parsed_data=str_getcsv($entry['description']);
    		if ( count($field_names) === count ($parsed_data)){
    			$feed_data = array_combine($field_names, $parsed_data);
    			
    			//set the depository name here
    			$feed_data['depository'] = 'DTCC';

    			$transaction = new Application_Model_Transaction();
    			$transaction->populateFromDescriptionData($feed_data);		
    			$transactions[]=$transaction;
    		}
    		else{
    			Zend_Registry::get('logger')->
    			    log('Feed data length does not match. Specification may have changed', Zend_Log::WARN);
    		}
    		
    	}    
    	return $transactions;
    }
    
  
    /**
     * saves the data from a transaction instance to the database
     * when appropriate conditions are met
     * 
     * @param Application_Model_Transaction $transaction
     * 
     * @access protected
     */
    protected function _updateDatabase(Application_Model_Transaction $transaction){
    	$trade_data = $transaction -> getTradeData();
    	switch ($transaction->getAction()) {
    		case 'NEW':
    			$this->_saveIfTrade($trade_data);
    			break;
    			
    		case 'CANCEL' :
    			$this->_getTradeMapper()->delete($trade_data['trade_id']);
    			break;
    	
    		case 'CORRECT' :
    			$this->_getTradeMapper()->delete($trade_data['trade_id']);
    			$this->_saveIfTrade($trade_data);
    			break;
    		
    		default:
    			break;
    	
    	}
    }
    
    /**
     * 
     * checks to see if the transaction is a new trade and, if so, saves to the database
     * 
     * @param Transaction $transaction
     * 
     * @access protected
     */
    protected function _saveIfTrade($data){
    	switch (strtolower($data['trans_type']) ){
    		case 'trade' :
    
    			$this->_getTradeMapper()->save($data);
    			break;
    		case 'amendment':
    		case 'novation':
    		case 'termination':
    		default:
    			// do nothing
    			break;
    	}
    }
    
    
	/**
	 * helper function which returns the initial options
	 * 
	 * @return array
	 * 
	 * @access protected
	 */
    protected function _getConfigOptions(){
    	return $this->getFrontController()->getParam('bootstrap')->getApplication()->getOptions();
    }
    
    /**
     * instantiates _tradeMapper if it does not already exist and returns it
     * 
     * @return Application_Model_TradeMapper
     * 
     * @access protected
     */
    protected function _getTradeMapper(){
    	if (!$this->_tradeMapper){
    		$this->_tradeMapper = new Application_Model_TradeMapper();
    	}
    	return $this->_tradeMapper;
    }
    
    /**
     * returns the value of the 'verbose' flag
     *
     * @return boolean
     *
     * @access protected
     */
    protected function _getVerbose(){
    	return $this->getRequest()->getParam('verbose', false);
    }
    
    
}

