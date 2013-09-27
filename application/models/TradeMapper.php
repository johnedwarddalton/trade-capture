<?php
// application/models/TradeMapper.php

/**
 * Class to to handle standard database functions
 * 
 * @package trade_capture
 * 
 * @author John Dalton
 *
 */
class Application_Model_TradeMapper
{
	protected $_dbTable;
	protected $_dbArchive;
	
	/**
	 * setter for _dbTable
	 * 
	 * @param Zend_DB_Table_Abstract $dbTable
	 * 
	 * @access public
	 */
	public function setDbTable($dbTable)
	{
		if (is_string ($dbTable)) {
			$dbTable = new $dbTable();
		}
	
		if (!$dbTable instanceof Zend_Db_Table_Abstract){
			throw new Exception('Invalid table data gateway provided');
		}
	
		$this->_dbTable = $dbTable;
		return $this;
	}
	
	/**
	 * Getter for _dbTable
	 * 
	 * @access public
	 *
	 */
	public function getDbTable()
	{
		if (null === $this->_dbTable) {
			$this->setDbTable('Application_Model_DbTable_Trade');
		}
		return $this->_dbTable;
	}
	
	/**
	 * setter for _dbArchive
	 *
	 * @param Zend_DB_Table_Abstract $dbArchive
	 *
	 * @access public
	 */
	public function setDbArchive($dbArchive)
	{
		if (is_string ($dbArchive)) {
			$dbArchive = new $dbArchive();
		}
	
		if (!$dbArchive instanceof Zend_Db_Table_Abstract){
			throw new Exception('Invalid table data gateway provided');
		}
	
		$this->_dbArchive = $dbArchive;
		return $this;
	}
	
	/**
	 * Getter for _dbArchive
	 *
	 * @access public
	 *
	 */
	public function getDbArchive()
	{
		if (null === $this->_dbArchive) {
			$this->setDbArchive('Application_Model_DbTable_Archive');
		}
		return $this->_dbArchive;
	}
	
	/**
	 * saves a single trade instance to the database
	 * 
	 * @param array 			$data
	 */
	public function save(array $data)
	{
		$trade_id = $data['trade_id'];
		
		// only add to database if the trade_id doesn't already exist
		if (!$this->exists($trade_id)){
			$this->getDbTable()->insert($data);
		}
	}
	
	/**
	 * function to find a single trade and populate trade object.
	 * 
	 * @param integer $id       unique key
	 * @param Application_Model_Trade $trade
	 * 
	 */
	public function find($id, Application_Model_Trade $trade)
	{
		$trade_id  = (int) $id;
        $rowset = $this->getDbTable()->find($trade_id);
        $row = $rowset->current();
        if ($row){
        	$trade->exchangeArray($row);
        }
	}
	
	/**
	 * function to see if a  trade exists
	 *
	 * @param integer $id       unique key
	 *
	 * @return boolean
	 */
	public function exists($id)
	{
		$trade_id  = (int) $id;
		$rowset = $this->getDbTable()->find($trade_id);
		$row = $rowset->current();
		if ($row){
			return true;
		}
		else{
			return false;
		}
	}
	
	/**
	 * deletes a single trade 
	 * 
	 * @param integer $id        the trade_id of the object to be deleted
	 * 
	 * @access public
	 */
	public function delete($id)
	{
		$trade_id  = (int) $id;
		$where = $this->getDbTable()->getAdapter()->quoteInto('trade_id = ?', $trade_id);
		$this->getDbTable()->delete($where);
	}
	
	/**
	 * returns a filtered set of results.  Default ordering
	 * is descending by execution date.
	 * 
	 * @param Zend_Db_Table_Select $select
	 * 
	 * @access protected
	 */
	public function fetchSome(Zend_Db_Table_Select $select)
	{
		$rowset = $this->getDbTable()->fetchAll( $select);
		
		$trades = array();
		foreach ($rowset as $row){
			$trade = new Application_Model_Trade();
			$trade->exchangeArray($row);
			$trades[] = $trade;
		}
		return $trades; 

	}	
	
	/**
	 *  returns the total number of rows that would be returned for the given SELECT statement
	 * 
	 * @param Zend_Db_Table_Select $select
	 * @return integer
	 */
	public function countRows(Zend_Db_Table_Select $select)
	{
		$rowset = $this->getDbTable()->fetchAll( $select);
		return $rowset[0];
	}
	
