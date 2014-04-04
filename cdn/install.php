<?php
	
	date_default_timezone_set('PRC');
	require_once ('include/db_mysql.php');
	

	$config = require_once('include/cdnconfig.php');
	$table = $config['table'];
	$db = new db_mysql($config['db'][0],$config['db'][1],$config['db'][2],$config['db'][3]);
	$sql = "
		CREATE TABLE `{$table}` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `filename` varchar(255) NOT NULL,
		  `md5` varchar(255) NOT NULL,
		  `bucket` varchar(255) DEFAULT NULL COMMENT '空间',
		  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 代表 upyun 2 代表7niu',
		  `date` int(11) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8
	";
	$s = "DROP TABLE IF EXISTS `{$table}`";
	$db->query($s);
	$db->query($sql);

	echo 'ok!  run index.php please...!';

	