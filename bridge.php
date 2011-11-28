<?php
/**
* For now this is just a proof of concept!
* Only the following collectd plugins are supported: cpu, vmem, memory, interface
* Support for the collectd types.db will be included very soon
**/

// read data pushed by collectd - it´s posted, but not recognized by php as post data
$fd = fopen('php://input','r');

// debug bridge -> graphite stream
//$logFile = fopen('/var/www/html/graphite.log', 'a');

// open connection to carbon
$graphiteConn = fsockopen('192.168.100.199', 2003);

#HTTP_RAW_POST_DATA works - but its not possible to fwrite it
#syslog(5,$HTTP_RAW_POST_DATA);

// decode json received by collectd
$data = json_decode($HTTP_RAW_POST_DATA);

// pushed data mostly contains multiple values - iterate over it
foreach ($data as $row) { 

	// FIXME: detect ip addresses here - add more generic approach
	$hostChunks = explode('.', $row->host);

	// FIXME: plugin instance should not be included if not existent
	$pluginInstance = 'default';

	if ($row->plugin_instance != '') {
		$pluginInstance = $row->plugin_instance;
	}
	
	// FIXME: implement types.db parsing
	if ($row->plugin == 'cpu' || $row->plugin == 'vmem' || $row->plugin == 'memory') {
		fwrite($graphiteConn, 'collectd.'.$hostChunks[0].'.'.$row->plugin.'.'.$pluginInstance.'.'.$row->type.'.'.$row->type_instance.' '.$row->values[0].' '.$row->time.PHP_EOL);
	}
	if ($row->plugin == 'interface' && substr($row->type_instance, 0, 3) != 'vif') {
		fwrite($graphiteConn, 'collectd.'.$hostChunks[0].'.'.$row->plugin.'.'.$pluginInstance.'.'.$row->type.'.'.$row->type_instance.'.rx'.' -'.$row->values[0].' '.$row->time.PHP_EOL);
		fwrite($graphiteConn, 'collectd.'.$hostChunks[0].'.'.$row->plugin.'.'.$pluginInstance.'.'.$row->type.'.'.$row->type_instance.'.tx'.' '.$row->values[1].' '.$row->time.PHP_EOL);
	}
}
