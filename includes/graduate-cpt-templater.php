<?php
/**
 * Helper class to queue up and load the CPT templates automatically (can
 * still be overridden by local templates)
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

namespace UCF\CAH\WordPress\Plugins\Common_Graduate_CPT;

final class GraduateCPTTemplater
{
    // Prevents instantiation
    private function __construct() {}

    // Change these to the new type and plugin directory path
    private static $_type = "graduate";
    private static $_plugin_dir = CAH_GRADUATE_CPT__PLUGIN_DIR;

    /**
     * Sets up the template filter, so the post type loads the
     * correct template.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     *
     * @return void
     */
    public static function set()
    {
        add_filter( 'template_include', [ __CLASS__, 'add' ] );
    }


    /**
     * Intercept the post template, and replace instances of our
     * CPT with our custom template.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     *
     * @param string $template  The template that WP is planning to use for the CPT
     * @return void
     */
    public static function add( $template )
    {

        if( is_singular( self::$_type ) && stripos( $template, 'single-' . self::$_type . '.php' ) === false )
        {
            $template = self::$_plugin_dir . 'templates/single-' . self::$_type . '.php';
        }
        
        if( is_archive() && is_post_type_archive( self::$_type ) && stripos( $template, 'archive-' . self::$_type . '.php' ) === false )
        {
            $template = self::$_plugin_dir . 'templates/archive-' . self::$_type . '.php';
        }

        return $template;
    }
}
?>