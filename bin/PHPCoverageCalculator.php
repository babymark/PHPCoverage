<?php
defined ( 'PHPCOVERAGE_HOME' ) || define ( 'PHPCOVERAGE_HOME', realpath ( dirname ( __FILE__ ) ) . '/../' );

require_once (PHPCOVERAGE_HOME . "Util/Report.php");

class PHPCoverageCalculator {
	
	protected $_coverageData = null;
	protected $_coverageLog = null;
	protected $_coverageReportDirectory = 'CoverageReport';
	protected $_coverageReportTitle = 'PHP Coverage Report';
	protected $_mapRules = null;
	protected $_diffListFiles = null;
	protected $_useDiff = false;
	protected $_backupLog = null;
	
	public function __construct($cli = false) {
		$this->set_coverageLog ( PHPCOVERAGE_HOME . 'logs/coverage_log' );
		
		if ($cli) {
			if ($_SERVER ['argc'] == 1) {
				$this->generateReport ();
			} elseif ($_SERVER ['argc'] >= 2) {
				$conds = getopt ( "d::h::m::f::o::t::b::" );
				if (in_array ( 'h', array_keys ( $conds ) )) {
					$this->help ();
				} else {
					if (isset ( $conds ['m'] ) && ! empty ( $conds ['m'] )) {
						$this->loadMapRules ( $conds ['m'] );
					}
					if (isset ( $conds ['d'] ) && ! empty ( $conds ['d'] )) {
						$this->loadDiffListFiles ( $conds ['d'] );
					}
					if (isset ( $conds ['f'] ) && ! empty ( $conds ['f'] )) {
						$this->set_coverageLog ( $conds ['f'] );
					}
					if (isset ( $conds ['o'] ) && ! empty ( $conds ['o'] )) {
						$this->set_coverageReportDirectory ( $conds ['o'] );
					}
					if (isset ( $conds ['t'] ) && ! empty ( $conds ['t'] )) {
						$this->set_coverageReportTitle ( $conds ['t'] );
					}
					if (isset ( $conds ['b'] ) && ! empty ( $conds ['b'] )) {
						$this->set_backupLog ( $conds ['b'] );
					}
					$this->generateReport ();
				}
			} else {
				$this->help ();
			}
		}
	}
	
	public function get_useDiff() {
		return $this->_useDiff;
	}
	public function set_useDiff($useDiff = false) {
		$this->_useDiff = $useDiff;
	}
	
	public function get_coverageLog() {
		return $this->_coverageLog;
	}
	public function set_coverageLog($coverageLog = null) {
		$this->_coverageLog = $coverageLog;
	}
	
	public function get_coverageReportDirectory() {
		return $this->_coverageReportDirectory;
	}
	public function set_coverageReportDirectory($coverageReportDirectory = 'CoverageReport') {
		$this->_coverageReportDirectory = $coverageReportDirectory;
	}
	
	public function get_coverageReportTitle() {
		return $this->_coverageReportTitle;
	}
	public function set_coverageReportTitle($coverageReportTitle = 'PHP Coverage Report') {
		$this->_coverageReportTitle = $coverageReportTitle;
	}
	
	public function get_backupLog() {
		return $this->_backupLog;
	}
	public function set_backupLog($backupLog = null) {
		$this->_backupLog = $backupLog;
	}
	
	public function __destruct() {
		// this->cleanRecord();
	
	}
	
	public function loadMapRules($conf_map) {
		try {
			$xml = simplexml_load_file ( $conf_map );
			if (! $xml) {
				throw new Exception ( "ERROR____: Fail load " . $conf_map . "\n" );
			}
			foreach ( $xml->{'rule'} as $rule ) {
				$this->_mapRules [trim ( ( string ) $rule ['from'] )] = trim ( ( string ) $rule ['to'] );
			}
		} catch ( Exception $e ) {
			throw new Exception ( "ERROR____: INIT FAIL!\n" . $e->getMessage () );
		}
	}
	
	public function loadDiffListFiles($conf_diffList) {
		try {
			$xml = simplexml_load_file ( $conf_diffList );
			if (! $xml) {
				throw new Exception ( "ERROR____: Fail load " . $conf_diffList . "\n" );
			}
			foreach ( $xml->{'filter'}->{'whitelist'}->{'file'} as $diff ) {
				$this->_diffListFiles [] = trim ( ( string ) $diff );
			}
		} catch ( Exception $e ) {
			throw new Exception ( "ERROR____: INIT FAIL!\n" . $e->getMessage () );
		}
		$this->set_useDiff ( true );
	}
	
	public function generateReport() {
		$this->_coverageData = array ();
		$this->getRecord (); 
		echo "INFO____: Generating report, this may take a few minutes, please wait patiently...\n";
		//echo "INFO____: Memory used: " . (int)(xdebug_memory_usage()/(1024*1024)) . "M.\n";
		
		if (! empty ( $this->_backupLog )) {
			$this->backupRecord ();
        }
        $data = PHPCoverage_Util_CodeCoverage::formatCodeCoverage($this->_coverageData);
        $codeCoverageLineInformation    = $data['codeCoverage_line'];
        $codeCoverageBranchInformation  = $data['codeCoverage_branch'];
        unset($data);

		PHPCoverage_Util_Report::render ( $codeCoverageLineInformation, $codeCoverageBranchInformation, $this->_coverageReportDirectory, $this->_coverageReportTitle );
	}
	
