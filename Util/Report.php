<?php
defined ( 'PHPCOVERAGE_HOME' ) || define ( 'PHPCOVERAGE_HOME', realpath ( dirname ( __FILE__ ) ) . '/../' );

/**
 * PHPCoverage
 *
 * Copyright (c) 2002-2010, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Sebastian Bergmann nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Testing
 * @package PHPCoverage
 * @author Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link http://www.PHPCoverage.de/
 * @since File available since Release 3.0.0
 */

require_once (PHPCOVERAGE_HOME . 'Util/CodeCoverage.php');
require_once (PHPCOVERAGE_HOME . 'Util/Filter.php');
require_once (PHPCOVERAGE_HOME . 'Util/Filesystem.php');
require_once (PHPCOVERAGE_HOME . 'Util/Report/Node/Directory.php');
require_once (PHPCOVERAGE_HOME . 'Util/Report/Node/File.php');

PHPCoverage_Util_Filter::addFileToFilter ( __FILE__, 'PHPCoverage' );

/**
 *
 *
 * @category   Testing
 * @package    PHPCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 3.4.15
 * @link       http://www.PHPCoverage.de/
 * @since      Class available since Release 3.0.0
 */
abstract class PHPCoverage_Util_Report {
	public static $templatePath;
	
	/**
	 * Renders the report.
	 *
	 * @param $codeCoverageLineInformation      code coverage (line)	
	 * @param $codeCoverageBranchInformation    code coverage (branch)	
	 * @param $title string       	
	 * @param $target string       	
	 * @param $charset string       	
	 * @param $yui boolean       	
	 * @param $highlight boolean       	
	 * @param $lowUpperBound integer       	
	 * @param $highLowerBound integer       	
	 */
    public static function render($codeCoverageLineInformation, $codeCoverageBranchInformation=array(), $target = 'CoverageReport', $title = 'PHP Coverage Report', $charset = 'ISO-8859-1', $yui = TRUE, $highlight = FALSE, $lowUpperBound = 35, $highLowerBound = 70) {
        
        ini_set('memory_limit', '2048M');

		$target = PHPCoverage_Util_Filesystem::getDirectory ( $target );
		
		self::$templatePath = sprintf ( '%s%sReport%sTemplate%s', 

		dirname ( __FILE__ ), DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR );

		$files = self::formatCoverageData ( $codeCoverageLineInformation );
        PHPCoverage_Util_CodeCoverage::setBranchInformation($codeCoverageBranchInformation);
        $commonPath = PHPCoverage_Util_Filesystem::reducePaths ( $files );
		$items = self::buildDirectoryStructure ( $files );
		
		unset ( $codeCoverageLineInformation );
		
		$root = new PHPCoverage_Util_Report_Node_Directory ( $commonPath, NULL );
		
		unset ( $commonPath );
		self::addItems ( $root, $items, $files, $yui, $highlight );
		self::copyFiles ( $target );
		
		PHPCoverage_Util_CodeCoverage::clearSummary ();
		
		$root->render ( $target, $title, $charset, $lowUpperBound, $highLowerBound );
	}
	
	/**
	 *
	 * @param $root PHPCoverage_Util_Report_Node_Directory       	
	 * @param $items array       	
	 * @param $files array       	
	 * @param $yui boolean       	
	 * @param $highlight boolean       	
	 */
	protected static function addItems(PHPCoverage_Util_Report_Node_Directory $root, array $items, array $files, $yui, $highlight) {
		//echo "INFO____: Memory use: " . xdebug_memory_usage() . "\n";
		foreach ( $items as $key => $value ) {
			if (substr ( $key, - 2 ) == '/f') {
				try {
					$file = $root->addFile ( substr ( $key, 0, - 2 ), $value, $yui, $highlight );
				} 

				catch ( RuntimeException $e ) {
					continue;
				}
			} else {
				$child = $root->addDirectory ( $key );
				self::addItems ( $child, $value, $files, $yui, $highlight );
			}
		}
	}
	
	/**
	 * Builds an array representation of the directory structure.
	 *
	 * For instance,
	 *
	 * <code>
	 * Array
	 * (
	 * [Money.php] => Array
	 * (
	 * ...
	 * )
	 *
	 * [MoneyBag.php] => Array
	 * (
	 * ...
	 * )
	 * )
	 * </code>
	 *
	 * is transformed into
	 *
	 * <code>
	 * Array
	 * (
	 * [.] => Array
	 * (
	 * [Money.php] => Array
	 * (
	 * ...
	 * )
	 *
	 * [MoneyBag.php] => Array
	 * (
	 * ...
	 * )
	 * )
	 * )
	 * </code>
	 *
	 * @param $files array       	
	 * @return array
	 */
	protected static function buildDirectoryStructure($files) {
		$result = array ();
		
		foreach ( $files as $path => $file ) {
			$path = explode ( '/', $path );
			$pointer = &$result;
			$max = count ( $path );
			
			for($i = 0; $i < $max; $i ++) {
				if ($i == ($max - 1)) {
					$type = '/f';
				} else {
					$type = '';
				}
				
				$pointer = &$pointer [$path [$i] . $type];
			}
			
			$pointer = $file;
		}
		return $result;
	}
	
	/**
	 *
	 * @param $target string       	
	 */
	protected static function copyFiles($target) {
		$files = array ('butter.png', 'chameleon.png', 'close12_1.gif', 'container.css', 'container-min.js', 'glass.png', 'scarlet_red.png', 'snow.png', 'style.css', 'yahoo-dom-event.js' );
		
		foreach ( $files as $file ) {
			copy ( self::$templatePath . $file, $target . $file );
		}
	}
	
	protected static function formatCoverageData($data) {
        $res = array ();  
		foreach ( $data as $file => $lines ) {
            if (count ( $lines ) > 0) { 
				foreach ( $lines as $line => $flag ) {
					if ($flag >= 1) {
						for($i = 0; $i < $flag; $i ++) {
							$res [$file] [$line] [] = 'FLAG';
						}
					} elseif (! isset ( $res [$file] [$line] )) {
						$res [$file] [$line] = $flag;
					}
				}
			} else {
				// res[$file] = self::addFileInformation($file);
			}
		}
		return $res;
	}
	
	protected static function addFileInformation($file) {
		if (file_exists ( $file )) {
			xdebug_start_code_coverage ( XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE );
			include_once $file;
			$coverage = xdebug_get_code_coverage ();
			xdebug_stop_code_coverage ();
			return $coverage [$file];
		} else {
			return array ();
		}
	}
}
?>
