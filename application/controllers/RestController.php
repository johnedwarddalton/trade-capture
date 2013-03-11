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
    	//  allow actions to return json data if specified
        $contextSwitch= $this->getHelper('contextSwitch');
    	$contextSwitch->addActionContext('retrieve', 'json')->initContext();
    	$contextSwitch->addActionContext('table', 'json')->initContext();	
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
       		$this->getTradeMapper()->find($trade_id, $trade);
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
     * datasource for jquery dataTables.  Returns data equivalent to the generic query
     * (retrieve) but specifically formatted for use with dataTables
     *
     * @return array
     *
     * @access public
     */
    public function tableAction()
    {
    	$params = $this->getRequest()->getParams();
    	
    	//list of coumns to be returned
    	$columns = array(
    			'trade_id','execution_date', 'eff_date', 'end_date', 'term', 'not_curr_1', 'not_curr_2', 'inst_type',
    			'inst_subtype', 'price', 'not_amount_1', 'not_amount_2','und_asset_1', 'und_asset_2', 'opt_strike', 'opt_type',
    			'opt_curr', 'opt_premium', 'opt_start', 'opt_expiry','opt_tenor', 'opt_add_price_type_1', 'opt_add_price_1'
    	);
    	
    	$results = $this->tradeQuery($params, $columns);
    	
    	
    	$entries=array();
    	$locale = new Zend_Locale('en_GB');
    	foreach ($results['trades'] as $trade){
    		$entry = array();
    		
    		//transfer returned columns
    		foreach ($columns as $col){
    			$entry[$col] = $trade->$col;
    		}
    		
    		// calculated data
    		$opt_prem_val = (double) str_replace(',', NULL, $trade->opt_premium) / 1e6;
    		$entry['opt_prem_val'] = round($opt_prem_val, 4);
    		$entry['opt_prem_bps'] = round(10000 * $opt_prem_val / $trade->not_amount_1,1);
    		
    		$date = new Zend_Date($trade->execution_date, $locale);
    		$entry['exec_date_short'] = $date->toString("EEE H:mm");
    		$date = new Zend_Date($trade->eff_date, $locale);
    		$entry['eff_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->end_date, $locale);
    		$entry['end_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_start, $locale);
    		$entry['opt_start_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_expiry, $locale);
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
     * @param array $modifiers
     *
     */
    protected function tradeQuery(array $modifiers, array $columns)
    {
    	 	 
    	$select = $this->getTradeMapper()->getDbTable()->select();	 
    
    	// get the total number of columns without any filtering
    	$select->from('trade', array('num' => 'count(trade_id)'));
    	$this->setSearchParameters($modifiers, $select); 
    	$result = $this->getTradeMapper()->countRows($select);
    	$total_rows = $result['num'];
    	 

    	$total_filtered_rows = $total_rows;
    	//dataTables filtering
    	if (isset($modifiers['sSearch'])){
    		$searchString = $modifiers['sSearch'];
    		if ( $searchString  !== "" ){
    			$num_cols = count($columns);
    			$filter = array();
    			for ( $i=0 ; $i<$num_cols ; $i++ ){
    				$filter[] = $columns[$i]. " LIKE '%" . $searchString  . "%'";
    			}
    			$sWhere = implode(' OR ', $filter);
    			 
    			// count total number of rows with filtering
    			$select = $this->getTradeMapper()->getDbTable()->select();
    			$select->from('trade', array('num' => 'count(trade_id)'));
    			$this->setSearchParameters($modifiers, $select);
    			$select->where($sWhere);
    			$result = $this->getTradeMapper()->countRows($select);
    			$total_filtered_rows = $result['num'];    			 
    		}     
    	}
    	 
    	// reset query
    	$select = $this->getTradeMapper()->getDbTable()->select();
    	$select->from('trade',$columns);
    	$this->setSearchParameters($modifiers, $select);
    	if ( isset($searchString) && ('' !== $searchString ) ) {
    		$select->where($sWhere);
    	}
    	 
    	//dataTable Paging;
    	if ( isset( $modifiers['iDisplayStart'] ) && $modifiers['iDisplayLength'] != '-1' ){
    		$select->limit($modifiers['iDisplayLength'], $modifiers['iDisplayStart']);
    	}
    	 
    	//default sorting
    	$sOrder = 'execution_date DESC';
    	//dataTable sorting
    	if ( isset( $modifiers['iSortCol_0'] ) )
    	{
    		$sOrder = array();
    		for ( $i=0; $i<intval( $modifiers['iSortingCols'] ); $i++ ){
    			if ( $modifiers[ 'bSortable_' . intval($modifiers['iSortCol_' . $i]) ] == "true" ){
    				$sOrder[] =  $modifiers[ 'mDataProp_' . intval( $modifiers['iSortCol_'.$i] ) ] . ' ' .  $modifiers['sSortDir_' . $i] ;
    			}
    		}
    	}
    	$select->order($sOrder);
    
    	 
    	$trades = $this->getTradeMapper()->fetchSome($select);
    	 
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
     * @param array $modifiers
     * @param Zend_DB_Table_Select $select
     * 
     * @access protected
     */
    protected function setSearchParameters( array $modifiers, Zend_DB_Table_Select $select){
    	$options = $this->getConfigOptions();
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
    protected function getTradeMapper()
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
    protected function getConfigOptions(){
    	return $this->getFrontController()->getParam('bootstrap')->getApplication()->getOptions();
    }

}



