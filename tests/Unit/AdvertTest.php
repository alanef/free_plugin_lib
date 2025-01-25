<?php
namespace Fullworks_Free_Plugin_Lib\Tests\Unit;

use Freemius;
use PHPUnit\Framework\TestCase;
use Fullworks_Free_Plugin_Lib\Classes\Advert;

class AdvertTest extends TestCase
{
	private $advert;

	protected function setUp(): void {
		$this->advert = new Advert();
	}

	public function testAdHiddenWhenPremiumInstalled() {
		global $fwantispam_fs;
		$fwantispam_fs = new Freemius();
		$fwantispam_fs->set_premium(true);

		ob_start();
		$this->advert->ad_display();
		$output = ob_get_clean();

		$this->assertEmpty($output);
	}

	public function testAdShownWhenPremiumNotInstalled() {
		global $fwantispam_fs;
		$fwantispam_fs = null;

		ob_start();
		$this->advert->ad_display();
		$output = ob_get_clean();

		$this->assertStringContainsString('fullworks-advert', $output);
		$this->assertStringContainsString('Anti-Spam Premium', $output);
		$this->assertStringContainsString('role="complementary"', $output);
	}
}