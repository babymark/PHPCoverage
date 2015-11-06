<?php
defined ( 'PHPCOVERAGE_HOME' ) || define ( 'PHPCOVERAGE_HOME', realpath ( dirname ( __FILE__ ) ) . '/../../../' );

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
 * @since File available since Release 3.2.0
 */

require_once (PHPCOVERAGE_HOME . 'Util/Filter.php');
require_once (PHPCOVERAGE_HOME . 'Util/File.php');
require_once (PHPCOVERAGE_HOME . 'Util/Filesystem.php');
require_once (PHPCOVERAGE_HOME . 'Util/Template.php');
require_once (PHPCOVERAGE_HOME . 'Util/Report/Node.php');

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
 * @since      Class available since Release 3.2.0
 */
class PHPCoverage_Util_Report_Node_File extends PHPCoverage_Util_Report_Node {
	/**
	 *
	 * @var array
	 */
	protected $codeLines;
	
	/**
	 *
	 * @var array
	 */
	protected $codeLinesFillup = array ();
	
	/**
	 *
	 * @var array
	 */
	protected $executedLines;
	
	/**
	 *
	 * @var boolean
	 */
	protected $yui = TRUE;
	
	/**
	 *
	 * @var boolean
	 */
	protected $highlight = FALSE;
	
	/**
	 *
	 * @var integer
	 */
	protected $numExecutableLines = 0;
	
	/**
	 *
	 * @var integer
	 */
	protected $numExecutedLines = 0;
	
	/**
	 *
	 * @var array
	 */
	protected $classes = array ();
	
	/**
	 *
	 * @var integer
	 */
	protected $numClasses = 0;
	
	/**
	 *
	 * @var integer
	 */
	protected $numTestedClasses = 0;
	
	/**
	 *
	 * @var integer
	 */
	protected $numMethods = 0;
	
	/**
	 *
	 * @var integer
	 */
	protected $numTestedMethods = 0;

    /**
     * @var    integer
     */
    protected $numBranches = 0;

    /**
     * @var    integer
     */
    protected $numTestedBranches = 0;

    /**
     * @var branches information
     */
    protected $branchInformation = array();


    protected $fileTokens = null;

    protected $useBranchSplit = false;

    /**
     * @var the max number of branches could been shown in one line
     */
    protected $branchFlagMax = 10;


	/**
	 *
	 * @var string
	 */
	protected $yuiPanelJS = '';
	
	/**
	 *
	 * @var array
	 */
	protected $startLines = array ();
	
	/**
	 *
	 * @var array
	 */
	protected $endLines = array ();
	
	/**
	 * Constructor.
	 *
	 * @param $name string       	
	 * @param $parent PHPCoverage_Util_Report_Node       	
	 * @param $executedLines array       	
	 * @param $yui boolean       	
	 * @param $highlight boolean       	
	 * @throws RuntimeException
	 */
	public function __construct($name, PHPCoverage_Util_Report_Node $parent = NULL, array $executedLines, $yui = TRUE, $highlight = FALSE) {
		parent::__construct ( $name, $parent );
		
		$path = $this->getPath ();
		if (! file_exists ( $path )) {
			throw new Exception ( sprintf ( 'Path "%s" does not exist.', $path ) );
		}
		
		$this->executedLines = $executedLines;
		$this->highlight = $highlight;
		$this->yui = $yui;
		$this->codeLines = $this->loadFile ( $path );
        $this->branchInformation = PHPCoverage_Util_CodeCoverage::getBranchInformation($path);  

		$this->calculateStatistics ();
	}
	
	/**
	 * Returns the classes of this node.
	 *
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}
	
	/**
	 * Returns the number of executable lines.
	 *
	 * @return integer
	 */
	public function getNumExecutableLines() {
		return $this->numExecutableLines;
	}
	
	/**
	 * Returns the number of executed lines.
	 *
	 * @return integer
	 */
	public function getNumExecutedLines() {
		return $this->numExecutedLines;
	}
	
	/**
	 * Returns the number of classes.
	 *
	 * @return integer
	 */
	public function getNumClasses() {
		return $this->numClasses;
	}
	
	/**
	 * Returns the number of tested classes.
	 *
	 * @return integer
	 */
	public function getNumTestedClasses() {
		return $this->numTestedClasses;
	}
	
