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
	 * Take the data from a new request form and curl it to Salesforce.com to
	 * create a case.
	 *
	 * @param	string	The context of the plugin: component/module name.view
	 * @param	JTable	The object that holds the data about to be saved.
	 * @param	boolean	Flag to indicate the content is new.
	 */
	 function onContentBeforeSave($context, $table, $isNew)
	 {
	 	$result = true;
	 	$form_vars = Array();
	 	$recordType = empty($table->sfdc_code) ? $this->params->get('record_type') :
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
	
	 		$form_vars['orgid'] = $this->params->get('orgid');
	 		$form_vars['recordType'] = substr($recordType, 0, 15);
	 		$form_vars['name'] = $table->name;
	 		$form_vars['email'] = $table->email;
	 		$form_vars['phone'] = $table->phone;
	 		$form_vars['company'] = $table->company;
	 		$form_vars['subject'] = $table->subject;
	 		$form_vars['description'] = $table->description;
	 		$form_vars['c_external'] = "1";
	 		
	 		$response = $this->sendData($this->params->get('endpoint'), $form_vars);
	 		$result = ($response === 200);
	 	}

		return $result;
	}
	/**
	 *	Send the data the SFDC web2case endpoint
	 */
	function sendData( $targetURL, $form_vars )
	{
		$sender = curl_init();
		curl_setopt($sender, CURLOPT_URL, $targetURL);
		curl_setopt($sender, CURLOPT_POST, true);
		$fields_string = http_build_query($form_vars);
		curl_setopt($sender, CURLOPT_POSTFIELDS, $fields_string);
		
		curl_exec($sender);

		return curl_getinfo($sender, CURLINFO_HTTP_CODE);
	}
}
