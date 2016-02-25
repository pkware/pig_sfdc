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
