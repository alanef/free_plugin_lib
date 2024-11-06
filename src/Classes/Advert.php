<?php

namespace Fullworks_Free_Plugin_Lib\Classes;

class Advert {
	public function ad_display() {
		if ( ! $this->is_advertised_plugin_installled() ) {
			?>
			<div class="fullworks-advert" role="complementary" aria-label="Premium Plugin Advertisement">
				<div class="fullworks-advert-content">
					<img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../Assets/images/anti-spam-01.png'); ?>"
						 alt="Anti-Spam Premium Plugin Features" 
						 class="fullworks-advert-image"
						 width="400"
						 height="400">
					<div class="fullworks-advert-text">
						<h3>Keep this FREE Plugin FREE</h3>
						<h3>By Supporting the Developer and Upgrading to Anti-Spam Premium</h3>
						<ul>
							<li>Advanced spam protection</li>
							<li>Autointegration to most Forms Packages</li>
							<li>Real-time threat detection</li>
							<li>Premium support</li>
							<li>FREE Trial</li>
						</ul>
						<a href="https://fullworksplugins.com/products/anti-spam/" 
						   class="button button-primary"
						   target="_blank"
						   rel="noopener noreferrer"
						   aria-label="Learn more about Anti-Spam Premium features">
							 Show your Support & Learn More
						</a>
					</div>
				</div>
			</div>
			<style>
				.fullworks-advert { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin: 20px 0; }
				.fullworks-advert-content { display: flex; align-items: center; gap: 20px; }
				.fullworks-advert-image { max-width: 400px; height: auto; }
				.fullworks-advert-text { flex: 1; }
				.fullworks-advert-text h3 { margin-top: 0; }
				.fullworks-advert-text ul { list-style-type: disc; margin-left: 20px; }
				@media (max-width: 782px) { .fullworks-advert-content { flex-direction: column; text-align: center; } }
			</style>
			<?php
		}
	}

	private function is_advertised_plugin_installled() {
		/** @var \Freemius $fwantispam_fs Freemius global object. */
		global $fwantispam_fs;
		if ( null === $fwantispam_fs ) {
			return false;
		}
		if ( $fwantispam_fs->can_use_premium_code() ) {
			return true;
		}
		return false;
	}

	public function advert_nag() {

	}

}
