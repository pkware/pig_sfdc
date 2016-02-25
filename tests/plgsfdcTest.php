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
	public function get($term)
	{
		if ($term == 'context') {
			return "fred.view, \r\nsam.view";
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
		$table->email = "email";
		$table->phone = "phone";
		$table->company = "company";
		$table->subject = "subject";
		$table->description = "description";
		
		return $table;
	}
	private function _expectedFormVariables()
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
}