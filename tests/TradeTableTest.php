<?php
namespace TradeCaptureTest\Model;

use TradeCaptureTest\Bootstrap;
use TradeCapture\Controller\TradeCaptureController;
use TradeCapture\Model\TradeTable;
use TradeCapture\Model\Trade;
use Zend\Db\ResultSet\ResultSet;
use PHPUnit_Framework_TestCase; 


class TradeTableTest extends PHPUnit_Framework_TestCase       
{                                                             
	protected $controller; 
	
	
		protected function setUp()
	    {   
			$serviceManager = Bootstrap::getServiceManager();
	        $this->controller = new TradeCaptureController();  
			$this->controller->setServiceLocator($serviceManager);
	    }
	
	
    public function testFetchAllReturnsAllTrades()
    {
        $resultSet        = new ResultSet();
        $mockTableGateway = $this->getMock('Zend\Db\TableGateway\TableGateway',
                                           array('select'), array(), '', false);
        $mockTableGateway->expects($this->once())
                         ->method('select')
                         ->with()
                         ->will($this->returnValue($resultSet));

        $tradeTable = new TradeTable($mockTableGateway);

        $this->assertSame($resultSet, $tradeTable->fetchAll());
    }

	public function testCanRetrieveATradeByItsId()
	{
	    $trade = new Trade();
	    $trade->exchangeArray(array('trade_id'     => 123,
	                                'effective_date' => date('2012-01-01'),
	                                'price'  => 0.89));

	    $resultSet = new ResultSet();
	    $resultSet->setArrayObjectPrototype(new Trade());
	    $resultSet->initialize(array($trade));

	    $mockTableGateway = $this->getMock('Zend\Db\TableGateway\TableGateway', array('select'), array(), '', false);
	    $mockTableGateway->expects($this->once())
	                     ->method('select')
	                     ->with(array('trade_id' => 123))
	                     ->will($this->returnValue($resultSet));

	    $tradeTable = new TradeTable($mockTableGateway);

	    $this->assertSame($trade, $tradeTable->getTrade(123));
	}

	
	public function testGetTradeTableReturnsAnInstanceOfTradeTable()
	{
	    $this->assertInstanceOf('TradeCapture\Model\TradeTable', $this->controller->getTradeTable());
	}
}