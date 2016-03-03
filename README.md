SFDC Plugin
=============

Name is rather prosaic, because this was intended simply to tickle an endpoint at SFDC
to set up further activity for us. You don't have to use it for WebToLead or WebToCase
or anything else inside of SFDC, as the endpoint it will hit is an open parameter. Use
it with any web service you want.

Installation
------------

I simply zipped the directory up, then installed it like any other Joomla extension would install.

Activity
------

This doesn't work on content to be displayed. Rather it sits on the save event inside of Joomla, and when the save event is triggered during one of the contexts you have chosen in the plugin configuration, it will grab the form data and ping the given endpoint with it.

There's an input box in the plugin configuration to list the fields you wish to send to the endpoint. Aside from the fields it always sends, it will send whatever fields are listed in that box.

The format for the field list is:

	source:destination
	
where "source" is the name of the field on the Joomla form it's reading from, and "destination" is the name of the post variable you want to send it to the endpoint as. There can only be two variables in any source/destination pairing, but you can set up mulitples by using the same variable in multiple pairs.

If you have designated multiple Joomla form fields for the same destination variable, it will use the first one in the list that has a value. If you designate multiple destination variables on the same Joomla form variable, that form variable will be copied into all the given fields.

For example:

	name:subject
	name:tested
	animal:subject
	code:tested
	
would result in the name field, if present on the form, being copied into both the subject and the tested variables to be posted to the given endpoint. But if the name variable is absent, while the animal and code variables are present on the form, then animal would be copied into subject while code would be copied into tested. If only the code variable is present on the form, then it would be copied into tested and be the only one, aside from the constants, to be sent.