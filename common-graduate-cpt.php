<?php
/**
 * Plugin Name: Common - Graduate GPT
 * Description: A custom post type for storing, displaying, and archiving program alumni spotlight entries, including headshots and information about their work. Originally designed for use on the English Department site for the Creative Writing MFA Graduates
 * Author: Mike W. Leavitt
 * Version: 0.0.1
 */

defined( 'ABSPATH' ) or die( "No direct access plzthx" );

// Declare useful constants
define( 'CAH_GRADUATE_CPT__PLUGIN_FILE', __FILE__ );
define( 'CAH_GRADUATE_CPT__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAH_GRADUATE_CPT__PLUGIN_URI', plugin_dir_url( __FILE__ ) );

// Includes
require_once 'includes/graduate-cpt-registrar.php';
require_once 'includes/graduate-cpt-templater.php';
require_once 'includes/graduate-cpt-shortcode.php';

// Register activation and deactivation hooks
register_activation_hook( __FILE__, function() {
    UCF\CAH\WordPress\Plugins\Common_Graduate_CPT\GraduateCPTRegistrar::register();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );

// Actions
add_action( 'init', [ "UCF\\CAH\\WordPress\\Plugins\\Common_Graduate_CPT\\GraduateCPTRegistrar", 'register' ], 10, 0 );
add_action( 'init', [ "UCF\\CAH\\WordPress\\Plugins\\Common_Graduate_CPT\\GraduateCPTRegistrar", 'setup_custom_columns' ], 10, 0 );
add_action( 'init', [ "UCF\\CAH\\WordPress\\Plugins\\Common_Graduate_CPT\\GraduateCPTTemplater", 'set' ], 10, 0 );
add_action( 'init', [ "UCF\\CAH\\WordPress\\Plugins\\Common_Graduate_CPT\\GraduateCPTShortcode", 'setup'], 10, 0 );
add_action( 'wp_insert_post_data', ["UCF\\CAH\\WordPress\\Plugins\\Common_Graduate_CPT\\GraduateCPTRegistrar", 'change_post_title'], 99, 1 );

?>