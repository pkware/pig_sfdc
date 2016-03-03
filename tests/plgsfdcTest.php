<?php
define('_JEXEC', "Testing");
/**
 *	First, stub out the parent class. I do this because I'm not interested in anything
 *	it does, for the purposes of this test. This test isonly concerned with the code
 *	specific to the plugin. Code outside the plugin is outside the scope of this test,
 *	and should be tested at the integration, not the unit, level.
 */
class JPlugin
{
	public function __construct(&$subject, $config = array())
	{
		$this->params = new Fetcher;
	}
}
/**
 *	Created as a stub class simply to mimic the behavior of the sole member of the
 *	parent class that is being called by the tested code. For the purposes of the
 *	test doubles, all we need do is mimic the results of the behavior, not the
 *	behavior itself. That should be tested at the integration level.
 */
class Fetcher
{
	public $map;
	
	public function get($term)
	{
		if ($term == 'context') {
			return "fred.view, \r\nsam.view";
		} else if ($term == 'field_mappings') {
			return $this->map;
		} else {
			return $term;
		}
	}
}

require_once "sfdc.php";
/**
 *	Test the plugin
 *
 *	NOTE: This test is rather fragile, due to the test-hostile nature of PHP. It
 *	depends upon implementation details (the name of the method that sends in
 *	the data, as well as the data variables sent in by the form). That means this
 *	test needs to be updated whenever those details change or else false negatives
 *	may occur.
 */