	/**
     * extracts the query parameters and returns the relevant data
     * from the database
     *
     * @param array $columns         column names to be fetched
     *
     */
    public function tradeQuery(array $columns, array $modifiers, array $options)
    {
    	

    	
    	$select = $this->getDbTable()->select();	

    	
    
    	// get the total number of columns without any filtering
    	$select->from('trade', array('num' => 'count(trade_id)'));
    	$this->_setSearchParameters ($select, $modifiers, $options); 
    	$result = $this->countRows($select);
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
    			$select = $this->getDbTable()->select();
    			$select->from('trade', array('num' => 'count(trade_id)'));
    			$this->_setSearchParameters($select, $modifiers, $options);
    			$select->where($str_where);
    			$result = $this->countRows($select);
    			$total_filtered_rows = $result['num'];    			 
    		}     
    	}
    	 
    	// reset query
    	$select = $this->getDbTable()->select();
    	$select->from('trade',$columns);
    	$this->_setSearchParameters ( $select, $modifiers, $options);
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
    
    	 
    	$trades = $this->fetchSome($select);

    	$daily_vol = 0;
    	if (isset($modifiers['bVol'])){
    		$select = $this->getDbTable()->select();
    		$select->from('trade', 'sum(not_amount_1)');
    		$this->_setSearchParameters ( $select, $modifiers, $options, true);
    		$rowset = $this->getDbTable()->fetchAll( $select);
    		$daily_vol = $rowset[0]['sum(not_amount_1)'];
    	}
    	
    	if (isset($modifiers['sEcho'])){
    		$echo = intval($modifiers['sEcho']);
    	}
    	else{
    		$echo = 0;
    	}
    
    	return array('echo' =>$echo, 'total_rows' => $total_rows, 'filtered_rows' => $total_filtered_rows, 'trades' => $trades, 'daily_vol' => $daily_vol);
    }
    

    public function volumeHistory(array $modifiers, array $options)
    {
    	 
    	$select = $this->getDbArchive()->select();
    	$select->from('trade_archive',array('date(execution_date)', 'sum(not_amount_1)'));
    	$select->group('DATE(execution_date)');
    	// avoid weekends;
    	$select->where('dayofweek(execution_date) <> 1');
    	$select->where('dayofweek(execution_date) <> 7');
    	$this->_setSearchParameters ( $select, $modifiers, $options);

    	//default sorting
    	$str_order = 'DATE(execution_date) ASC';
    	$select->order($str_order);
    	$results = $this->getDbArchive()->fetchAll($select);

    
    	return $results;
    }
    
    
    /**
     * 
     * @param Zend_DB_Table_Select $select
     * 
     * @access protected
     */
    protected function _setSearchParameters( Zend_DB_Table_Select $select, array $modifiers, array $options, $today = false){
		
    	//  decide which types of trade we are interested in
    	if (isset($modifiers['trans'])){
    		switch (strtolower($modifiers['trans']) ){
    			case 'term' :
    				$select->where('trans_type = ?', 'Termination');
    				break;
    			case 'all':
    				// do nothing;
    				break;
    			default:
    				// only get data for trades
    				$select->where('trans_type = ?', 'Trade');
    				break;
    		}
    	}
    	else {
    		$select->where('trans_type = ?', 'Trade');
    	}
    	
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
    	
    	//term filters
    	if (isset($modifiers['term_min'])){	
    		$select->where('term >= ?', $modifiers['term_min']);
    	}
    	if (isset($modifiers['term_max'])){
    		$select->where('term < ?', $modifiers['term_max']);
    	}
    	
    	
    	//specify either number of trades, from-to dates or since a certain number of hours
    	// or if "today" flag is set, only today's trades
    	if ($today){
    		$select->where('execution_date >= DATE(NOW())');
    	}
    	else{
    		//specify the day if the week
    		if (isset($modifiers['dow'])){
    			$select->where('DAYOFWEEK(DATE(execution_date)) = ?', $modifiers['dow'] );
    		}
    		
    		if (isset($modifiers['last'])){
    			$select->limit($modifiers['last'], 0);
    		}
    		elseif (isset( $modifiers['from'])){
    			$select->where('DATE(execution_date) >= DATE_SUB(DATE(NOW()), INTERVAL ? DAY)', $modifiers['from'] );
    			if (isset ($modifiers['to'])){
    				$select->where('DATE(execution_date) <= DATE_SUB(DATE(NOW()), INTERVAL ? DAY)', $modifiers['to'] );
    			}
    		}
    		elseif (isset( $modifiers['begin'])){
    			$select->where('execution_date >= ?', $modifiers['begin'] );
    			if (isset ($modifiers['end'])){
    				$select->where('execution_date < ?', $modifiers['end'] );
    			}
    		}
    		else {
    			if (isset($modifiers['since'])){
    				$since = min( $options['rest']['maximum_since'], $modifiers['since']);
    			}
    			else{
    				$since = $options['rest']['default_since'];
    			}
    			$select->where('execution_date >=  DATE_SUB(utc_timestamp(), INTERVAL ? HOUR)', $since);
    		}
    	}    
    }	
}

