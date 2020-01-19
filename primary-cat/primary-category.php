<?php
    /**
     * Plugin Name: Primary Cat
     * Plugin URI: https://www.linkedin.com/in/gonzafernandez/
     * Description: Allows publishers to designate a primary category for posts.
     * Version: 1.0
     * Author: Gonzalo Federico Fernandez
     * Author URI: https://www.linkedin.com/in/gonzafernandez/
     **/
    
    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }
    
    define('PRIMARYCATEGORY_VERSION', "1.0");
    require_once "src/pc.class.php";
    
    $pc = WP_PrimaryCategory::get_instance();
    $pc->init();
    