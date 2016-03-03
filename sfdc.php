<?php
// no direct access
defined( '_JEXEC' ) or die;
 
class PlgContentSfdc extends JPlugin
{
	/**
	 * Load the language file on instantiation. Note this is only available in Joomla 3.1
	 * and higher.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;
	/**
	 * Holds the data to be sent.
	 *
	 * @var    array
	 * @since  3.1
	 */
	protected $formVars = array();

	/**
	 * Take the data from a new request form and curl it to Salesforce.com to
	 * create a case.
	 *
	 * @param	string	The context of the plugin: component/module name.view
	 * @param	JTable	The object that holds the data about to be saved.
	 * @param	boolean	Flag to indicate the content is new.
	 */
	 function onContentBeforeSave($context, $table, $isNew)
	 {
	 	$mappings        = $this->_setMappings();
	 	$result          = true;
	 	$this->formVars  = Array();
	 	$recordType      = empty($table->sfdc_code) ? $this->params->get('record_type') :
	 												$table->sfdc_code;
	 	
	 	if ($isNew && 
	 		in_array($context, preg_split(
	 				"/[\s,]+/",
	 				$this->params->get('context'),
	 				NULL,
	 				PREG_SPLIT_NO_EMPTY
	 		))
	 	) {
	 		$result = false;
	
	 		$this->formVars['c_external'] = "1";
	 		$this->formVars['orgid'] = $this->params->get('orgid');
	 		$this->formVars['recordType'] = substr($recordType, 0, 15);

			foreach ($mappings as $sourceDest)
			{
				$source = $sourceDest[0];
				if (isset($table->$source))
				{
					$this->_setSendVariable($sourceDest[1], $table->$source);
				}
			}
	 		
	 		$response = $this->sendData($this->params->get('endpoint'));
	 		$result = ($response === 200);
	 	}

		return $result;
	}
	/**
	 *	Send the data the SFDC web2case endpoint
	 *
	 * @param	string	$targetURL	The URL to send the data to
	 */
	function sendData( $targetURL )
	{
		$sender = curl_init();
		curl_setopt($sender, CURLOPT_URL, $targetURL);
		curl_setopt($sender, CURLOPT_POST, true);
		$fields_string = http_build_query($this->formVars);
		curl_setopt($sender, CURLOPT_POSTFIELDS, $fields_string);
		
		curl_exec($sender);

		return curl_getinfo($sender, CURLINFO_HTTP_CODE);
	}
	/**
	 * Build the field mappings array
	 *
	 * @return	array	Array linking table attributes to form_vars
	 */
	protected function _setMappings()
	{
		$mappings = array();
	
		$pairs = preg_split(
			"/[\s,]+/",
			$this->params->get('field_mappings'),
			NULL,
			PREG_SPLIT_NO_EMPTY
		);
		foreach ($pairs as $pair)
		{
			$sourceDest = explode(":", $pair, 2);
			$mappings[] = $sourceDest;
		}
		return $mappings;
	}
	/**
	 * Sets the forms variable only if the value is not null.
	 *
	 * @return	array	Array form variables
	 */
	protected function _setSendVariable($key, $value=null)
	{
		if (!is_null($value) && !isset($this->formVars[$key]))
		{
			$this->formVars[$key] = $value;
		}
	}
}
