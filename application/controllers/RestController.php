<?php
//  application/controllers/RestController.php


/**
 * REST controller to give remote access to the data
 *
 * @author John Dalton
 *
 * @package Trade_Capture
 *
 */
class RestController extends Zend_Controller_Action
{

    /**
     * instance of class which is used to communicate with database
     *
     * @var TradeMapper
     *
     */
    protected $_tradeMapper = null;

    /** setup function for controller.
     *  
     * @access public
     */
    public function init()
    {	
    	// check http call using basic authentication
    	$this->_authorize();
    	
    	//  allow actions to return json data if specified
        $contextSwitch= $this->getHelper('contextSwitch');
    	$contextSwitch->addActionContext('retrieve', 'json')->initContext();
    	$contextSwitch->addActionContext('table', 'json')->initContext();	
    }
    
    
    /**
     *  authenticates request
     *  
     *  @access protected
     */
    protected function _authorize(){
    	$config = array(
    			'accept_schemes' => 'basic',
    			'realm'          => 'trade-capture',
    	);
    	
    	$adapter = new Zend_Auth_Adapter_Http($config);
    	
    	$options = $this->_getConfigOptions();
    	$basic_resolver_file = $options['auth']['file']['basic'];
    	$basic_resolver = new Zend_Auth_Adapter_Http_Resolver_File();
    	$basic_resolver->setFile($basic_resolver_file);
    	
    	$request = $this->getRequest();
    	$response = $this->getResponse();
    	$adapter->setBasicResolver($basic_resolver);
    	$adapter->setRequest($request);
    	$adapter->setResponse($response);
    	
    	$result = $adapter->authenticate();
    	
    	if ( !$result->isValid()){
    		$request->setActionName('unauth');
    	}
    			
    }
	
    
    /**
     * sets up the response for a unauthorised request
     * 
     * @access public
     */
    public function unauthAction()
    {
    	$this->getResponse()->setHttpResponseCode(401);
    	$this->_helper->json('Request unauthorised');
    }
    
    /**
     * returns data for a single trade if id is supplied.
 	 *
     * @return array
     * 
     * @access public
     */
    public function retrieveAction()
    {
    	
		$params = $this->getRequest()->getParams();
       
		if ( isset($params['id']) ){			//single trade request
       		$trade_id = (int) $params['id'];
       		$trade = new Application_Model_Trade();
       		$this->_getTradeMapper()->find($trade_id, $trade);
       		$entry = $trade->toArray();
       		
       		// set null values to blanks
       		foreach ($entry as $key=>$value){
       		if (!$value){
       			$entry[$key] = '';
       		}
       	}
       $this->view->entry = $entry;   
    	}
  }


    
 