class plgsfdcTest extends \PHPUnit_Framework_TestCase
{
	/**
	 *	Test setup method. Is a little more complicated than usual because of the
	 *	untestable nature of PHP. The code under test calls cURL, which cannot be
	 *	effectively mocked/stubbed. So this instantiates a mock of the class being
	 *	tested, with all the "normal" methods of the class available, except the
	 *	method that sets up and calls cURL. By masking that method with a test double
	 *	in this manner, we get to test the class method(s) without issuing the web
	 *	request from cURL.
	 */
	public function setup()
	{
		$this->plugin = $this->getMockBuilder('PlgContentSfdc')
			->setConstructorArgs(array(new Fetcher))
            ->setMethods(array('sendData'))
            ->getMock();
        
        $this->plugin->params->map = $this->_csmap();
     }
	/**
	 *	Sets the a bad context, which ensures the mocked method should not be called.
	 */
	public function testParamsNotSentBecauseBadContext()
	{
		$this->plugin->expects($this->never())
     		->method('sendData')
     		->with("endpoint", $this->_expectedFormVariables())
     		->will($this->returnValue(200));

        $this->assertTrue($this->plugin->onContentBeforeSave(
        		'context',
        		$this->_dataTable(),
        		true
        ));
	}
	/**
	 *	Sets the conditions for the sendData test double to be called the correct
	 *	number of times with the correct arguments, and return with the HTTP code
	 *	for success: 200 (integer).
	 */
	public function testParamsSentWithDefaultSfdc()
	{
		$this->plugin->expects($this->once())
     		->method('sendData')
     		->with("endpoint", $this->_expectedFormVariablesDefaultSfdc())
     		->will($this->returnValue(200));

        $this->assertTrue($this->plugin->onContentBeforeSave(
        		'fred.view',
        		$this->_dataTableWithNullSfdcCode(),
        		true
        ));
	}
	/**
	 *	Sets the conditions for the sendData test double to be called the correct
	 *	number of times with the correct arguments, and return with the HTTP code
	 *	for success: 200 (integer).
	 */
	public function testParamsSentWithGoodReturn()
	{
		$this->plugin->expects($this->once())
     		->method('sendData')
     		->with("endpoint", $this->_expectedFormVariables())
     		->will($this->returnValue(200));

        $this->assertTrue($this->plugin->onContentBeforeSave(
        		'fred.view',
        		$this->_dataTable(),
        		true
        ));
	}
	/**
	 *	Sets the conditions for the sendData test double to be called the correct
	 *	number of times with the correct arguments, and return with the HTTP code
	 *	for success: 200 (integer).
	 */
	public function testCustServiceParamsSentWithGoodReturn()
	{
		$this->plugin->expects($this->once())
     		->method('sendData')
     		->with("endpoint", $this->_expectedCustFormVariables())
     		->will($this->returnValue(200));

        $this->assertTrue($this->plugin->onContentBeforeSave(
        		'fred.view',
        		$this->_dataTableCust(),
        		true
        ));
	}
	/**
	 *	Sets the conditions for the sendData test double to be called the correct
	 *	number of times with the correct arguments, and return with the HTTP code
	 *	for success: 200 (integer).
	 */
	public function testParamsSentWithTooLomgRecordtype()
	{
		$this->plugin->expects($this->once())
     		->method('sendData')
     		->with("endpoint", $this->_expectedFormVariablesShortened())
     		->will($this->returnValue(200));

        $this->assertTrue($this->plugin->onContentBeforeSave(
        		'fred.view',
        		$this->_dataTableTooLong(),
        		true
        ));
	}
	/**
	 *	Sets the conditions for the sendData test double to be called the correct
	 *	number of times with the correct arguments, but return with an HTTP code
	 *	for failure: 404 (integer) -- meaning "Page Not Found."
	 */
	public function testParamsSentWithErrorReturn()
	{
		$this->plugin->expects($this->once())
     		->method('sendData')
     		->with("endpoint", $this->_expectedFormVariables())
     		->will($this->returnValue(404));

        $this->assertFalse($this->plugin->onContentBeforeSave(
        		'sam.view',
        		$this->_dataTable(),
        		true
        ));
	}
	/**
	 *	Builds a test double for the data from the request form.
	 */
	private function _dataTable()
	{
		$table = new stdClass;
		$table->name = "name";
		$table->sfdc_code = "11223344";
		$table->email = "email";
		$table->phone = "phone";
		$table->company = "company";
		$table->subject = "subject";
		$table->description = "description";
		
		return $table;
	}
	/**
	 *	Builds a test double for the data from the cust service request form.
	 */
	private function _dataTableCust()
	{
		$table = new stdClass;
		$table->name = "name";
		$table->sfdc_code = "11223344";
		$table->email = "email";
		$table->phone = "phone";
		$table->company = "company";
		$table->description = "description";
		$table->request = "License Key Request";
		$table->product = "SecureZIP";
		$table->prodversion = "anything";
		$table->platform = "Windows";
		$table->cpu = "Much Text";
		
		return $table;
	}
	/**
	 *	Builds a test double for the data from the request form.
	 */
	private function _dataTableTooLong()
	{
		$table = new stdClass;
		$table->name = "name";
		$table->sfdc_code = "123456789012345678";
		$table->email = "email";
		$table->phone = "phone";
		$table->company = "company";
		$table->subject = "subject";
		$table->description = "description";
		
		return $table;
	}
	/**
	 *	Builds a test double for the data from the request form.
	 */
	private function _dataTableWithNullSfdcCode()
	{
		$table = new stdClass;
		$table->name = "name";
		$table->sfdc_code = null;
		$table->email = "email";
		$table->phone = "phone";
		$table->company = "company";
		$table->subject = "subject";
		$table->description = "description";
		
		return $table;
	}
	private function _expectedCustFormVariables()
	{
		$table = $this->_dataTableCust();
		
		return array(
	 		'orgid' => 'orgid',
	 		'recordType' => '11223344',
	 		'name' => $table->name,
	 		'email' => $table->email,
	 		'phone' => $table->phone,
	 		'company' => $table->company,
	 		'subject' => $table->request,
	 		'description' => $table->description,
	 		'Product__c' => $table->product,
	 		'Product_Family__c' => $table->product,
	 		'Version__c' => $table->prodversion,
	 		'Platform__c' => $table->platform,
	 		'License_Report__c' => $table->cpu,
	 		'c_external' => "1"
	 	);
	}
	private function _expectedFormVariables()
	{
		$table = $this->_dataTable();
		
		return array(
	 		'orgid' => 'orgid',
	 		'recordType' => '11223344',
	 		'name' => $table->name,
	 		'email' => $table->email,
	 		'phone' => $table->phone,
	 		'company' => $table->company,
	 		'subject' => $table->subject,
	 		'description' => $table->description,
	 		'c_external' => "1"
	 	);
	}
	private function _expectedFormVariablesShortened()
	{
		$table = $this->_dataTable();
		
		return array(
	 		'orgid' => 'orgid',
	 		'recordType' => '123456789012345',
	 		'name' => $table->name,
	 		'email' => $table->email,
	 		'phone' => $table->phone,
	 		'company' => $table->company,
	 		'subject' => $table->subject,
	 		'description' => $table->description,
	 		'c_external' => "1"
	 	);
	}
	private function _expectedFormVariablesDefaultSfdc()
	{
		$table = $this->_dataTable();
		
		return array(
	 		'orgid' => 'orgid',
	 		'recordType' => 'record_type',
	 		'name' => $table->name,
	 		'email' => $table->email,
	 		'phone' => $table->phone,
	 		'company' => $table->company,
	 		'subject' => $table->subject,
	 		'description' => $table->description,
	 		'c_external' => "1"
	 	);
	}
	private function _csMap()
	{
		return <<<EOT
name:name,
email:email,
phone:phone,
company:company,
subject:subject,
description:description,
request:subject,
product:Product__c,
product:Product_Family__c,
platform:Platform__c,
prodversion:Version__c,
cpu:License_Report__c
EOT
;
	}
}