	/**
	 * Returns the number of methods.
	 *
	 * @return integer
	 */
	public function getNumMethods() {
		return $this->numMethods;
	}
	
	/**
	 * Returns the number of tested methods.
	 *
	 * @return integer
	 */
	public function getNumTestedMethods() {
		return $this->numTestedMethods;
	}

    /**
     * Returns the number of branches.
     *
     * @return integer
     */
    public function getNumBranches()
    {
        return $this->numBranches;
    }

    /**
     * Returns the number of tested branches.
     *
     * @return integer
     */
    public function getNumTestedBranches()
    {
        return $this->numTestedBranches;
    }


	/**
	 * Renders this node.
	 *
	 * @param $target string       	
	 * @param $title string       	
	 * @param $charset string       	
	 * @param $lowUpperBound integer       	
	 * @param $highLowerBound integer       	
	 */
	public function render($target, $title, $charset = 'ISO-8859-1', $lowUpperBound = 35, $highLowerBound = 70) {
		if ($this->yui) {
			$template = new PHPCoverage_Util_Template ( PHPCoverage_Util_Report::$templatePath . 'file.html' );
			
			$yuiTemplate = new PHPCoverage_Util_Template ( PHPCoverage_Util_Report::$templatePath . 'yui_item.js' );
		} else {
			$template = new PHPCoverage_Util_Template ( PHPCoverage_Util_Report::$templatePath . 'file_no_yui.html' );
		}
		
		$i = 1;
		$lines = '';
		$ignore = FALSE;
		
		foreach ( $this->codeLines as $line ) {
			if (strpos ( $line, '@codeCoverageIgnoreStart' ) !== FALSE) {
				$ignore = TRUE;
			} 

			else if (strpos ( $line, '@codeCoverageIgnoreEnd' ) !== FALSE) {
				$ignore = FALSE;
			}
			
			$css = '';
			
			if (! $ignore && isset ( $this->executedLines [$i] )) {
				$count = '';
				
				// Array: Line is executable and was executed.
				// count(Array) = Number of tests that hit this line.
				if (is_array ( $this->executedLines [$i] )) {
					$color = 'lineCov';
					$numTests = count ( $this->executedLines [$i] );
					$count = sprintf ( '%8d', $numTests );
					
					if ($this->yui) {
						$buffer = '';
						$testCSS = '';
						/*
						 * foreach ($this->executedLines[$i] as $test) { if
						 * (!isset($test->__liHtml)) { $test->__liHtml = ''; if
						 * ($test instanceof
						 * PHPCoverage_Framework_SelfDescribing) { $testName =
						 * $test->toString(); if ($test instanceof
						 * PHPCoverage_Framework_TestCase) { switch
						 * ($test->getStatus()) { case
						 * PHPCoverage_Runner_BaseTestRunner::STATUS_PASSED: {
						 * $testCSS = ' class=\"testPassed\"'; } break; case
						 * PHPCoverage_Runner_BaseTestRunner::STATUS_FAILURE: {
						 * $testCSS = ' class=\"testFailure\"'; } break; case
						 * PHPCoverage_Runner_BaseTestRunner::STATUS_ERROR: {
						 * $testCSS = ' class=\"testError\"'; } break; case
						 * PHPCoverage_Runner_BaseTestRunner::STATUS_INCOMPLETE:
						 * case
						 * PHPCoverage_Runner_BaseTestRunner::STATUS_SKIPPED: {
						 * $testCSS = ' class=\"testIncomplete\"'; } break;
						 * default: { $testCSS = ''; } } } } $test->__liHtml .=
						 * sprintf( '<li%s>%s</li>', $testCSS,
						 * addslashes(htmlspecialchars($testName)) ); } $buffer
						 * .= $test->__liHtml; }
						 */
						if ($numTests > 1) {
							$header = $numTests . ' tests cover';
						} else {
							$header = '1 test covers';
						}
						
						$header .= ' line ' . $i;
						
						$yuiTemplate->setVar ( array ('line' => $i, 'header' => $header, 'tests' => $buffer ), FALSE );
						
						$this->yuiPanelJS .= $yuiTemplate->render ();
					}
				} 				

				// -1: Line is executable and was not executed.
				else if ($this->executedLines [$i] == - 1) {
					$color = 'lineNoCov';
					$count = sprintf ( '%8d', 0 );
				} 				

				// -2: Line is dead code.
				else {
					$color = 'lineDeadCode';
					$count = '        ';
				}
				
                //$css = sprintf ( '<span class="%s">       %s : ', $color, $count );

                $css = sprintf(
                    '<span class="%s">  ##BRANCH_FLAG##  %s : ',
                    $color,
                    $count
                );
                $branch_flag = $this->formatBranchFlag($i);
                $css = str_replace("##BRANCH_FLAG##", $branch_flag, $css);

			}
			
			$fillup = array_shift ( $this->codeLinesFillup );
			
			if ($fillup > 0) {
				$line .= str_repeat ( ' ', $fillup );
			}

            /* original
            $lines .= sprintf ( 
                '<span class="lineNum" id="container%d"><a name="%d"></a><a href="#%d" id="line%d">%8d</a> </span>%s%s%s' . "\n", 
                $i, 
                $i, 
                $i, 
                $i, 
                $i, 
                ! empty ( $css ) ? $css : '                : ', 
                ! $this->highlight ? htmlspecialchars ( $line ) : $line, 
                ! empty ( $css ) ? '</span>' : '' 
            );
            */

            // add for branch   
            if($this->useBranchSplit && in_array($i, array_keys($this->branchInformation)) && count($this->branchInformation[$i])>1 ){
                $lines .= sprintf(
                    '<span class="lineNum" id="container%d"><a name="%d"></a><a href="#%d" id="line%d">%8d</a> </span>%s%s%s' . "\n",
                    $i,
                    $i,
                    $i,
                    $i,
                    $i,
                    #!empty($css) ? $css : '                : ',
                    !empty($css) ? $css : '  ' . $this->getBranchFlagMax() . '           : ',
                    !$this->highlight ? htmlspecialchars($line) : $line,
                    !empty($css) ? '</span>' : ''
                );
                $branch_lines_num = count($this->branchInformation[$i]);
                $next_branch_line = $line;
                for($current_branch=$branch_lines_num; $current_branch>0; $current_branch--){
                    if($current_branch>1){
                        $branch_position = PHPCoverage_Util_File::getFirstLogicPos($next_branch_line);
                        if($branch_position == -1){
                            break;
                        }
                        $branch_line = substr($next_branch_line, 0, $branch_position) . PHPCoverage_Util_File::getBlankSpace(strlen($line)-$branch_position);
                        $next_branch_line = PHPCoverage_Util_File::getBlankSpace($branch_position) . substr($next_branch_line, $branch_position);
                    }else{
                        $branch_line = $next_branch_line;
                    }

                    $branch_flag = $this->formatBranchFlag($i, count($this->branchInformation[$i])-$current_branch);
                    $branchColor = $this->formatBranchColor($i, count($this->branchInformation[$i])-$current_branch);
                    $branch_css = sprintf(
                        '<span class="%s">  ##BRANCH_FLAG##        : ',
                        $branchColor
                    );
                    $branch_css = str_replace("##BRANCH_FLAG##", $branch_flag, $branch_css);
                    $lines .= sprintf(
                        '<span class="lineNum" id="container%d"><a name="%d"></a><a href="#%d" id="line%d">        </a> </span>%s%s%s' . "\n",
                        $i,
                        $i,
                        $i,
                        $i,
                        #!empty($branch_css) ? $branch_css : '                : ',
                        !empty($branch_css) ? $branch_css : '  ' . $this->getBranchFlagMax() . '           : ',
                        !$this->highlight ? htmlspecialchars($branch_line) : $branch_line,
                        !empty($branch_css) ? '</span>' : ''
                    );
                }
            }else{
                $lines .= sprintf(
                    '<span class="lineNum" id="container%d"><a name="%d"></a><a href="#%d" id="line%d">%8d</a> </span>%s%s%s' . "\n",
                    $i,
                    $i,
                    $i,
                    $i,
                    $i,
                    #!empty($css) ? $css : '                : ',
                    !empty($css) ? $css : '  ' . $this->getBranchFlagMax() . '           : ',
                    !$this->highlight ? htmlspecialchars($line) : $line,
                    !empty($css) ? '</span>' : ''
                );
            }

			$i ++;
		}
		
		$items = '';
		
		foreach ( $this->classes as $className => $classData ) {
			// f ($classData['executedLines'] == $classData['executableLines'])
			// {
			if ($classData ['executedLines'] > 0) {
				$numTestedClasses = 1;
				$testedClassesPercent = 100;
			} else {
				$numTestedClasses = 0;
				$testedClassesPercent = 0;
			}
			
			$numTestedMethods = 0;
			$numMethods = count ( $classData ['methods'] );
			
			foreach ( $classData ['methods'] as $method ) {
				// f ($method['executedLines'] == $method['executableLines']) {
				if ($method ['executedLines'] > 0) {
					$numTestedMethods ++;
				}
			}
			
            $items .= $this->doRenderItem ( 
                array (
                    'name' => sprintf ( 
                        '<b><a href="#%d">%s</a></b>', 
			            $classData ['startLine'], 
                        $className 
                    ), 
                    'numClasses' => 1, 
                    'numTestedClasses' => $numTestedClasses, 
                    'testedClassesPercent' => sprintf ( '%01.2f', $testedClassesPercent ), 
                    'numMethods' => $numMethods, 
                    'numTestedMethods' => $numTestedMethods, 
                    'testedMethodsPercent' => $this->calculatePercent ( $numTestedMethods, $numMethods ), 
                    'numExecutableLines' => $classData ['executableLines'], 
                    'numExecutedLines' => $classData ['executedLines'], 
                    'executedLinesPercent' => $this->calculatePercent ( $classData ['executedLines'], $classData ['executableLines'] ) 
                ), 
                $lowUpperBound, 
                $highLowerBound 
            );
			
			foreach ( $classData ['methods'] as $methodName => $methodData ) {
				// f ($methodData['executedLines'] ==
				// $methodData['executableLines']) {
				if ($methodData ['executedLines'] > 0) {
					$numTestedMethods = 1;
					$testedMethodsPercent = 100;
				} else {
					$numTestedMethods = 0;
					$testedMethodsPercent = 0;
				}
				
                $items .= $this->doRenderItem ( 
                    array (
                        'name' => sprintf ( 
                            '&nbsp;<a href="#%d">%s</a>', 
                            $methodData ['startLine'], 
                            htmlspecialchars ( $methodData ['signature'] ) 
                        ), 
                        'numClasses' => '', 
                        'numTestedClasses' => '', 
                        'testedClassesPercent' => '', 
                        'numMethods' => 1, 
                        'numTestedMethods' => $numTestedMethods, 
                        'testedMethodsPercent' => sprintf ( '%01.2f', $testedMethodsPercent ), 
                        'numExecutableLines' => $methodData ['executableLines'], 
                        'numExecutedLines' => $methodData ['executedLines'], 
                        'executedLinesPercent' => $this->calculatePercent ( 
                            $methodData ['executedLines'], $methodData ['executableLines'] 
                        ) 
                    ), 
                    $lowUpperBound, 
                    $highLowerBound, 
                    'method_item.html' 
                );
			}
		}
		
		$this->setTemplateVars ( $template, $title, $charset );
		
        $template->setVar ( 
            array (
                'lines' => $lines, 
                'total_item' => $this->renderTotalItem ( $lowUpperBound, $highLowerBound, FALSE ), 
                'items' => $items, 
                'yuiPanelJS' => $this->yuiPanelJS 
            ) 
        );
		
		$cleanId = PHPCoverage_Util_Filesystem::getSafeFilename ( $this->getId () );
		$template->renderTo ( $target . $cleanId . '.html' );
		
		$this->yuiPanelJS = '';
		$this->executedLines = array ();
	}
	
