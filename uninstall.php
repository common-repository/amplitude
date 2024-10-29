<?php

if (! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

delete_option( 'aampli_plg_options' );