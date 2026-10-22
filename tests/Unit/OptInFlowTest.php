<?php
namespace Fullworks_Free_Plugin_Lib\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Fullworks_Free_Plugin_Lib\Main;

/**
 * The opt-in status, setup notice and dismissal (PLUGINS_SMALL-6).
 */
class OptInFlowTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		ffpl_test_reset();
	}

	private function make_main($shortname, $page)
	{
		return new Main(
			$page . '/' . $page . '.php',
			'http://test.com/wp-admin/options-general.php?page=' . $page,
			$shortname,
			$page,
			ucwords(str_replace('-', ' ', $page))
		);
	}

	private function notice_html(Main $main)
	{
		ob_start();
		$main->maybe_show_setup_notice();
		return ob_get_clean();
	}

	/**
	 * Runs a handler that may end the request, returning what it responded with, or null if it returned.
	 */
	private function run_handler(callable $handler)
	{
		try {
			$handler();
		} catch (\FFPL_Test_Json_Response $response) {
			return $response;
		} catch (\FFPL_Test_Redirect $redirect) {
			return $redirect;
		}
		return null;
	}

	public function testDismissPersistsForUppercaseShortname()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');
		update_site_option('SUE_form_rendered', 'pending');

		// The notice's JS posts the shortname; sanitize_key() lowercases it
		$_POST['shortname'] = 'SUE';
		$response = $this->run_handler(array($main, 'handle_dismiss_notice'));

		$this->assertInstanceOf(\FFPL_Test_Json_Response::class, $response);
		$this->assertTrue($response->success);
		$this->assertTrue(get_user_meta(1, 'SUE_notice_dismissed', true));
		$this->assertSame('', $this->notice_html($main));
	}

	public function testDismissPersistsForHyphenatedShortname()
	{
		$main = $this->make_main('SSFGM-Free', 'simple-google-maps-short-code');
		update_site_option('SSFGM-Free_form_rendered', 'pending');

		$_POST['shortname'] = 'SSFGM-Free';
		$this->run_handler(array($main, 'handle_dismiss_notice'));

		$this->assertTrue(get_user_meta(1, 'SSFGM-Free_notice_dismissed', true));
	}

	public function testDismissForAnotherPluginIsLeftToThatPlugin()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');

		// Returning, rather than responding, lets the other plugin's handler run
		$_POST['shortname'] = 'SWEGTS';
		$this->assertNull($this->run_handler(array($main, 'handle_dismiss_notice')));
		$this->assertSame('', get_user_meta(1, 'SUE_notice_dismissed', true));
	}

	public function testDismissRejectsBadNonce()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');
		$GLOBALS['ffpl_test_ajax_nonce_valid'] = false;

		$_POST['shortname'] = 'SUE';
		$response = $this->run_handler(array($main, 'handle_dismiss_notice'));

		$this->assertFalse($response->success);
		$this->assertSame('', get_user_meta(1, 'SUE_notice_dismissed', true));
	}

	public function testPendingRedirectsToOptInScreen()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');
		update_site_option('SUE_form_rendered', 'pending');

		$_GET['page'] = 'stop-user-enumeration';
		$redirect = $this->run_handler(array($main, 'handle_optin_page'));

		$this->assertInstanceOf(\FFPL_Test_Redirect::class, $redirect);
		$this->assertStringContainsString('page=ffpl-opt-in-SUE', $redirect->location);
		$this->assertSame('rendering', get_site_option('SUE_form_rendered'));
	}

	public function testRenderingLeftWithoutChoiceShowsOptInScreenAgain()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');
		update_site_option('SUE_form_rendered', 'rendering');

		$_GET['page'] = 'stop-user-enumeration';
		$redirect = $this->run_handler(array($main, 'handle_optin_page'));

		$this->assertInstanceOf(\FFPL_Test_Redirect::class, $redirect);
		$this->assertStringContainsString('page=ffpl-opt-in-SUE', $redirect->location);
	}

	public function testDecidedStatusesDoNotRedirect()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');
		$_GET['page'] = 'stop-user-enumeration';

		foreach (array('optin', 'optout') as $status) {
			update_site_option('SUE_form_rendered', $status);
			$this->assertNull($this->run_handler(array($main, 'handle_optin_page')), $status);
		}
	}

	public function testNoticeLinksToOptInScreen()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');
		update_site_option('SUE_form_rendered', 'rendering');

		$html = $this->notice_html($main);

		$this->assertStringContainsString('href="http://test.com/wp-admin/options-general.php?page=ffpl-opt-in-SUE"', $html);
		$this->assertStringNotContainsString('visited settings', $html);
	}

	public function testExistingDecisionsAndDismissalsAreStillRead()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');

		// Sites that chose before this release must not be asked again
		update_site_option('SUE_form_rendered', 'optout');
		$this->assertSame('', $this->notice_html($main));

		update_site_option('SUE_form_rendered', 'pending');
		update_user_meta(1, 'SUE_notice_dismissed', true);
		$this->assertSame('', $this->notice_html($main));
	}

	public function testTwoPluginsKeepTheirOwnShortnames()
	{
		// Both plugins bundle the library, so they share one copy of the class
		$sue    = $this->make_main('SUE', 'stop-user-enumeration');
		$swegts = $this->make_main('SWEGTS', 'stop-wp-emails-going-to-spam');

		$sue->add_settings_page();
		update_site_option('SWEGTS_form_rendered', 'optout');

		$this->assertSame('pending', get_site_option('SUE_form_rendered'));
		$this->assertStringContainsString('data-shortname="SUE"', $this->notice_html($sue));
		$this->assertSame('', $this->notice_html($swegts));

		$_GET['page'] = 'stop-user-enumeration';
		$redirect = $this->run_handler(array($sue, 'handle_optin_page'));
		$this->assertStringContainsString('page=ffpl-opt-in-SUE', $redirect->location);

		$_POST['shortname'] = 'SUE';
		$this->assertNull($this->run_handler(array($swegts, 'handle_dismiss_notice')));
		$this->run_handler(array($sue, 'handle_dismiss_notice'));
		$this->assertTrue(get_user_meta(1, 'SUE_notice_dismissed', true));
		$this->assertSame('', get_user_meta(1, 'SWEGTS_notice_dismissed', true));
	}

	public function testOptInRequestForAnotherPluginIsLeftToThatPlugin()
	{
		$main = $this->make_main('SUE', 'stop-user-enumeration');
		update_site_option('SUE_form_rendered', 'rendering');

		$_POST['shortname'] = 'SWEGTS';
		$this->assertNull($this->run_handler(array($main, 'handle_optin_ajax')));
		$this->assertSame('rendering', get_site_option('SUE_form_rendered'));
	}

	public function testUninstallRemovesNamedPluginsStatus()
	{
		$this->make_main('SUE', 'stop-user-enumeration');
		$this->make_main('SWEGTS', 'stop-wp-emails-going-to-spam');
		update_site_option('SUE_form_rendered', 'optin');
		update_site_option('SWEGTS_form_rendered', 'optin');

		Main::plugin_uninstall('SUE');

		$this->assertFalse(get_site_option('SUE_form_rendered'));
		$this->assertSame('optin', get_site_option('SWEGTS_form_rendered'));
	}
}