	/**
	 * Calculates coverage statistics for the file.
	 */
	protected function calculateStatistics() {
		$this->processClasses ();
		$this->processFunctions ();
        $this->processBranches();

		$ignoreStart = - 1;
		$lineNumber = 1;
		
		foreach ( $this->codeLines as $line ) {
			if (isset ( $this->startLines [$lineNumber] )) {
				// Start line of a class.
				if (isset ( $this->startLines [$lineNumber] ['methods'] )) {
					$currentClass = &$this->startLines [$lineNumber];
				} 				

				// Start line of a method.
				else {
					$currentMethod = &$this->startLines [$lineNumber];
				}
			}
			
			if (strpos ( $line, '@codeCoverageIgnore' ) !== FALSE) {
				if (strpos ( $line, '@codeCoverageIgnoreStart' ) !== FALSE) {
					$ignoreStart = $lineNumber;
				} 

				else if (strpos ( $line, '@codeCoverageIgnoreEnd' ) !== FALSE) {
					$ignoreStart = - 1;
				}
			}
			
			if (isset ( $this->executedLines [$lineNumber] )) {
				// Array: Line is executable and was executed.
				if (is_array ( $this->executedLines [$lineNumber] )) {
					if (isset ( $currentClass )) {
						$currentClass ['executableLines'] ++;
						$currentClass ['executedLines'] ++;
					}
					
					if (isset ( $currentMethod )) {
						$currentMethod ['executableLines'] ++;
						$currentMethod ['executedLines'] ++;
					}
					
					$this->numExecutableLines ++;
					$this->numExecutedLines ++;
				} 				

				// -1: Line is executable and was not executed.
				else if ($this->executedLines [$lineNumber] == - 1) {
					if (isset ( $currentClass )) {
						$currentClass ['executableLines'] ++;
					}
					
					if (isset ( $currentMethod )) {
						$currentMethod ['executableLines'] ++;
					}
					
					$this->numExecutableLines ++;
					
					if ($ignoreStart != - 1 && $lineNumber > $ignoreStart) {
						if (isset ( $currentClass )) {
							$currentClass ['executedLines'] ++;
						}
						
						if (isset ( $currentMethod )) {
							$currentMethod ['executedLines'] ++;
						}
						
						$this->numExecutedLines ++;
					}
				}
			}
			
			if (isset ( $this->endLines [$lineNumber] )) {
				// End line of a class.
				if (isset ( $this->endLines [$lineNumber] ['methods'] )) {
					unset ( $currentClass );
				} 				

				// End line of a method.
				else {
					unset ( $currentMethod );
				}
			}
			
			$lineNumber ++;
		}
		
		foreach ( $this->classes as $className => $class ) {
			foreach ( $class ['methods'] as $method ) {
				// f ($method['executedLines'] == $method['executableLines']) {
				// f ($method['executedLines'] >0 || $method['executableLines']
				// == 0) {
				if ($method ['executedLines'] > 0) {
					$this->numTestedMethods ++;
				}
			}
			
			if ($className != '*') {
				// f ($class['executedLines'] == $class['executableLines']) {
				// f ($class['executedLines'] >0 || $class['executableLines'] ==
				// 0) {
				if ($class ['executedLines'] > 0) {
					$this->numTestedClasses ++;
				}
			}
		}
	}
	
