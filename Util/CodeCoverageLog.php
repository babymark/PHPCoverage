<?php

require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

abstract class PHPUnit_Util_CodeCoverageLog{

    protected static $codeCoverageLog = array();
    
    public static function appendCodeCoverageLog($data){
        foreach (array_keys($data) as $file) {
            if (PHPUnit_Util_Filter::isFiltered($file, FALSE)) {
                unset($data[$file]);
            }
        }
        if(is_array($data) && count($data)>0){
            foreach($data as $file => $lines){
                if ( ! in_array($file, array_keys(self::$codeCoverageLog))){
                    self::$codeCoverageLog[$file] = $lines;
                }else{
                    foreach($lines as $lineNum=>$lineInfo){
                        if ( ! in_array($lineNum, array_keys(self::$codeCoverageLog[$file])) ){
                            self::$codeCoverageLog[$file][$lineNum] = $lineInfo;
                        }else{
                            if( is_array($lineInfo) && is_array(self::$codeCoverageLog[$file][$lineNum]) ){
                                foreach($lineInfo as $flag=>$flag_status){
                                    if ( ! in_array($flag, array_keys(self::$codeCoverageLog[$file][$lineNum])) ){
                                        self::$codeCoverageLog[$file][$lineNum][$flag] = $flag_status;
                                    }else{
                                        if( ($flag_status==1) && (self::$codeCoverageLog[$file][$lineNum][$flag]==0)){
                                            self::$codeCoverageLog[$file][$lineNum][$flag] = 1;
                                        }elseif( ($flag_status==1) && (self::$codeCoverageLog[$file][$lineNum][$flag]==2)){
                                            self::$codeCoverageLog[$file][$lineNum][$flag] = 3;
                                        }elseif( ($flag_status==2) && (self::$codeCoverageLog[$file][$lineNum][$flag]==0)){
                                            self::$codeCoverageLog[$file][$lineNum][$flag] = 2;
                                        }elseif( ($flag_status==2) && (self::$codeCoverageLog[$file][$lineNum][$flag]==1)){
                                            self::$codeCoverageLog[$file][$lineNum][$flag] = 3;
                                        }elseif( ($flag_status==3) ){
                                            self::$codeCoverageLog[$file][$lineNum][$flag] = 3;
                                        }
                                    }
                                }
                            }elseif( !is_array($lineInfo) && !is_array(self::$codeCoverageLog[$file][$lineNum]) ){
                                if ( $lineInfo > 0 && !is_array(self::$codeCoverageLog[$file][$lineNum]) && self::$codeCoverageLog[$file][$lineNum] > 0 ){
                                    self::$codeCoverageLog[$file][$lineNum] += $lineInfo;
                                }elseif($lineInfo > 0 && !is_array(self::$codeCoverageLog[$file][$lineNum]) && self::$codeCoverageLog[$file][$lineNum] <= 0 ){
                                    self::$codeCoverageLog[$file][$lineNum] = $lineInfo;
                                }
                            }
                        }
                    }
                }
            }
            ksort(self::$codeCoverageLog[$file]);
            #var_dump(self::$codeCoverageLog); 
        }
    }
    
    public static function generateCodeCoverageLog(PHPUnit_Framework_TestResult $result, $name="codeCoverageLog"){
        $data = self::getCodeCoverageLog($result);
        if(empty($name)){
            throw new PHPUnit_Framework_Exception("\nERROR____: empty file name of codeCoverageLog");
        }elseif(empty($data)){
            throw new PHPUnit_Framework_Exception("\nWARNING____: empty data of codeCoverageLog");
        }else{
	    $dir = dirname($name);
	    if(!is_dir($dir)){
	    	mkdir($dir, 0775, true);
	    }
            if ($handle = fopen( $name, 'w')) {
                $outputStr = json_encode($data);
                fwrite($handle, $outputStr."\n");
                fclose($handle);
            }else{
                throw new PHPUnit_Framework_Exception("\nERROR____: fail to write codeCoverageLog");
            }
        }
    }
    
    public static function getCodeCoverageLog(PHPUnit_Framework_TestResult $result){
        /*
        foreach(PHPUnit_Util_Filter::$whitelistedFiles as $file){
            if( !isset(PHPUnit_Util_Filter::$coveredFiles[$file]) && !PHPUnit_Util_Filter::isFiltered($file, TRUE, TRUE) ){
                $uncoveredWhiteListFiles[$file] = true;
            } 
        }
        if(isset($uncoveredWhiteListFiles) && count($uncoveredWhiteListFiles)>0){
            foreach( $uncoveredWhiteListFiles as $uncoveredFile => $status ){
                if ($status && file_exists($uncoveredFile)) {
                    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
                    include_once $uncoveredFile;
                    $coverage = xdebug_get_code_coverage();
                    xdebug_stop_code_coverage();
                    foreach ($coverage as $fileName => $fileData) {
                        if($uncoveredWhiteListFiles[$fileName]){
                            foreach($fileData as $cov_line=>$cov_line_flag){
                                if($cov_line_flag>0){
                                    $fileData[$cov_line] = -1;
                                }
                            $uncoveredWhiteListFiles[$fileName] = false;
                            self::$codeCoverageLog[$fileName] = $fileData;
                            }
                        }
                    }
        
                }
            }
        }
         */

        $result->getCodeCoverageInformation();
        return self::$codeCoverageLog;
    }
    
    public static function clearCodeCoverageLog(){
        self::$codeCoverageLog = array();
    }



}
