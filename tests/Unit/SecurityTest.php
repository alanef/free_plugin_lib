<?php
namespace Fullworks_Free_Plugin_Lib\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Fullworks_Free_Plugin_Lib\Classes\Security;

class SecurityTest extends TestCase
{
	public function testRateLimitingBlocksAfterThreshold() {
		$attempts = 0;

		// Mock get_transient and set_transient
		global $mock_attempts;
		$mock_attempts = 0;

		function mock_get_transient($key) {
			global $mock_attempts;
			return $mock_attempts;
		}

		function mock_set_transient($key, $value) {
			global $mock_attempts;
			$mock_attempts = $value;
			return true;
		}

		$result1 = Security::check_rate_limit('test', 5, 3600);
		$this->assertTrue($result1);

		$mock_attempts = 5;
		$result2 = Security::check_rate_limit('test', 5, 3600);
		$this->assertFalse($result2);
	}

	public function testNonceVerification()
	{
		// Valid nonce
		$_REQUEST['test_nonce'] = 'valid_nonce';
		$this->assertTrue(Security::verify_nonce('test_nonce', 'test_action'));

		// Invalid nonce
		$_REQUEST['test_nonce'] = 'invalid_nonce';
		$this->assertFalse(Security::verify_nonce('test_nonce', 'test_action'));
	}

	public function testIPDetection()
	{
		$reflection = new \ReflectionClass(Security::class);
		$method = $reflection->getMethod('get_client_ip');
		$method->setAccessible(true);
		$security = new Security();

		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
		$this->assertEquals('192.168.1.1', $method->invoke($security));

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';
		$this->assertEquals('10.0.0.1', $method->invoke($security));

		$_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid-ip';
		$this->assertEquals('127.0.0.1', $method->invoke($security));
	}
}