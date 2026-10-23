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

	/**
	 * @dataProvider freeShortnameProvider
	 */
	public function testFreeShortnamesSubscribeUnderTheirPluginId($shortname, $plugin_id)
	{
		$GLOBALS['ffpl_test_last_post'] = null;
		$email = new Email($shortname);

		$this->assertTrue($email->handle_optin_submission('test@example.com'));
		$body = json_decode($GLOBALS['ffpl_test_last_post']['args']['body'], true);
		$this->assertSame($plugin_id, $body['plugin_id']);
	}

	public function freeShortnameProvider()
	{
		return [
			'SSFGM-Free' => ['SSFGM-Free', 'ssfgm'],
			'MMT-Free'   => ['MMT-Free', 'mmt'],
			'LHF-Free'   => ['LHF-Free', 'lhf'],
			'RSHFD-Free' => ['RSHFD-Free', 'rshfd'],
			'exact key'  => ['CSCF', 'cfcs'],
		];
	}

	public function testUnknownShortnameSendsNothing()
	{
		// Negative control: a shortname with no map entry, with or without the suffix
		$GLOBALS['ffpl_test_last_post'] = null;
		foreach (array('NOPE', 'NOPE-Free') as $shortname) {
			$this->assertFalse((new Email($shortname))->handle_optin_submission('test@example.com'), $shortname);
		}
		$this->assertNull($GLOBALS['ffpl_test_last_post']);
	}
}
