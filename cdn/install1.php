<?php
	$db = new sqlite3('cdn.db');
	$sql = " create table cdn(
		id integer primarykey auto_increment,
		filename varchar(255),
		md5 varchar(32),
		bucket varchar(50),
		type integer(2),
		date integer(11)
		)";
	$db->query($sql);
	echo 'done.....';
	$db->close();