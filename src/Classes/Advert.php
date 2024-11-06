<?php

namespace Fullworks_Free_Plugin_Lib\Classes;

class Advert {
	public function ad_display() {
		if ( ! $this->is_advertised_plugin_installled() ) {
			echo "advertisement  for plugin";
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