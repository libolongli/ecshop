<?php
	
	return array(
		'channel'=>array('7niu','upyun'),     //自定义要同时采用的 上传方式,7niu,upyun...
		'db'=>array('localhost','root','root','blood'),  //数据库访问 host/用户名/密码/数据库
		'upyun'=>array(
			array('nomius1','nomius','libo1990622'),    //空间名称/用户名/密码
			array('nomius2','nomius','libo1990622'),
		),
		'7niu'=>array(
			//空间名称/AK/SK
			array('nomius1','zdHQdYUTQtPegwK2cs2N0_GEODdwQKFemd_hI6Jt','RKdSWQy1oK2vFJLzL1GewRBQVWedWtMjauwz53LR'), 
			array('dick1','DxGTGFQhr4VcsS6S9D4C3F1uoAIu9UeQbtRtLbr3','Pz3EADrf6-bZNbS0lIdoilPTUTPU8rGcTBKo4JWm'),  			
		),
		'rootpath'=>'D:/web/dick',  //网站根目录
		'dir'=>array('/doc12'),  //要上传的路径
		'table'=>'cdn',
	);