	/**
	 *
	 * @author Aidan Lister <aidan@php.net>
	 * @author Sebastian Bergmann <sb@sebastian-bergmann.de>
	 * @param $file string       	
	 * @return array
	 */
	protected function loadFile($file) {
		$lines = explode ( "\n", str_replace ( "\t", '    ', file_get_contents ( $file ) ) );
		$result = array ();
		
		if (count ( $lines ) == 0) {
			return $result;
		}
		
		$lines = array_map ( 'rtrim', $lines );
		$linesLength = array_map ( 'strlen', $lines );
		$width = max ( $linesLength );
		
		foreach ( $linesLength as $line => $length ) {
			$this->codeLinesFillup [$line] = $width - $length;
		}
		
		if (! $this->highlight) {
			unset ( $lines [count ( $lines ) - 1] );
			return $lines;
		}
		
        //$tokens = token_get_all ( file_get_contents ( $file ) );
        $this->fileTokens = token_get_all(file_get_contents($file));

		$stringFlag = FALSE;
		$i = 0;
		$result [$i] = '';
		
		foreach ( $this->fileTokens as $j => $token ) {
			if (is_string ( $token )) {
				if ($token === '"' && $this->fileTokens [$j - 1] !== '\\') {
					$result [$i] .= sprintf ( '<span class="string">%s</span>', 

					htmlspecialchars ( $token ) );
					
					$stringFlag = ! $stringFlag;
				} else {
					$result [$i] .= sprintf ( '<span class="keyword">%s</span>', 

					htmlspecialchars ( $token ) );
				}
				
				continue;
			}
			
			list ( $token, $value ) = $token;
			
			$value = str_replace ( array ("\t", ' ' ), array ('&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;' ), htmlspecialchars ( $value ) );
			
			if ($value === "\n") {
				$result [++ $i] = '';
			} else {
				$lines = explode ( "\n", $value );
				
				foreach ( $lines as $jj => $line ) {
					$line = trim ( $line );
					
					if ($line !== '') {
						if ($stringFlag) {
							$colour = 'string';
						} else {
							switch ($token) {
								case T_INLINE_HTML :
									{
										$colour = 'html';
									}
									break;
								
								case T_COMMENT :
								case T_DOC_COMMENT :
									{
										$colour = 'comment';
									}
									break;
								
								case T_ABSTRACT :
								case T_ARRAY :
								case T_ARRAY_CAST :
								case T_AS :
								case T_BOOLEAN_AND :
								case T_BOOLEAN_OR :
								case T_BOOL_CAST :
								case T_BREAK :
								case T_CASE :
								case T_CATCH :
								case T_CLASS :
								case T_CLONE :
								case T_CONCAT_EQUAL :
								case T_CONTINUE :
								case T_DEFAULT :
								case T_DOUBLE_ARROW :
								case T_DOUBLE_CAST :
								case T_ECHO :
								case T_ELSE :
								case T_ELSEIF :
								case T_EMPTY :
								case T_ENDDECLARE :
								case T_ENDFOR :
								case T_ENDFOREACH :
								case T_ENDIF :
								case T_ENDSWITCH :
								case T_ENDWHILE :
								case T_END_HEREDOC :
								case T_EXIT :
								case T_EXTENDS :
								case T_FINAL :
								case T_FOREACH :
								case T_FUNCTION :
								case T_GLOBAL :
								case T_IF :
								case T_INC :
								case T_INCLUDE :
								case T_INCLUDE_ONCE :
								case T_INSTANCEOF :
								case T_INT_CAST :
								case T_ISSET :
								case T_IS_EQUAL :
								case T_IS_IDENTICAL :
								case T_IS_NOT_IDENTICAL :
								case T_IS_SMALLER_OR_EQUAL :
								case T_NEW :
								case T_OBJECT_CAST :
								case T_OBJECT_OPERATOR :
								case T_PAAMAYIM_NEKUDOTAYIM :
								case T_PRIVATE :
								case T_PROTECTED :
								case T_PUBLIC :
								case T_REQUIRE :
								case T_REQUIRE_ONCE :
								case T_RETURN :
								case T_SL :
								case T_SL_EQUAL :
								case T_SR :
								case T_SR_EQUAL :
								case T_START_HEREDOC :
								case T_STATIC :
								case T_STRING_CAST :
								case T_THROW :
								case T_TRY :
								case T_UNSET_CAST :
								case T_VAR :
								case T_WHILE :
									{
										$colour = 'keyword';
									}
									break;
								
								default :
									{
										$colour = 'default';
									}
							}
						}
						
						$result [$i] .= sprintf ( '<span class="%s">%s</span>', 

						$colour, $line );
					}
					
					if (isset ( $lines [$jj + 1] )) {
						$result [++ $i] = '';
					}
				}
			}
		}
		
		unset ( $result [count ( $result ) - 1] );
		
		return $result;
	}
	
