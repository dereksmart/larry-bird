<?php
/*
Plugin Name: Larry Bird
Plugin URI: https://github.com/dereksmart/larry-bird
Description: Search WP (Formerly Jetpack's Omnisearch)
Version: 0.1
*/

if ( is_admin() ) {
	require_once( dirname(__FILE__) . '/class.larry-bird.php' );
}
