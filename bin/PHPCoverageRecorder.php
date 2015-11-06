<?php
defined ( 'PHPCOVERAGE_HOME' ) | define ( 'PHPCOVERAGE_HOME', realpath ( dirname ( __FILE__ ) ) . '/../' );

class PHPCoverageRecorder {
	
	protected static $_recordFiles;
	protected static $_confXML;
	
	public static function run($paramCount, $params) {
		
		//self::mergeRecordDir('./test',false,'merge.log');
		//self::mergeRecordDir('/home/work/tmp/test','work@db-testing-ecom123-vm3.db01.baidu.com','merge.log');
		//exit;
		
		self::$_recordFiles = array ();
		self::$_confXML = PHPCOVERAGE_HOME . 'conf/conf_coverage.xml';
		
		if ( 'start' === trim ( $params [1] )) {
			if('' != trim($params[2]) && file_exists( trim($params[2]) ) ){
                self::$_confXML = trim($params[2]);
            }
            self::loadXML ();
			self::startRecord ();
		} elseif ( 'stop' === trim ( $params [1] )) {
			if('' != trim($params[2]) && file_exists( trim($params[2]) ) ){
                self::$_confXML = trim($params[2]);
            }
			self::loadXML ();
			self::stopRecord ();
		} elseif ( 'restart' === trim ( $params [1] )) {
			if('' != trim($params[2]) && file_exists( trim($params[2]) ) ){
                self::$_confXML = trim($params[2]);
            }
			self::loadXML ();
			self::stopRecord ();
			self::cleanRecord ();
			self::startRecord ();
		} elseif ( 'clean' === trim ( $params [1] )) {
			self::cleanRecord ();
		} elseif ( 'help' === trim ( $params [1] )) {
			self::help ();
		} elseif ($paramCount >= 3 && 'merge' === trim ( $params [1] )) {
			$conds = getopt ( "h::f:d:o::" );
			
			if ( count($conds) == $paramCount - 2 ){
                    if( !( empty($conds ['f']) || is_array ($conds ['f']) || is_array ($conds ['h']) || is_array ($conds ['o']) ) ) {
				        self::mergeRecord ( $conds ['f'], $conds ['h'], $conds ['o'] );
                    }elseif( !( empty($conds ['d']) || is_array ($conds ['d']) || is_array ($conds ['h']) || is_array ($conds ['o']) ) ) { 
				        self::mergeRecordDir ( $conds ['d'], $conds ['h'], $conds ['o'] );
                    }else{
				        self::help ();
                    }
			} else {
				self::help ();
			}
		} else {
			self::help ();
		}
	}
	
	protected static function loadXML() {
		if (file_exists ( self::$_confXML ) && filesize ( self::$_confXML ) > 0) {
			$xml = simplexml_load_file ( self::$_confXML );
			if (! $xml) {
				throw new Exception ( "ERROR____: Fail to load " . self::$_confXML . "\n" );
			}
			foreach ( $xml->{'recordfile'} as $recordfile ) {
				$file_path = ( string ) $recordfile ['file'];
				self::$_recordFiles [$file_path] ['whitelist'] = array ();
				self::$_recordFiles [$file_path] ['blacklist'] = array ();
				
				if (count ( $recordfile->{'whitelist'}->{'path'} ) > 0) {
					foreach ( $recordfile->{'whitelist'}->{'path'} as $whitelist_path ) {
						self::$_recordFiles [$file_path] ['whitelist'] [] = ( string ) $whitelist_path;
					}
				} else {
					throw new Exception ( "ERROR____: No whitelist forders configured in  " . $file_path . "\n" );
				}
				if (count ( $recordfile->{'blacklist'}->{'path'} ) > 0) {
					foreach ( $recordfile->{'blacklist'}->{'path'} as $blacklist_path ) {
						self::$_recordFiles [$file_path] ['blacklist'] [] = ( string ) $blacklist_path;
					}
				}
			}
		} else {
			throw new Exception ( "ERROR____: Could not open " . self::$_confXML . " or empty file " . "\n" );
		}
	
	}
	
	protected static function cleanRecord() {
		$res = shell_exec ( '/bin/rm -rf ' . PHPCOVERAGE_HOME . 'logs/*' );
		return $res;
	}
	