	protected function processClasses() {
		$classes = PHPCoverage_Util_File::getClassesInFile ( $this->getPath () );
		
		foreach ( $classes as $className => $class ) {
			$this->classes [$className] = array (
                'methods' => array (), 
                'startLine' => $class ['startLine'], 
                'executableLines' => 0, 
                'executedLines' => 0 
            );
			
			$this->startLines [$class ['startLine']] = &$this->classes [$className];
			$this->endLines [$class ['endLine']] = &$this->classes [$className];
			
			foreach ( $class ['methods'] as $methodName => $method ) {
				$this->classes [$className] ['methods'] [$methodName] = array (
                    'signature' => $method ['signature'], 
                    'startLine' => $method ['startLine'], 
                    'executableLines' => 0, 
                    'executedLines' => 0 
                );
				
				$this->startLines [$method ['startLine']] = &$this->classes [$className] ['methods'] [$methodName];
				$this->endLines [$method ['endLine']] = &$this->classes [$className] ['methods'] [$methodName];
				
				$this->numMethods ++;
			}
			
			$this->numClasses ++;
		}
	}
	
	protected function processFunctions() {
		$functions = PHPCoverage_Util_File::getFunctionsInFile ( $this->getPath () );
		
		if (count ( $functions ) > 0 && ! isset ( $this->classes ['*'] )) {
			$this->classes ['*'] = array (
                'methods' => array (), 
                'startLine' => 0, 
                'executableLines' => 0, 
                'executedLines' => 0 
            );
		}
		
		foreach ( $functions as $functionName => $function ) {
			$this->classes ['*'] ['methods'] [$functionName] = array (
                'signature' => $function ['signature'], 
                'startLine' => $function ['startLine'], 
                'executableLines' => 0, 
                'executedLines' => 0 
            );
			
			$this->startLines [$function ['startLine']] = &$this->classes ['*'] ['methods'] [$functionName];
			$this->endLines [$function ['endLine']] = &$this->classes ['*'] ['methods'] [$functionName];
			
			$this->numMethods ++;
		}
    }


