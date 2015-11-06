<?php
defined ( 'PHPCOVERAGE_HOME' ) || define ( 'PHPCOVERAGE_HOME', realpath ( dirname ( __FILE__ ) ) . '/../' );

require_once (PHPCOVERAGE_HOME . 'Util/Filter.php');
PHPCoverage_Util_Filter::addFileToFilter ( __FILE__, 'PHPCoverage' );

class PHPCoverageListener {
	
	protected $_includePaths;
	protected $_excludePaths;
	protected $_uncoveredFiles;
	protected $_coverageData;
    protected $_phpExtensions;
    protected $_logFile;
	
	public function __construct($includePaths = array("."), $excludePaths = array()) {
		$this->_phpExtensions = array ('php', 'tpl', 'inc' );
		$this->_uncoveredFiles = array ();
		$this->_includePaths = $includePaths;
		$this->_excludePaths = $excludePaths;
		$this->_logFile = PHPCOVERAGE_HOME . 'logs/coverage_log';
		$this->startListener ();
	}
	
	public function __destruct() {
		$this->stopListener ();
	}
	
	protected function startListener() {
		if (extension_loaded ( "xdebug" )) {
			xdebug_start_code_coverage ( XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE );
			return true;
		} else {
			exit ( "ERROR____: xdebug not loaded !" );
		}
	}
	
	public function stopListener() {
		if (extension_loaded ( "xdebug" )) {
			$this->_coverageData = xdebug_get_code_coverage ();
			xdebug_stop_code_coverage ();
			
			foreach ( $this->_coverageData as $file => &$lines ) {
				$this->_coverageData [$this->replaceBackslashes ( realpath ( $file ) )] = $lines;
			}
			
			$this->_coverageData = $this->stripCoverageData ();
			$this->addRecord ();
			return true;
		} else {
			exit ( "ERROR____: xdebug not loaded !" );
		}
	}
	
	protected function addRecord($record = false) {
		$log_file = PHPCOVERAGE_HOME . 'logs/log';
		if (! $record) {
			$record = $this->_coverageData;
			$log_file = $this->_logFile;
		}
		if (! empty ( $record )) {
			if ($handle = fopen ( $log_file, 'a' )) {
				$outputStr = json_encode ( $record );
				fwrite ( $handle, $outputStr . "\n" );
				fclose ( $handle );
			}
		}
	}
	
	protected function cleanRecord() {
		$res = shell_exec ( '/bin/rm -rf ' . PHPCOVERAGE_HOME . 'logs/*' );
		return $res;
	}
	
	protected function stripCoverageData() {
		if (empty ( $this->_coverageData )) {
			return $this->_coverageData;
		}
		$this->processSourcePaths ();
		
		$this->addFiles ();
		$altCoverageData = array ();
		foreach ( $this->_coverageData as $filename => &$lines ) {
			$preserve = false;
			$realFile = $filename;
			for($i = 0; $i < count ( $this->_includePaths ); $i ++) {
				if (strpos ( $realFile, $this->_includePaths [$i] ) === 0) {
					$preserve = true;
				}
			}
			for($i = 0; $i < count ( $this->_excludePaths ); $i ++) {
				if (strpos ( $realFile, $this->_excludePaths [$i] ) === 0) {
					$preserve = false;
				}
			}
            if ($preserve) {
                if (count ( $lines ) > 0) {
                    foreach($lines as $line=>$lineInfo){
                        if(is_array($lineInfo)){
                            $i=1;
                            $lines[$line] = array();
                            while(count($lineInfo)){
                                $lines[$line][$i] = array_shift($lineInfo);
                                $i++;
                            }
                        }
                    }
					$altCoverageData [$filename] = $lines;
				} else {
					// altCoverageData[$filename] = $lines;
					$this->addUncoveredFiles ( $filename );
				}
			}
		}

		if (count ( $this->_uncoveredFiles ) > 0) {
			$uncovered_files_data = $this->addUncoveredFilesInformation ( $this->_uncoveredFiles );
			$altCoverageData = array_merge ( $altCoverageData, $uncovered_files_data );
		}

		array_multisort ( $altCoverageData, SORT_STRING );
		return $altCoverageData;
	
	}
	
