<?php
namespace Fullworks_Free_Plugin_Lib\Tests\Unit;

use Fullworks_Free_Plugin_Lib\Classes\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
	private $email;

	protected function setUp(): void
	{
		$this->email = new Email('SWEGTS');
	}

	/**
	 * @dataProvider emailValidationProvider
	 */
	public function testHandleOptinSubmission($emailAddress, $expected)
	{
		$result = $this->email->handle_optin_submission($emailAddress);
		$this->assertEquals($expected, $result);
	}

	public function emailValidationProvider()
	{
		return [
			'valid email' => ['test@example.com', true],
			'invalid email' => ['not-an-email', false],
			'empty string' => ['', false],
			'too long email' => [str_repeat('a', 255) . '@example.com', false]
		];
	}
}