    protected function processBranches(){
        foreach( $this->branchInformation as $line=>$flags ){
            if( is_array($flags) ){
                foreach($flags as $flag=>$flag_status){
                    $this->numBranches += 2;
                    if($flag_status == 3){
                        $this->numTestedBranches +=2;
                    }elseif($flag_status == 2){
                        $this->numTestedBranches +=1;
                    }elseif($flag_status == 1){
                        $this->numTestedBranches +=1;
                    }
                }
                #tbd condition cover when branches > 1 as condition
                #if(count($flags)>1){
                #
                #}
            }
        }
    }
    
    protected function getBranchFlagMax(){
        $branch_flag = '';
        $branch_flag_unit = ' ';
        for($i=0; $i<$this->branchFlagMax; $i++){
            $branch_flag .= ' ' . $branch_flag_unit;
        }
        return $branch_flag;
    }
    
    protected function formatBranchFlag($lineNum, $flagNum = null){
        $branch_flag = '';
        $branch_flag_unit = ' ';
        if(empty($this->branchInformation[$lineNum])){
            $branch_flag .= $this->getBranchFlagMax();
        }elseif( ($flagNum !== null) && ((int)$flagNum >= count($this->branchInformation[$lineNum])) ){
            $branch_flag .= $this->getBranchFlagMax();
        }else{
            if( count($this->branchInformation[$lineNum]) >= $this->branchFlagMax ){
                //warning
                $max = $this->branchFlagMax;
            }else{
                $max = count($this->branchInformation[$lineNum]);
                for($i=$max; $i<$this->branchFlagMax; $i++){
                    $branch_flag .= ' ' . $branch_flag_unit;
                }
            }
            if($flagNum === null){
                foreach( array_values($this->branchInformation[$lineNum]) as $value){
                    if($value == 1){
                        $branch_flag .= (strlen(trim($branch_flag)) == 0)? " T"  : ":T" ;
                    }elseif($value == 2){
                        $branch_flag .= (strlen(trim($branch_flag)) == 0)? " F"  : ":F" ;
                    }elseif($value == 3){
                        $branch_flag .= (strlen(trim($branch_flag)) == 0)? " B"  : ":B" ;
                    }elseif($value == 0){
                        $branch_flag .= (strlen(trim($branch_flag)) == 0)? " N"  : ":N" ;
                    }else{
                        $branch_flag .= " " . $branch_flag_unit;
                    }
                }
            }else{
                $n = 0;
                foreach( array_values($this->branchInformation[$lineNum]) as $value){
                    if($n == (int)$flagNum){
                        if($value == 1){
                            $flag_status = "T";
                        }elseif($value == 2){
                            $flag_status = "F";
                        }elseif($value == 3){
                            $flag_status = "B";
                        }elseif($value == 0){
                            $flag_status = "N";
                        }else{
                            $flag_status = " ";
                        }
                    }else{
                        $branch_flag .= " " . $branch_flag_unit;
                    }
                    $n++;
                }
                $branch_flag .= "--->" . $flag_status;
            }
        }
        return $branch_flag;
    }
                            
    protected function formatBranchColor($lineNum, $flagNum){
        $color = 'branchNone';
        if(!empty($this->branchInformation[$lineNum])
                && (int)$flagNum >=0
                && ((int)$flagNum < count($this->branchInformation[$lineNum]))
        ){
            $n = 0;
            foreach( array_values($this->branchInformation[$lineNum]) as $value){
                if($n == (int)$flagNum){
                    if($value == 1){
                        $color = 'branchTrue';
                    }elseif($value == 2){
                        $color = 'branchFalse';
                    }elseif($value == 3){
                        $color = 'branchBoth';
                    }elseif($value == 0){
                        $color = 'branchNone';
                    }
                }
                $n++;
            }
        }
        return $color;
    }                   


}

?>