	protected function processSourcePaths() {
		$this->removeAbsentPaths ( $this->_includePaths );
		$this->removeAbsentPaths ( $this->_excludePaths );
	}
	
	protected function removeAbsentPaths(&$dirs) {
		for($i = 0; $i < count ( $dirs ); $i ++) {
			if (! file_exists ( $dirs [$i] )) {
				array_splice ( $dirs, $i, 1 );
				$i --;
			} else {
				$dirs [$i] = realpath ( $dirs [$i] );
			}
		}
	}
	
	protected function addFiles() {
		$files = array ();
		for($i = 0; $i < count ( $this->_includePaths ); $i ++) {
			$this->_includePaths [$i] = $this->replaceBackslashes ( $this->_includePaths [$i] );
			if (is_dir ( $this->_includePaths [$i] )) {
				$this->getFilesAndDirs ( $this->_includePaths [$i], $files );
			} else if (is_file ( $this->_includePaths [$i] )) {
				$files [] = $this->_includePaths [$i];
			}
		}
		for($i = 0; $i < count ( $this->_excludePaths ); $i ++) {
			$this->_excludePaths [$i] = $this->replaceBackslashes ( $this->_excludePaths [$i] );
		}
		for($i = 0; $i < count ( $files ); $i ++) {
			for($j = 0; $j < count ( $this->_excludePaths ); $j ++) {
				if (strpos ( $files [$i], $this->_excludePaths [$j] ) === 0) {
					continue;
				}
			}
			if (! array_key_exists ( $files [$i], $this->_coverageData )) {
				$this->_coverageData [$files [$i]] = array ();
			}
		}
	}
	
	protected function getFilesAndDirs($dir, &$files) {
		$dirs [] = $dir;
		while ( count ( $dirs ) > 0 ) {
			$currDir = realpath ( array_pop ( $dirs ) );
			if (! is_readable ( $currDir )) {
				continue;
			}
			$currFiles = scandir ( $currDir );
			for($j = 0; $j < count ( $currFiles ); $j ++) {
				if ($currFiles [$j] == "." || $currFiles [$j] == "..") {
					continue;
				}
				$currFiles [$j] = $currDir . "/" . $currFiles [$j];
				if ( is_file($currFiles[$j]) && (substr($currFiles[$j],-4) == '.php') ) {
					$pathParts = pathinfo ( $currFiles [$j] );
					if (isset ( $pathParts ['extension'] ) && in_array ( $pathParts ['extension'], $this->_phpExtensions )) {
						$files [] = $this->replaceBackslashes ( $currFiles [$j] );
					}
				}
				if (is_dir ( $currFiles [$j] )) {
					$dirs [] = $currFiles [$j];
				}
			}
		}
	}
	
	public function replaceBackslashes($path) {
		$path = str_replace ( "\\", "/", $path );
		if (strpos ( $path, ":" ) === 1) {
			$path = strtoupper ( substr ( $path, 0, 1 ) ) . substr ( $path, 1 );
		}
		return $path;
	}
	
	protected function addUncoveredFilesInformation($uncovered_files) {
		$total_coverage = array ();
		foreach ( $uncovered_files as $file => $status ) {
			if ($status && file_exists ( $file )) {
				xdebug_start_code_coverage ( XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE );
				include_once ($file);
				$coverage = xdebug_get_code_coverage ();
				xdebug_stop_code_coverage ();
				foreach ( $coverage as $filename => $cov ) {
					if ($this->_uncoveredFiles [$filename]) {
                        foreach ( $cov as $cov_line => $cov_line_flag ) {
                            if( is_array($cov_line_flag) ){
                                /*
                                foreach(array_keys($cov_line_flag) as $flag){
                                    $cov[$cov_line][$flag] = 0;
                                }
                                */
                                $cov[$cov_line] = array();
                                for($i=1; $i<=count($cov_line_flag); $i++){
                                    $cov[$cov_line][$i] = 0;
                                }
                            }elseif ($cov_line_flag > 0) {
								$cov[$cov_line] = - 1;
							}
							
							$total_coverage [$filename] = $cov;
							$this->_uncoveredFiles [$filename] = false;
						}
					}
				}
			}
		}
		return $total_coverage;
	}
	
	protected function addUncoveredFiles($filename) {
		$this->_uncoveredFiles [$filename] = true;
	}

}
