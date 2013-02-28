<?php


class TransactionTest extends PHPUnit_Framework_TestCase
{
    public function testTransactionCanBeInitialised()
    {
        $transaction = new Application_Model_Transaction();
        $this->assertInstanceOf('Application_Model_Transaction', $transaction);
    }

}