	public function getRecord() {
		// record_files = array();
		$lineNum = 0;
		if (file_exists ( $this->_coverageLog )) {
			if ($handle = fopen ( $this->_coverageLog, 'r' )) {
				$lineNumTotal = trim ( shell_exec ( 'cat ' . $this->_coverageLog . ' |wc -l' ) );
				while ( ! feof ( $handle ) ) {
					if ($line = fgets ( $handle )) {
						$lineNum ++;
						echo "INFO____: Calculate line " . $lineNum . " (" . $lineNum . "/" . $lineNumTotal . ").\n";
						echo "INFO____: Memory used: " . ( int ) (xdebug_memory_usage () / (1024 * 1024)) . "M.\n";
                        $record = json_decode ( $line, true );
						$this->appendRecord ( $record );
						
						// record_files_tmp = array_keys($record);
						// record_files =
					// array_unique(array_merge($record_files,
					// $record_files_tmp));
					}
				}
				unset ( $record );
				fclose ( $handle );
				return true;
            } else { 
                throw new Exception ("ERROR____: Read Record failed!");
			}
		}
        throw new Exception ("ERROR____: Record not exist!");
	}
	
	public function appendRecord($record) {
		foreach ( $record as $file => $data ) {
			if (isset ( $this->_mapRules ) && count ( $this->_mapRules ) > 0) {
				foreach ( $this->_mapRules as $from => $to ) {
					if (strpos ( $file, $from ) === 0 || strpos ( $file, $from ) > 0) {
						$file = str_replace ( $from, $to, $file );
						break;
					}
				}
			}
			$needRecord = true;
			if ($this->_useDiff) {
				$needRecord = false;
				if (isset ( $this->_diffListFiles ) && count ( $this->_diffListFiles ) > 0) {
					foreach ( $this->_diffListFiles as $diff ) {
						if (strpos ( $file, $diff ) === 0 || strpos ( $file, $diff ) > 0) {
							$needRecord = true;
							break;
						}
					}
				}
			}
			
			if ($needRecord) {
				if (! in_array ( $file, array_keys ( $this->_coverageData ) )) {
					$this->_coverageData [$file] = $data;
				} else {
					foreach ( $data as $lineNum => $lineInfo ) {
						if (! in_array ( $lineNum, array_keys ( $this->_coverageData [$file] ) )) {
							$this->_coverageData [$file] [$lineNum] = $lineInfo;
                        } else {
                            if( is_array($lineInfo) && is_array($this->_coverageData[$file][$lineNum]) ){
                                foreach($lineInfo as $flag=>$flag_status){
                                    if ( ! in_array($flag, array_keys($this->_coverageData[$file][$lineNum])) ){
                                        $this->_coverageData[$file][$lineNum][$flag] = $flag_status;
                                    }else{
                                        if( ($flag_status==1) && ($this->_coverageData[$file][$lineNum][$flag]==0)){
                                            $this->_coverageData[$file][$lineNum][$flag] = 1;
                                        }elseif( ($flag_status==1) && ($this->_coverageData[$file][$lineNum][$flag]==2)){
                                            $this->_coverageData[$file][$lineNum][$flag] = 3;
                                        }elseif( ($flag_status==2) && ($this->_coverageData[$file][$lineNum][$flag]==0)){
                                            $this->_coverageData[$file][$lineNum][$flag] = 2;
                                        }elseif( ($flag_status==2) && ($this->_coverageData[$file][$lineNum][$flag]==1)){
                                            $this->_coverageData[$file][$lineNum][$flag] = 3;
                                        }elseif( ($flag_status==3) ){
                                            $this->_coverageData[$file][$lineNum][$flag] = 3;
                                        }
                                    }
                                }
                            }elseif( !is_array($lineInfo) && !is_array($this->_coverageData[$file][$lineNum]) ){
							    if ($lineInfo > 0 && !is_array($this->_coverageData[$file][$lineNum]) && $this->_coverageData [$file] [$lineNum] > 0) {
								    $this->_coverageData[$file][$lineNum] += $lineInfo;
							    } elseif ($lineInfo > 0 && !is_array($this->_coverageData[$file][$lineNum]) && $this->_coverageData [$file] [$lineNum] <= 0) {
								    $this->_coverageData[$file][$lineNum] = $lineInfo;
							    }
                            }
						}
					}
					ksort ( $this->_coverageData [$file] );
				}
			}
		}
	}
	
	public function backupRecord() {
		if ($handle = fopen ( $this->_backupLog, 'w' )) {
			$outputStr = json_encode ( $this->_coverageData );
			fwrite ( $handle, $outputStr . "\n" );
			fclose ( $handle );
		} else {
			throw new Exception ( "ERROR____: Backup Record Failed!" );
		}
	}
	
	protected function help() {
		echo "###################################### HELP INFO ########################################\n";
		echo "Coverage Report      : php " . basename(__FILE__) . "\n";
		echo "Diffcoverage Report  : php " . basename(__FILE__) . " -ddiff-code-coverage.xml -mconf_map.xml -icoverage_log -odiffcoverage " . "\n";
		echo "Options: \n";
		echo "  -h: help \n";
		echo "  -m: recommond xml to configure map rules (rule: from 'A' to 'B' ) \n";
		echo "  -d: recommond xml to configure phpunit diff whitelist \n";
		echo "  -f: recommond log file (json formated) to calculate \n";
		echo "  -o: recommond output coverage director \n";
		echo "  -t: recommond output coverage title \n";
		echo "  -b: recommond file to backup coverage data in one line \n";
	}

}

$test = new PHPCoverageCalculator ( true );
