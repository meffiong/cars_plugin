<?php
/*
Plugin Name: Some Spider Cars
Plugin URI: http://mandelaeffiong.com
Description: A cool plugin for listing cars!
Version: 1.0
Author: Mandela Effiong
Author URI: http://mandelaeffiong.com
License: GPL2
*/

#####################################################################

/* Copyright 2019 Mandela Effiong (email : mandelaeffiong@gmail.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-cars.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-cars-template.php' );


register_activation_hook( __FILE__, array( 'PageTemplater','get_instance' ) );

SomeSpiderCars::get_instance();