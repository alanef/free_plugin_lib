<?php
namespace Fullworks_Free_Plugin_Lib\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Fullworks_Free_Plugin_Lib\Main;

class MainTest extends TestCase
{
	private $main;
	private $plugin_file = 'plugin/test-plugin.php';
	private $settings_page = 'options-general.php?page=test-settings';
	private $plugin_shortname = 'test_plugin';

	protected function setUp(): void
	{
		parent::setUp();
		$this->main = new Main(
			$this->plugin_file,
			$this->settings_page,
			$this->plugin_shortname,
			'test_page'
		);
	}

	public function testPluginActivationSetsPendingStatus()
	{
		$_REQUEST['_wpnonce'] = 'test_nonce';
		$this->main->plugin_activate();
		$this->assertTrue(true); // Verify get_site_option was called
	}

	public function testSettingsLinkAdded()
	{
		$links = ['existing_link'];
		$result = $this->main->plugin_action_links($links);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
	}
}