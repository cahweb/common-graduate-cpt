<?php
/**
 * A static helper class used to register the custom post type.
 *
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

namespace UCF\CAH\WordPress\Plugins\Common_Graduate_CPT;

final class GraduateCPTRegistrar
{
    // Prevents instantiation
    private function __construct()
    {}

    private static $_text_domain;

    // Fill in your desired post type slug here.
    private static $_type = 'graduate';


    // Public Methods

    /**
     * Registers the Graduate CPT and sets related editor actions.
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @return void
     */
    public static function register()
    {
        // CPT labels
        $labels = apply_filters('spa_studio_cpt_labels', [
            'singular'    => 'Graduate',
            'plural'      => 'Graduates',
            'text_domain' => 'cah_' . self::$_type . '_cpt',
        ]);

        // Registering the post type with WP
        register_post_type( self::$_type, self::_args( $labels ) );

        // Add our new metabox to the editor
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_metabox' ], 10, 0 );

        // Point WP to our custom save function, so we can
        // store the new post metadata.
        add_action( 'save_post_' . self::$_type, [ __CLASS__, 'save' ] );
    }


    /**
     * Registers any extra metaboxes we'll need.
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @return void
     */
    public static function register_metabox()
    {
        // The arguments here are:
        //      - the name of the metabox
        //      - the box's title in the editor
        //      - function to call for HTML markup
        //      - the post type to add the box for
        //      - situations to show the box in
        //      - priority for box display
        add_meta_box(
            'grad-info',
            'Graduate Information',
            [ __CLASS__, 'build' ]
        );
    }


    /**
     * Builds the HTML markup for the new metabox.
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @return void
     */
    public static function build()
    {
        global $post;

        if( !is_object( $post ) ) return;

        // Get the metadata
        $meta = maybe_unserialize( get_post_meta( $post->ID, 'graduate-info', true ) );

        if( !$meta ) {
            $meta = [];
        }

        // Start an output buffer to build the HTML. Putting it in a table like this is
        // the WordPress standard. We fill values back in where available.
        ob_start();
        ?>
        <div class="wrap">
            <table id="grad-info-table">
                <tr>
                    <td>
                        <label for="fname">First Name: </label>
                    </td>
                    <td>
                        <input type="text" id="fname" name="fname" value="<?= self::_get_value( 'fname', $meta ); ?>">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lname">Last Name: </label>
                    </td>
                    <td>
                        <input type="text" id="lname" name="lname" value="<?= self::_get_value( 'lname', $meta ); ?>">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="semester">Graduation Semester: </label>
                    </td>
                    <?php $semester = self::_get_value( 'semester', $meta ); ?>
                    <td>
                        <select id="semester" name="semester">
                        <?php foreach( ['-- Select One --' => '', 'Spring' => 'spring', 'Summer' => 'summer', 'Fall' => 'fall' ] as $label => $value ) : ?>
                            <option value="<?= $value ?>"<?= $value === $semester ? " selected" : "" ?>><?= $label ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="year">Graduation Year: </label>
                    </td>
                    <td>
                        <input type="number" id="year" name="year" maxlength="4" value="<?= self::_get_value( 'year', $meta ); ?>">
                    </td>
                </tr>
            </table>
        </div>
        <?php
        // Echo the buffered HTML
        echo ob_get_clean();
    }


    /**
     * Saves our new metadata whenever save_post runs for this
     * post type.
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @return void
     */
    public static function save()
    {
        global $post;

        if ( !is_object( $post ) ) return;

        // Create an array to hold the metadata
        $meta = [];

        // List the field names we're looking for
        $keys = [
            'fname',
            'lname',
            'semester',
            'year',
        ];

        // Loop through and add them to the metadata array, as well as
        // to their own meta field (for sorting posts in queries later on).
        foreach( $keys as $field )
        {
            if( isset( $_POST[ $field ] ) )
            {
                $meta[$field] = $_POST[$field];

                // Storing the fields individually for sorting purposes
                update_post_meta( $post->ID, "graduate-$field", $_POST[$field] );
            }
        }

        // Serialize the meta array
        $meta_serialized = serialize( $meta );

        // Storing the serialized array for quicker access to all the data at once.
        update_post_meta( $post->ID, 'graduate-info', $meta_serialized );
    }


    /**
     * Adds all the filters and actions that will establish our custom columns for the CPT
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     * 
     * @return void
     */
    public static function setup_custom_columns()
    {
        add_filter( 'manage_graduate_posts_columns', [ __CLASS__, 'add_columns' ] );
        add_action( 'manage_graduate_posts_custom_column', [ __CLASS__, 'custom_columns'], 10, 2 );
        add_action( 'manage_edit-graduate_sortable_columns', [ __CLASS__, 'custom_columns_sortable'] );
        add_action( 'pre_get_posts', [ __CLASS__, 'custom_columns_orderby'] );
    }


    /**
     * Gets rid of the "Title" column that we're not using and adds all our columns in the right order.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     * 
     * @param array $columns  The list of columns and their labels
     * 
     * @return array $columns  The updated list of columns and labels
     */
    public static function add_columns( array $columns ) : array
    {
        unset( $columns['title'] );

        $new_columns = [
            'lname' => __( 'Last Name', 'cah_graduate_cpt' ),
            'fname' => __( 'First Name', 'cah_graduate_cpt' ),
            'semester' => __( 'Semester', 'cah_graduate_cpt' ),
            'year' => __( 'Year', 'cah_graduate_cpt' ),
            'date' => $columns['date'],
        ];

        return $new_columns;
    }


    /**
     * Populates the values of our custom columns. Echoes the result.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     * 
     * @param string $column  The slug of our custom column
     * @param int $post_id  The ID of the post, for grabbing the metadata.
     * 
     * @return void
     */
    public static function custom_columns( string $column, $post_id )
    {
        $meta = get_post_meta( $post_id, "graduate-$column", true );

        echo ucfirst( $meta );
    }


    /**
     * Sets the custom orderby parameters for the meta values, so we can make these
     * custom columns sortable.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     * 
     * @param WP_Query $query  The WordPress post query we'll be modifying
     * 
     * @return void
     */
    public static function custom_columns_orderby( $query )
    {
        if( !is_admin() ) return;

        $orderby = $query->get( 'orderby' );

        $key = "";

        if( in_array( $orderby, [ 'lname', 'fname', 'semester' ] ) )
        {
            $query->set( 'meta_key', "graduate-$orderby" );
            $query->set( 'orderby', 'meta_value' );
        }
        else if( 'year' === $orderby )
        {
            $query->set( 'meta_key', "graduate-$orderby" );
            $query->set( 'orderby', 'meta_value_num' );
        }
    }

    
    /**
     * Makes our custom columns sortable
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     * 
     * @param array $columns  The list of sortable columns and their orderby keys
     * 
     * @return array $columns  The updated list of sortable columns
     */
    public static function custom_columns_sortable( array $columns ) : array
    {
        $columns['lname'] = 'lname';
        $columns['fname'] = 'fname';
        $columns['semester'] = 'semester';
        $columns['year'] = 'year';

        return $columns;
    }


    public static function change_post_title( $data )
    {
        // We're only interested in posts of this type
        if( self::$_type === $data['post_type'] )
        {
            // Change the title
            $data['post_title'] = 'Graduate Spotlight';
        }

        return $data;
    }


    // Private Methods

    /**
     * Creates, filters, and returns the array of arguments to be
     * passed to register_post_type() in
     * CAH_SPAStudioCPTRegistrar::register(), above.
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @param array $labels  An array of labels, defined in register(), which contain the singular label, plural label, and text domain for the CPT.
     *
     * @return array
     */
    private static function _args(array $labels): array
    {
        $singular    = $labels['singular'];
        $plural      = $labels['plural'];
        $text_domain = $labels['text_domain'];

        // Change any options you want here.
        return apply_filters( 'spa_' . self::$_type . '_cpt_args', [
            'label'               => __( $singular, $text_domain),
            'description'         => __( $plural, $text_domain),
            'labels'              => self::_labels($singular, $plural, $text_domain),
            'supports'            => ['thumbnail', 'editor', 'custom-fields', 'page-attributes', 'post-formats'],
            'taxonomies'          => self::_taxonomies(),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-buddicons-buddypress-logo',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'query_var'           => false,
        ]);
    }


    /**
     * Creates the full array of labels for our CPT, which is passed as part
     * of the $args array to register_post_type().
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @param string $singular      The singular label for the CPT.
     * @param string $plural        The plural label for the CPT.
     * @param string $text_domain   The text domain for the CPT.
     *
     * @return array
     */
    private static function _labels(string $singular, string $plural, string $text_domain): array
    {

        self::$_text_domain = $text_domain;

        return [
            'name'                  => self::_wpstr($plural, 'Post Type General Name'),
            'singular_name'         => self::_wpstr($singular, 'Post Type Singular Name'),
            'menu_name'             => self::_wpstr($plural),
            'name_admin_bar'        => self::_wpstr($singular),
            'archives'              => self::_wpstr("$plural Archives"),
            'parent_item_colon'     => self::_wpstr("Parent $singular:"),
            'all_items'             => self::_wpstr("All $plural"),
            'add_new_item'          => self::_wpstr("Add New $singular"),
            'add_new'               => self::_wpstr("Add New"),
            'new_item'              => self::_wpstr("New $singular"),
            'edit_item'             => self::_wpstr("Edit $singular Information"),
            'update_item'           => self::_wpstr("Update $singular"),
            'view_item'             => self::_wpstr("View $singular"),
            'delete_item'           => self::_wpstr("Delete $singular"),
            'search_items'          => self::_wpstr("Search $plural"),
            'not_found'             => self::_wpstr("$singular Not Found"),
            'not_found_in_trash'    => self::_wpstr("$singular Not Found in Trash"),
            'featured_image'        => self::_wpstr("$singular Photo"),
            'set_featured_image'    => self::_wpstr("Set $singular Photo"),
            'remove_featured_image' => self::_wpstr("Remove $singular Photo"),
            'use_featured_image'    => self::_wpstr("Use as $singular Photo"),
            'insert_into_item'      => self::_wpstr("Insert into $singular"),
            'uploaded_to_this_item' => self::_wpstr("Uploaded to this $singular"),
            'items_list'            => self::_wpstr("$plural List"),
            'items_list_navigation' => self::_wpstr("$plural List Navigation"),
            'filter_items_list'     => self::_wpstr("Filter $plural List"),
        ];
    }


    /**
     * Filters the taxonomies, to be passed to _args(), above.
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @return array
     */
    private static function _taxonomies(): array
    {
        $tax = array();
        $tax = apply_filters( "spa_" . self::$_type . "_cpt_taxonomies", $tax);

        foreach ($tax as $t) {
            if (!taxonomy_exists($t)) {
                unset($tax[$t]);
            }
        }

        return $tax;
    }


    /**
     * A little helper function to generate a WP localized string.
     * This seemed cleaner than typing "$text_domain" over and
     * over again.
     *
     * @author Mike W. Leavitt
     * @since 0.1.0
     *
     * @param string $label  The label we're trying to localize.
     * @param string $context  The context, in case we're calling the _x() function.
     *
     * @return string
     */
    private static function _wpstr(string $label, string $context = null): string
    {

        if ($context) {
            return _x($label, $context, self::$_text_domain);
        }
        return __($label, self::$_text_domain);
    }


    /**
     * Gets a value from the metadata array, if it exists, or returns an empty string.
     * 
     * @author Mike W. Leavitt
     * @since 1.0.0
     * 
     * @param string $key  The meta key we're looking for
     * @param array $meta  The array of metadata
     * 
     * @return string  The value of that particular piece of metadata (or an empty string)
     */
    private static function _get_value( string $key, array $meta ): string
    {
        if( isset( $meta[$key] ) )
            return $meta[$key];
        else
            return '';
    }
}