    /**
     * datasource for jquery dataTables.  Returns data 
     * specifically formatted for use with dataTables
     *
     * @return array
     *
     * @access public
     */
    public function tableAction()
    {

    	$params = $this->getRequest()->getParams();
    	
    	//list of columns to be returned
    	$columns = array(
    			'unix_timestamp(creation_date)','trade_id','cleared', 'execution_date', 'eff_date', 'end_date', 'term', 'not_curr_1', 'not_curr_2', 'inst_type',
    			'inst_subtype', 'price', 'not_amount_1', 'not_amt_capped', 'not_amount_2','und_asset_1', 'und_asset_2', 'opt_strike',
    			 'opt_type','opt_curr', 'opt_premium', 'opt_start', 'opt_expiry','opt_tenor', 'opt_add_price_type_1', 'opt_add_price_1', 
    	);
    	
    	$results = $this->_tradeQuery ($columns);
    	
    	
    	$entries=array();
    	foreach ($results['trades'] as $trade){
    		$entry = array();
    		
    		//transfer returned columns
    		foreach ($columns as $col){
    			$entry[$col] = $trade->$col;
    		}
    		
    		// calculated data
    		$opt_prem_val = (double) str_replace(',', NULL, $trade->opt_premium) / 1e6;
    		$entry['opt_prem_val'] = round($opt_prem_val, 4);
    		
    		if ($trade->not_amount_1){       // avoid division by zero
    			$entry['opt_prem_bps'] = round(10000 * $opt_prem_val / $trade->not_amount_1,1);
    		}
    		
    		$date = new Zend_Date($trade->execution_date);
    		$entry['exec_date_short'] = $date->toString("EEE H:mm");
    		$date = new Zend_Date($trade->eff_date);
    		$entry['eff_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->end_date);
    		$entry['end_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_start);
    		$entry['opt_start_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_expiry);
    		$entry['opt_expiry_short'] = $date->toString("MMM-YY");
    		$entries[] = $entry;
    	}
    	$this->view->aaData = $entries;
    	$this->view->sEcho = $results['echo'];
    	$this->view->iTotalRecords = $results['total_rows'];
    	$this->view->iTotalDisplayRecords = $results['filtered_rows'];
    }
    
    /**
     * extracts the query parameters and returns the relevant data
     * from the database
     *
     * @param array $columns         column names to be fetched
     *
     */
    protected function _tradeQuery(array $columns)
    {
    	$modifiers = $this->getRequest()->getParams();
    	$select = $this->_getTradeMapper()->getDbTable()->select();	 
    
    	// get the total number of columns without any filtering
    	$select->from('trade', array('num' => 'count(trade_id)'));
    	$this->_setSearchParameters ($select); 
    	$result = $this->_getTradeMapper()->countRows($select);
    	$total_rows = $result['num'];
    	 

    	$total_filtered_rows = $total_rows;
    	//dataTables filtering
    	if (isset($modifiers['sSearch'])){
    		$search_string = $modifiers['sSearch'];
    		if ( $search_string  !== "" ){
    			$num_cols = count($columns);
    			$filter = array();
    			for ( $i=0 ; $i<$num_cols ; $i++ ){
    				$filter[] = $columns[$i]. " LIKE '%" . $search_string  . "%'";
    			}
    			$str_where = implode(' OR ', $filter);
    			 
    			// count total number of rows with filtering
    			$select = $this->_getTradeMapper()->getDbTable()->select();
    			$select->from('trade', array('num' => 'count(trade_id)'));
    			$this->_setSearchParameters($select);
    			$select->where($str_where);
    			$result = $this->_getTradeMapper()->countRows($select);
    			$total_filtered_rows = $result['num'];    			 
    		}     
    	}
    	 
    	// reset query
    	$select = $this->_getTradeMapper()->getDbTable()->select();
    	$select->from('trade',$columns);
    	$this->_setSearchParameters ( $select);
    	if ( isset($search_string) && ('' !== $search_string ) ) {
    		$select->where($str_where);
    	}
    	 
    	//dataTable Paging;
    	if ( isset( $modifiers['iDisplayStart'] ) && $modifiers['iDisplayLength'] != '-1' ){
    		$select->limit($modifiers['iDisplayLength'], $modifiers['iDisplayStart']);
    	}
    	 
    	//default sorting
    	$str_order = 'execution_date DESC';
    	//dataTable sorting
    	if ( isset( $modifiers['iSortCol_0'] ) )
    	{
    		$str_order = array();
    		for ( $i=0; $i<intval( $modifiers['iSortingCols'] ); $i++ ){
    			if ( $modifiers[ 'bSortable_' . intval($modifiers['iSortCol_' . $i]) ] == "true" ){
    				$str_order[] =  $modifiers[ 'mDataProp_' . intval( $modifiers['iSortCol_'.$i] ) ] . ' ' .  $modifiers['sSortDir_' . $i] ;
    			}
    		}
    	}
    	$select->order($str_order);
    
    	 
    	$trades = $this->_getTradeMapper()->fetchSome($select);
    	 
    	if (isset($modifiers['sEcho'])){
    		$echo = intval($modifiers['sEcho']);
    	}
    	else{
    		$echo = 0;
    	}
    
    	return array('echo' =>$echo, 'total_rows' => $total_rows, 'filtered_rows' => $total_filtered_rows, 'trades' => $trades);
    }
    
    
    /**
     * 
     * @param Zend_DB_Table_Select $select
     * 
     * @access protected
     */
    protected function _setSearchParameters( Zend_DB_Table_Select $select){
    	
    	$modifiers = $this->getRequest()->getParams();
    	$options = $this->_getConfigOptions();
    	if (isset($modifiers['currency'])){
    		$select->where('not_curr_1 = ?', $modifiers['currency']);
    	}
    	if (isset($modifiers['type'])){
    		if ( 'AllOptions' === $modifiers['type']){
    			$select->where('inst_type IN (?) ', array('Option', 'CapFloor'));
    		}
    		else{
    			$select->where('inst_type = ?', $modifiers['type']);
    		}
    	}
    	if (isset($modifiers['subtype'])){
    		
    		$select->where('inst_subtype = ?', $modifiers['subtype']);
    	}
    	if (isset($modifiers['minimum'])){
    		$select->where('not_amount_1 >= ?', $modifiers['minimum']);
    	}
    	if (isset($modifiers['since'])){
    		$since = min( $options['rest']['maximum_since'], $modifiers['since']);
    	}
    	else{
    		$since = $options['rest']['default_since'];
    	}
    	$select->where('execution_date >=  DATE_SUB(utc_timestamp(), INTERVAL ? HOUR)', $since);
    }
    
    /**
     * instantiates _tradeMapper if it does not already exist and returns it
     *
     * @return Application_Model_TradeMapper
     *
     * @access protected
     *
     */
    protected function _getTradeMapper()
    {
    	if (!$this->_tradeMapper){
    		$this->_tradeMapper = new Application_Model_TradeMapper();
    	}
    	return $this->_tradeMapper;
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

}



