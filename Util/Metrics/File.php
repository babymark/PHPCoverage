<?php
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
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
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
 * @category   Testing
 * @package    PHPCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.PHPCoverage.de/
 * @since      File available since Release 3.2.0
 */
defined ( 'PHPCOVERAGE_HOME' ) || define ( 'PHPCOVERAGE_HOME', realpath ( dirname ( __FILE__ ) ) . '/../../' );

require_once (PHPCOVERAGE_HOME . 'Util/File.php');
require_once (PHPCOVERAGE_HOME . 'Util/Filter.php');
require_once (PHPCOVERAGE_HOME . 'Util/Metrics.php');

PHPCoverage_Util_Filter::addFileToFilter ( __FILE__, 'PHPCoverage' );

/**
 * File-Level Metrics.
 *
 * @category Testing
 * @package PHPCoverage
 * @author Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: 3.4.15
 * @link http://www.PHPCoverage.de/
 * @since Class available since Release 3.2.0
 */
class PHPCoverage_Util_Metrics_File extends PHPCoverage_Util_Metrics {
	protected $coverage = 0;
	protected $loc = 0;
	protected $cloc = 0;
	protected $ncloc = 0;
	protected $locExecutable = 0;
	protected $locExecuted = 0;
	
	protected $filename;
	protected $classes = array ();
	protected $functions = array ();
	
	protected static $cache = array ();
	
	/**
	 * Constructor.
	 *
	 * @param $filename string       	
	 * @param $codeCoverage array       	
	 * @throws RuntimeException
	 */
	protected function __construct($filename, &$codeCoverage = array()) {
		if (! file_exists ( $filename )) {
			throw new RuntimeException ( sprintf ( 'File "%s" not found.', $filename ) );
		}
		
		$this->filename = $filename;
		
		foreach ( PHPCoverage_Util_File::countLines ( $this->filename ) as $name => $value ) {
			$this->$name = $value;
		}
		
		$this->setCoverage ( $codeCoverage );
		
		foreach ( PHPCoverage_Util_File::getClassesInFile ( $filename ) as $className => $class ) {
			$this->classes [$className] = PHPCoverage_Util_Metrics_Class::factory ( new ReflectionClass ( $className ), $codeCoverage );
		}
		
		foreach ( PHPCoverage_Util_File::getFunctionsInFile ( $filename ) as $functionName => $function ) {
			$this->functions [$functionName] = PHPCoverage_Util_Metrics_Function::factory ( new ReflectionFunction ( $functionName ), $codeCoverage );
		}
	}
	
	/**
	 * Factory.
	 *
	 * @param $filename string       	
	 * @param $codeCoverage array       	
	 * @return PHPCoverage_Util_Metrics_File
	 */
	public static function factory($filename, &$codeCoverage = array()) {
		if (! isset ( self::$cache [$filename] )) {
			self::$cache [$filename] = new PHPCoverage_Util_Metrics_File ( $filename, $codeCoverage );
		} 

		else if (! empty ( $codeCoverage ) && self::$cache [$filename]->getCoverage () == 0) {
			self::$cache [$filename]->setCoverage ( $codeCoverage );
		}
		
		return self::$cache [$filename];
	}
	
	/**
	 *
	 * @param $codeCoverage array       	
	 */
	public function setCoverage(array &$codeCoverage) {
		if (! empty ( $codeCoverage )) {
			$this->calculateCodeCoverage ( $codeCoverage );
			
			foreach ( $this->classes as $class ) {
				$class->setCoverage ( $codeCoverage );
			}
			
			foreach ( $this->functions as $function ) {
				$function->setCoverage ( $codeCoverage );
			}
		}
	}
	
	/**
	 * Returns the path to the file.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->filename;
	}
	
	/**
	 * Classes.
	 *
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}
	
	/**
	 * A class.
	 *
	 * @param $className string       	
	 * @return ReflectionClass
	 */
	public function getClass($className) {
		return $this->classes [$className];
	}
	
	/**
	 * Functions.
	 *
	 * @return array
	 */
	public function getFunctions() {
		return $this->functions;
	}
	
	/**
	 * A function.
	 *
	 * @param $functionName string       	
	 * @return ReflectionClass
	 */
	public function getFunction($functionName) {
		return $this->functions [$functionName];
	}
	
	/**
	 * Lines.
	 *
	 * @return array
	 */
	public function getLines() {
		return file ( $this->filename );
	}
	
	/**
	 * Tokens.
	 *
	 * @return array
	 */
	public function getTokens() {
		return token_get_all ( file_get_contents ( $this->filename ) );
	}
	
	/**
	 * Returns the Code Coverage for the file.
	 *
	 * @return float
	 */
	public function getCoverage() {
		return $this->coverage;
	}
	
	/**
	 * Lines of Code (LOC).
	 *
	 * @return int
	 */
	public function getLoc() {
		return $this->loc;
	}
	
	/**
	 * Executable Lines of Code (ELOC).
	 *
	 * @return int
	 */
	public function getLocExecutable() {
		return $this->locExecutable;
	}
	
	/**
	 * Executed Lines of Code.
	 *
	 * @return int
	 */
	public function getLocExecuted() {
		return $this->locExecuted;
	}
	
	/**
	 * Comment Lines of Code (CLOC).
	 *
	 * @return int
	 */
	public function getCloc() {
		return $this->cloc;
	}
	
	/**
	 * Non-Comment Lines of Code (NCLOC).
	 *
	 * @return int
	 */
	public function getNcloc() {
		return $this->ncloc;
	}
	
	/**
	 * Calculates the Code Coverage for the class.
	 *
	 * @param $codeCoverage array       	
	 */
	protected function calculateCodeCoverage(&$codeCoverage) {
		$statistics = PHPCoverage_Util_CodeCoverage::getStatistics ( $codeCoverage, $this->filename, 1, $this->loc );
		
		$this->coverage = $statistics ['coverage'];
		$this->loc = $statistics ['loc'];
		$this->locExecutable = $statistics ['locExecutable'];
		$this->locExecuted = $statistics ['locExecuted'];
	}
}
?>