	protected static function startRecord() {
		if (count ( self::$_recordFiles ) == 0) {
			throw new Exception ( "WARNING____: No file configured in " . self::$_confXML . "\n" );
		}
		foreach ( self::$_recordFiles as $file_path => $file_params ) {
			if ($file_handle = fopen ( $file_path, "r" )) {
				$lineNum = 1;
				$ret = array ();
				while ( ! feof ( $file_handle ) ) {
					$line = fgets ( $file_handle );
					if (($lineNum == 2) && ! ('//PHPCOVERAGE_INFORMATION_STARTPOINT' === trim ( $line ))) {
						$ret [] = '//PHPCOVERAGE_INFORMATION_STARTPOINT' . "\n";
						$ret [] = str_replace ( '##PHPCOVERAGE_HOME##', PHPCOVERAGE_HOME, 'require_once(\'##PHPCOVERAGE_HOME##Util/CodeCoverageListener.php\');' ) . "\n";
						$ret [] = '$PHPCOVERAGE_INCLUDEPATH = array(' . "\n";
						foreach ( $file_params ['whitelist'] as $whitelist_path ) {
							$ret [] = str_replace ( '##WHITELIST_PATH##', $whitelist_path, '        \'##WHITELIST_PATH##\',' ) . "\n";
						}
						$ret [] = ');' . "\n";
						$ret [] = '$PHPCOVERAGE_EXCLUDEPATH = array(' . "\n";
						foreach ( $file_params ['blacklist'] as $blacklist_path ) {
							$ret [] = str_replace ( '##BLACKLIST_PATH##', $blacklist_path, '        \'##BLACKLIST_PATH##\',' ) . "\n";
						}
						$ret [] = ');' . "\n";
						$ret [] = '$phpcoveragetrace = new PHPCoverageListener($PHPCOVERAGE_INCLUDEPATH, $PHPCOVERAGE_EXCLUDEPATH);' . "\n";
						$ret [] = '//PHPCOVERAGE_INFORMATION_ENDPOINT' . "\n";
					}
					$ret [] = $line;
					
					$lineNum ++;
				}
				fclose ( $file_handle );
				if ($file_handle = fopen ( $file_path, "w" )) {
					foreach ( $ret as $ret_line ) {
						fwrite ( $file_handle, $ret_line );
					}
					fclose ( $file_handle );
				} else {
					throw new Exception ( "ERROR____: Could not write " . $file_path . "\n" );
				}
			
			} else {
				throw new Exception ( "ERROR____: Could not open " . $file_path . "\n" );
			}
		
		}
	
	}
	
	protected static function stopRecord() {
		if (count ( self::$_recordFiles ) == 0) {
			throw new Exception ( "WARNING____: No file configured in " . self::$_confXML . "\n" );
		}
		foreach ( self::$_recordFiles as $file_path => $file_params ) {
			if ($file_handle = fopen ( $file_path, "r" )) {
				$flag = 1;
				$ret = array ();
				while ( ! feof ( $file_handle ) ) {
					$line = fgets ( $file_handle );
					if ('//PHPCOVERAGE_INFORMATION_STARTPOINT' === trim ( $line )) {
						$flag = 0;
						continue;
					} elseif ('//PHPCOVERAGE_INFORMATION_ENDPOINT' === trim ( $line )) {
						$flag = 1;
						continue;
					}
					if ($flag) {
						$ret [] = $line;
					}
				}
				fclose ( $file_handle );
				if ($file_handle = fopen ( $file_path, "w" )) {
					foreach ( $ret as $ret_line ) {
						fwrite ( $file_handle, $ret_line );
					}
					fclose ( $file_handle );
				} else {
					throw new Exception ( "ERROR____: Could not write " . $file_path . "\n" );
				}
			
			} else {
				throw new Exception ( "ERROR____: Could not open " . $file_path . "\n" );
			}
		
		}
	
	}
	
