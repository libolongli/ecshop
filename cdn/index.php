<?php
	date_default_timezone_set('PRC');
	
	/**
	 * class cdn  cdn上传的类
	 * @author   <dick>
	 * @date(2014/04/03)
	 */


	class cdn{	
	
		private $_db;		
		
		private $root = '';  			//网站根目录
		
		private $dir = array();  		//存放所有的文件
		
		private $config = array();		//存放需要上传的空间的配置信息
		
		private $table = '';
		function __construct(){
			
			$config = require_once('include/cdnconfig.php');
			require_once ('include/db_mysql.php');

			$this->root = $config['rootpath'];
			$this->table = $config['table'];
			//将需要遍历的文件放在一个数组里面
			foreach($config['dir'] as $k=>$v){
				$this->traverse($this->root.$v);
				sleep(1);
			}
			
			if(!$this->dir) exit('no file!');
			
			//数据库连接实例
				//$this->_db = new db_mysql($config['db'][0],$config['db'][1],$config['db'][2],$config['db'][3]);
				$this->_db = new sqlite3('cdn.db');

			
			//加载需要的文件
			if(in_array('upyun',$config['channel'])){
				require_once('include/upyun/upyun.class.php');
				foreach($config['upyun'] as $k=>$v){
					array_push($this->config,array('upyun',$v['0'],$v['1'],$v['2']));
				}

			}
			
			//加载需要的文件
			if(in_array('7niu',$config['channel'])){
				require_once("include/qiniu/io.php");
				require_once("include/qiniu/rs.php");
				foreach($config['7niu'] as $k=>$v){
					array_push($this->config,array('7niu',$v['0'],$v['1'],$v['2']));
				}
			}
					

			//用于开发其他云盘的数据

		}

		/**
		 * [main description]
		 * @return [type] [description]
		 */
		function main(){		
			
			try{		
				foreach($this->config as $k=>$v){
				
					if($v['0']=='upyun'){
						$this->writeToUpyun($v);
					}
					
					if($v['0']=='7niu'){
						$this->writeToSeven($v);
					}

					//在下面用于其它盘的开发...
				}
			}catch(Exception $e){
				 echo $e->getCode();
				 echo $e->getMessage();
			}			
			echo 'done';
			exit;			
		}
		
		//遍历文件夹
		function traverse($path = '.') {
		
			if(!is_dir($path)){
				echo $path." is not exit!";
				return false;
			}
			
			$current_dir = opendir($path);    //opendir()返回一个目录句柄,失败返回false
			while(($file = readdir($current_dir)) !== false) {    //readdir()返回打开目录句柄中的一个条目
				$sub_dir = $path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
				if($file == '.' || $file == '..') {
					continue;
				}else if(is_dir($sub_dir)) {    //如果是目录,进行递归
					$this->traverse($sub_dir);
				} else {    //如果是文件进行操作
					$tmpfile = $path . DIRECTORY_SEPARATOR . $file;
					array_push($this->dir,$tmpfile);
				}
			}
		}
		
		
		//往UPyun上传
		function writeToUpyun($config){
			$sql = "SELECT * FROM {$this->table} WHERE bucket='{$config['1']}' AND type=1";
			$a = $b =array();
			$a =  $this->getAll($sql);
			
			foreach($a as $k=>$v){
				$b[md5($v['filename'])] = $v;
			}
			
			$link = new UpYun($config['1'], $config['2'], $config['3']);
			foreach($this->dir as $key=>$file){
				$md5 = md5(file_get_contents($file));
				$fm = md5(str_replace('\\','/',$file));
				$config['file'] =str_replace('\\','/',$file);
				if((!isset($b[$fm])) || $b[$fm]['md5']!=$md5){
					$fh = fopen($file, 'rb');
					$tf  = str_replace($this->root,'',$file);
								
					$rsp = $link->writeFile($tf, $fh, True);
					fclose($fh);
					
					//上传成功执行写入数据库操作
					if($rsp){	
						$config['update'] =0;
						
						if(isset($b[$fm])  && $b[$fm]['md5']!=$md5){
							$config['update']=1;
						}	
						$config['md5'] = $md5;
						
						$config['id'] = isset($b[$fm]['id']) ? $b[$fm]['id'] :0;
						$this->writeToDb($config);
					}
					echo '*';
					sleep(1);
				}
				
			}	
			
		}
		
		//往7牛上传
		function writeToSeven($config){

			$sql = "SELECT * FROM {$this->table} WHERE bucket='{$config['1']}' AND type=2";
			$a = $b =array();
			$a = $this->getAll($sql);
			
			foreach($a as $k=>$v){
				$b[md5($v['filename'])] = $v;
			}

			//空间密钥等各种信息
			$bucket = $config['1'];
			$accessKey = $config['2'];
			$secretKey = $config['3'];
			Qiniu_SetKeys($accessKey, $secretKey);
			
			
			foreach($this->dir as $key=>$file){
			    $md5 = md5(file_get_contents($file));
				
				$fm = md5(str_replace('\\','/',$file));
				$config['file'] =str_replace('\\','/',$file);
				//echo $file;exit;
				if((!isset($b[$fm])) || $b[$fm]['md5']!=$md5){
					$tf  = str_replace($this->root.'/','',$file);
					$tf  = str_replace('\\','/',$tf);
					//上传到7牛
					$putPolicy = new Qiniu_RS_PutPolicy($bucket.':'.$tf);
					
					$upToken = $putPolicy->Token(null);
					$putExtra = new Qiniu_PutExtra();
					$putExtra->Crc32 = 1;
					$rsp = Qiniu_PutFile($upToken,$tf,$file,$putExtra);
					//插入数据库
					if($rsp['0']['key']==$tf){	
						$config['update'] =0;
						if(isset($b[$fm])  && $b[$fm]['md5']!=$md5){
							$config['update']=1;
						}	
						$config['md5'] = $md5;
						
						$config['id'] = isset($b[$fm]['id']) ? $b[$fm]['id'] :0;
						$this->writeToDb($config);
					}
					echo '*';
					sleep(1);
				}
				
				
			}
		}
		
		//在下面急性其他CDN的函数开发






		//往数据库写入数据
		function writeToDb($config){
			$date  = time();
			$type = $config[0]=='upyun' ? 1 : 2;
			
			
			
			if($config['update']){
				$sql = " UPDATE {$this->table} SET md5='{$config['md5']}',date=$date WHERE id={$config['id']}";
				
			}else{	
				$sql = "INSERT INTO {$this->table} (filename,md5,type,date,bucket) 
					VALUES('{$config['file']}','{$config['md5']}','{$type}',{$date},'{$config['1']}')";	
				
			}
			
			$this->_db->query($sql);
		}
		
		
		function getAll($sql){

			$result = $this->_db->query($sql);//->fetchArray(SQLITE3_ASSOC); 
			$row = array(); 
		 
			while($res = $result->fetchArray(SQLITE3_ASSOC)){ 
					array_push($row,$res);
			} 
			
			return $row;
		}
		
		//.....
		
	}
	
	
	
	$cdn = new cdn();
	$cdn->main();
	
	
	
	
	