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
	public function fetchSome(Zend_Db_Table_Select $select, $order = 'execution_date DESC')
	{
		$rowset = $this->getDbTable()->fetchAll( $select->order($order));
		$trades = array();
		foreach ($rowset as $row){
			$trade = new Application_Model_Trade();
			$trade->exchangeArray($row);
			$trades[] = $trade;
		}
		return $trades;
	}	
	
}

