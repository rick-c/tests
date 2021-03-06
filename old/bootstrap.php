<?php

/**
* Habari unit test bootstrap file
*
* How to use:
* Step 1: Create a symlink to the tests directory within the htdocs directory
* Step 2: Include this file at the beginning of a test
**/

if(!defined('HABARI_PATH')) {
	define('HABARI_PATH', dirname( dirname( __FILE__ ) ) );
}

if(!defined('UNIT_TEST')) {
	define('UNIT_TEST', true);
}
if(!defined('DEBUG')) {
	define('DEBUG', true);
}

if(!class_exists('UnitTestCase')):

class UnitTestCase
{
	static $run_all = false;
	public $messages = array();
	public $pass_count = 0;
	public $fail_count = 0;
	public $exception_count = 0;
	public $case_count = 0;
	private $exceptions = array();
	private $checks = array();
	private $asserted_exception = null;

	public function assert_true($value, $message = 'Assertion failed')
	{
		if($value !== true) {
			$this->messages[] = array($message, debug_backtrace());
			$this->fail_count++;
		}
		else {
			$this->pass_count++;
		}
	}

	public function assert_false($value, $message = 'Assertion failed')
	{
		if($value !== false) {
			$this->messages[] = array($message, debug_backtrace());
			$this->fail_count++;
		}
		else {
			$this->pass_count++;
		}
	}

	public function assert_equal($value1, $value2, $message = 'Assertion failed')
	{
		if($value1 != $value2) {
			$this->messages[] = array($message, debug_backtrace());
			$this->fail_count++;
		}
		else {
			$this->pass_count++;
		}
	}

	public function assert_identical($value1, $value2, $message = 'Assertion failed')
	{
		if($value1 !== $value2) {
			$this->messages[] = array($message, debug_backtrace());
			$this->fail_count++;
		}
		else {
			$this->pass_count++;
		}
	}

	public function assert_exception($exception = '', $message = 'Expected exception')
	{
		$this->asserted_exception = array($exception, $message);
	}

	public function check($checkval, $message = 'Expected check')
	{
		$this->checks[$checkval] = $message;
	}

	public function pass_check($checkval)
	{
		unset($this->checks[$checkval]);
	}

	public function named_test_filter( $function_name )
	{
		return preg_match('%^test_%', $function_name);
	}

	private final function pre_test()
	{
		$this->asserted_exceptions = array();
		$this->exceptions = array();
		$this->checks = array();
	}

	private final function post_test()
	{
		if(isset($this->asserted_exception)) {
			$this->fail_count++;
			echo '<div><em>Fail:</em> ' . $this->asserted_exception[1] . '<br/>' . $this->asserted_exception[0] . '</div>';
		}
		foreach($this->checks as $check => $message) {
			$this->fail_count++;
			echo '<div><em>Fail:</em> ' . $message . '</div>';
		}
	}

	public function run()
	{
		$methods = get_class_methods($this);
		$methods = array_filter($methods, array($this, 'named_test_filter'));
		$cases = 0;
		echo '<h1>' . get_class($this) . '</h1>';

		foreach($methods as $method) {
			$this->messages = array();

			echo '<h2>' . $method . '</h2>';

			$this->pre_test();
			if(method_exists($this, 'setup')) {
				$this->setup();
			}

			try {
				$this->$method();
			}
			catch(Exception $e) {
				if(strpos($e->getMessage(), $this->asserted_exception) !== false || get_class($e) == $this->asserted_exception[0]) {
					$this->pass_count++;
					$this->asserted_exception = null;
				}
				else {
					$this->exception_count++;
					$trace = $e->getTrace();
					$ary = current($trace);
					while( strpos($ary['file'], 'error.php') != false ) {
						$ary = next($trace);
					}
					$ary = current($trace);
					echo '<div><em>Exception '. get_class($e) .':</em> ' . $e->getMessage() . '<br/>' . $ary['file'] . ':' . $ary['line'] . '</div>';
					echo '<pre>' . print_r($trace, 1) . '</pre>';
				}
			}

			if(method_exists($this, 'teardown')) {
				$this->teardown();
			}
			$this->post_test();

			foreach($this->messages as $message) {
				echo '<div><em>Fail:</em> ' . $message[0] . '<br/>' . $message[1][0]['file'] . ':' . $message[1][0]['line'] . '</div>';
			}

			$this->case_count++;
		}

		echo "<div class=\"test complete\">{$this->case_count}/{$this->case_count} tests complete.  {$this->fail_count} failed assertions.  {$this->pass_count} passed assertions.  {$this->exception_count} exceptions.</div>";
	}

	public static function run_one($classname)
	{
		if(self::$run_all) {
			return;
		}
		$testobj = new $classname();
		echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>';

		$testobj->run();
		echo '</body></html>';
	}

	public static function run_all()
	{
		$pass_count = 0;
		$fail_count = 0;
		$exception_count = 0;
		$case_count = 0;

		self::$run_all = true;
		$classes = get_declared_classes();
		$classes = array_unique($classes);
		sort($classes);
		echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>';
		foreach($classes as $class) {
			$parents = class_parents($class, false);
			if(in_array('UnitTestCase', $parents)) {
				$obj = new $class();
				$obj->run();

				$pass_count += $obj->pass_count;
				$fail_count += $obj->fail_count;
				$exception_count += $obj->exception_count;
				$case_count += $obj->case_count;
			}
		}
		echo "<div class=\"all test complete\">{$case_count}/{$case_count} tests complete.  {$fail_count} failed assertions.  {$pass_count} passed assertions.  {$exception_count} exceptions.</div>";
		echo '</body></html>';
	}

	public static function run_dir($directory = null)
	{
		self::$run_all = true;
		if(!isset($directory)) {
			$directory = dirname(__FILE__);
		}
		$tests = glob($directory . '/test_*.php');
		foreach($tests as $test) {
			include($test);
		}
		self::run_all();
	}
}

include '../index.php';

endif;

?>
