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
			'test_page',
			'Plugin Name'
		);
	}

	public function testConstructorInitializesProperties()
	{
		// Test that Main object is properly constructed
		$this->assertInstanceOf(Main::class, $this->main);
	}

	public function testSettingsLinkAdded()
	{
		$links = ['existing_link'];
		$result = $this->main->plugin_action_links($links);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
	}
}