<?php
// application/models/TradeArchiver.php

/**
 * Class to archive database
 * 
 * @package trade_capture
 * 
 * @author John Dalton
 *
 */
class Application_Model_TradeArchiver
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
	 * move data from the main database to the archive
	 * 
	 * @param integer $num_of_days   age limit in days
	 * 
	 * @return integer          number of records archived  
	 * 
	 * @access public
	 */
	public function archive($num_of_days){
		$num_of_days = intval($num_of_days);
		
		
		//  Object-oriented MVC approach  
		//get the relevant data from the trade table
		/*
		$select = $this->getDbTable()->select();
		$select->where('execution_date <  DATE_SUB(NOW(), INTERVAL ? DAY)', $num_of_days);
		$rowset = $this->getDbTable()->fetchAll($select);
		
		//insert into the archive and delete from trade table
		$archive = $this->getDbArchive();
		foreach($rowset as $row){
			$archive->insert($row->toArray());
			$row->delete();
		}
		
		return count($rowset);	
		*/
		
		// More efficient but less object-oriented approach - database dependent
		$adapter = $this->getDbArchive()->getAdapter();
		$stmt = $adapter->query('INSERT IGNORE INTO trade_archive SELECT * FROM trade WHERE 
				execution_date < DATE_SUB(NOW(), INTERVAL ? DAY)', $num_of_days);
		$adapter->query('DELETE FROM trade WHERE 
				execution_date < DATE_SUB(NOW(), INTERVAL ? DAY)', $num_of_days);
	   
		return $stmt->rowCount();
		
		
	}
	

	
}