	public static function mergeRecord($file, $remote = false, $output = false) {
		if (empty ( $file )) {
			throw new Exception ( "ERROR____: file not recommend, please use help for more infomation!\n" );
		}
		$mergeData = null;
		empty ( $output ) ? $output = PHPCOVERAGE_HOME . 'logs/coverage_log' : $output;
		if ($remote) {
			$cmd = "ssh " . $remote . "  -t 'cat " . $file . " '";
			$res = trim ( `$cmd` );
			if (strpos ( $res, 'No such file or directory' ) || strpos ( $res, 'Name or service not known' )) {
				throw new Exception ( "ERROR____: Fail to get record file: " . $file . " from remote: " . $remote . "\n" );
			}
			if (empty ( $res )) {
				echo "WARNING____: Empty file: " . $file . " from remote: " . $remote . "\n";
				return false;
			}
			$res = explode ( "\n", $res );
			if ($handle = fopen ( $output, 'a' )) {
				foreach ( $res as $line ) {
					$line = trim ( $line );
					if (! empty ( $line )) {
						fwrite ( $handle, $line . "\n" );
					}
				}
				fclose ( $handle );
			} else {
				throw new Exception ( "ERROR____: Fail to merge record! \n" );
			}
		} else {
			$cmd = "cat " . $file;
			$res = trim ( `$cmd` );
			if (empty ( $res )) {
				echo "WARNING____: Empty file: " . $file . "\n";
				return false;
			}
			$res = explode ( "\n", $res );
			if ($handle = fopen ( $output, 'a' )) {
				foreach ( $res as $line ) {
					$line = trim ( $line );
					if (! empty ( $line )) {
						fwrite ( $handle, $line . "\n" );
					}
				}
				fclose ( $handle );
			} else {
				throw new Exception ( "ERROR____: Fail to merge record! \n" );
			}
		}
	}
	
	public static function mergeRecordDir($dir, $remote = false, $output = false) {
		if (empty ( $dir )) {
			throw new Exception ( "ERROR____: dir not recommend, please use help for more infomation!\n" );
		}
		$mergeData = null;
		empty ( $output ) ? $output = PHPCOVERAGE_HOME . 'logs/coverage_log' : $output;
		
		if ($handle_file = fopen ( $output, 'a' )) {
			echo "INFO____: Begin to merge Record..." . "\n";
		} else {
			throw new Exception ( "ERROR____: Fail to merge record! \n" );
		}
		
		if ($remote) {
			$tmp_dir = 'tmp_merge_dir_' . time ();
			$cmd = "scp -r " . $remote . ":" . trim ( $dir ) . " ./" . $tmp_dir;
			$res = shell_exec ( $cmd );
			if (   strpos ( $res, 'No such file or directory' ) 
                || strpos ( $res, 'Name or service not known') 
                || strpos ( $res, 'Network is unreachable') 
            ) {
				throw new Exception ( "ERROR____: Fail to get record dir: " . $dir . " from remote: " . $remote . "\n" );
			}
			$dir = $tmp_dir;
		}
		
		if ($handle_dir = opendir ( $dir )) {
			while ( false !== ($file = readdir ( $handle_dir )) ) {
				if ($file != "." && $file != ".." && $file != ".svn") {
					if (is_file ( $dir . "/" . $file )) {
						$content = file_get_contents ( "$dir/$file" );
						$content = trim ( $content );
						if (empty ( $content )) {
							echo "WARNING____: Empty file: " . $dir . "/" . $file . "\n";
						} else {
							$content = explode ( "\n", $content );
							foreach ( $content as $line ) {
								$line = trim ( $line );
								if (! empty ( $line )) {
									fwrite ( $handle_file, $line . "\n" );
								}
							}
						}
					}
				}
			}
		}else{
            throw new Exception ( "ERROR____: Fail to get record dir: " . $dir . " from remote: " . $remote . "\n" );
        }
		
		echo "INFO____: Finish to merge record..." . "\n";
		fclose ( $handle_file );
		if ($remote) {
			$res = shell_exec ( "/bin/rm -rf " . $tmp_dir );
            if (! empty ( $res )) {
				throw new Exception ( "WARNING____: Fail to remove tmp dir: " . $tmp_dir . "\n" );
			}
		}
	}
	
	protected static function help() {
		echo "############################# HELP INFO ##############################\n";
		echo "start  : start record coverage infomation\n";
		echo "stop   : stop record coverage infomation\n";
		echo "restart: clean old records and then start record coverage infomation\n";
		echo "clean  : clean records\n";
		echo "help   : help infomation\n";
		echo "merge  : local => php " . __FILE__ . " merge -f\$file -o\$log\n";
		echo "merge  : remote=> php " . __FILE__ . " merge -hwork@bb-testing-ecom124.vm.baidu.com -f\$file -o\$log\n";
		echo "merge  : local dir  => php " . __FILE__ . " merge -d\$dir -o\$log\n";
		echo "merge  : remote dir => php " . __FILE__ . " merge -hwork@bb-testing-ecom124.vm.baidu.com -d\$dir -o\$log\n";
	
	}

}


PHPCoverageRecorder::run ( $argc, $argv );


