<?php
/**
 * Sets up the shortcode that will allow you to pull certain groups of graduates
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

namespace UCF\CAH\WordPress\Plugins\Common_Graduate_CPT;

final class GraduateCPTShortcode
{
    private function __construct() {} // Prevents instantiation

    public static function setup()
    {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_scripts' ], 5, 0 );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_enqueue_scripts' ], 10, 0 );
        add_shortcode( 'graduate-list', [ __CLASS__, 'shortcode' ] );
    }


    public static function register_scripts()
    {
        wp_register_style(
            'graduate-cpt-style',
            CAH_GRADUATE_CPT__PLUGIN_URI . "/css/graduate-cpt-style.css",
            [],
            filemtime( CAH_GRADUATE_CPT__PLUGIN_DIR . "/css/graduate-cpt-style.css" ),
            'all'
        );
    }


    public static function maybe_enqueue_scripts()
    {
        global $post;

        if( !is_object( $post ) ) return;

        if( 'graduate' === $post->post_type || stripos( $post->post_content, '[graduate-list' ) !== false )
        {
            wp_enqueue_style( 'graduate-cpt-style' );
        }
    }


    public static function shortcode( $atts = [] )
    {
        $semester_lookup = [
            1 => 'Spring',
            2 => 'Summer',
            3 => 'Fall',
        ];


        $a = shortcode_atts(
            [
                'program' => '',
                'year' => '',
                'semester' => '',
                'img_shape' => 'circle',
            ],
            $atts
        );

        if( !empty( $a['semester'] ) )
        {
            foreach( $semester_lookup as $key => $value )
            {
                if( strtolower( $a['semester'] ) === strtolower( $value ) )
                {
                    $a['semester'] = $key;
                }
            }
        }

        $args = [
            'post_type' => 'graduate',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => self::_get_meta_args( $a ),
            'orderby' => self::_get_orderby( $a ),
        ];

        if( !empty( $a['program'] ) && term_exists( $a['program'], 'graduate-programs' ) ) // should work for both category slug and ID
        {
            $tax_query = [
                'taxonomy' => 'graduate-programs'
            ];

            if( is_numeric( $a['program'] ) )
            {
                $tax_query['field'] = 'term_id';
                $tax_query['terms'] = $a['program'];
            }
            else
            {
                $tax_query['field'] = 'slug';
                $tax_query['terms'] = $a['program'];
            }

            $args['tax_query'] = [ $tax_query ];
        }

        $query = new \WP_Query( $args );

        // Sorting manually by name, since the query doesn't seem to do it
        $graduates = [];

        // We're going to have to do some extra steps if we have multiple semesters and/or years, so I'm
        // doing this for the moment so I don't mess up any of the other bits of functionality
        if( !empty( $a['semester'] ) && !empty( $a['year'] ) )
        {
            // First we'll have to get all the results into a form we can use
            if( $query->have_posts() )
            {
                while( $query->have_posts() )
                {
                    $query->the_post();

                    $meta = maybe_unserialize( get_post_meta( get_the_ID(), 'graduate-info', true ) );

                    $grad = [
                        'id' => get_the_ID(),
                        'thumbnail_url' => get_the_post_thumbnail_url( get_the_ID() ),
                        'content' => get_the_content(),
                    ];

                    foreach( $meta as $key => $value )
                    {
                        $grad[$key] = $value;
                    }

                    $graduates[] = $grad;
                }
            }

            uasort( $graduates, function( $a, $b ) {

                if( strcasecmp( $a['lname'], $b['lname'] ) === 0 )
                {
                    return $a['fname'] <=> $b['fname'];
                }
                else
                {
                    return $a['lname'] <=> $b['lname'];
                }
            });
        }

        if( !empty( $graduates ) )
        {
            $img_css = "";

            switch( $a['img_shape'] )
            {
                case 'circle':
                    $img_css = "rounded-circle object-position-center object-fit-cover";
                    break;

                case 'round-square':
                    $img_css = "rounded object-position-center object-fit-cover";
                    break;

                case 'square':
                default:
                    $img_css = "rounded-0 object-position-center object-fit-cover";
                    break;
            }

            ob_start();
            ?>

            <div class="container">
            <?php foreach( $graduates as $grad ) : ?>

                <?php extract( $grad ); // should give us $id, $thumbnail_url, $content, $lname, $fname, $semester, and $year ?>

                <div class="row hidden-md-down">
                    <div class="col d-flex">
                    <?php if( !empty( $thumbnail_url ) ) : ?>
                        <div class="grad-image mr-5">
                            <img src="<?= $thumbnail_url ?>" class="<?= !empty( $img_css ) ? " $img_css" : "" ?>" width="150" height="150" alt="<?= "$fname $lname" ?>">
                        </div>
                    <?php endif; ?>
                        <div class="<?= !empty( $thumbnail_url ) ? "grad-text" : "" ?> w-100">
                            <h3 class="mb-2"><?= "$fname $lname" ?></h3>
                            <h5 class="font-weight-normal font-italic"><?= $semester_lookup[$semester] . " $year" ?></h5>
                            <div class="grad-content">
                                <?= apply_filters( 'the_content', $content ); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row hidden-lg-up">
                    <div class="col d-flex flex-column">
                        <div class="grad-header mb-3 d-flex flex-row align-items-center">
                            <?php if( !empty( $thumbnail_url ) ) : ?>
                                <div class="grad-image mr-5">
                                    <img src="<?= $thumbnail_url ?>" class="<?= !empty( $img_css ) ? " $img_css" : "" ?>" width="150" height="150" alt="<?= "$fname $lname" ?>">
                                </div>
                            <?php endif; ?>
                            <div class="grad-name">
                                <h3 class="mb-2"><?= "$fname $lname" ?></h3>
                                <h5 class="font-weight-normal font-italic"><?= $semester_lookup[$semester] . " $year" ?></h5>
                            </div>
                        </div>
                        <div class="grad-content">
                            <?= apply_filters( 'the_content', $content ); ?>
                        </div>
                    </div>
                </div>

                <hr class="mt-4 mb-5" />

            <?php endforeach; ?>
            </div>

            <?php
            return ob_get_clean();
        }

        ob_start();

        // For Debug
        //var_dump( $query );

        $img_css = "";

        switch( $a['img_shape'] )
        {
            case 'circle':
                $img_css = "rounded-circle object-position-center object-fit-cover";
                break;

            case 'round-square':
                $img_css = "rounded object-position-center object-fit-cover";
                break;

            case 'square':
            default:
                $img_css = "rounded-0 object-position-center object-fit-cover";
                break;
        }

        if( $query->have_posts() ) :
        ?>
            <div class="container">
            <?php while( $query->have_posts() ) : ?>
                <?php
                $query->the_post();

                $meta = maybe_unserialize( get_post_meta( get_the_ID(), 'graduate-info', true ) );

                // Should give us $fname, $lname, $program, $semester, and $year
                extract( $meta );

                ?>
                <div class="row">
                    <div class="col-2">
                        <img src="<?= get_the_post_thumbnail_url(); ?>" class="img-fluid size-small alignnone<?= !empty( $img_css ) ? " $img_css" : "" ?>" style="max-height: 150px; max-width: 150px;" width="150" height="150" alt="<?= "$fname $lname" ?>">
                    </div>
                    <div class="col-10">
                        <h3 class="mb-2"><?= "$fname $lname" ?></h3>
                        <h5 class="font-weight-normal font-italic"><?= $semester_lookup[$semester] . " $year" ?></h5>
                        <div>
                            <?= the_content(); ?>
                        </div>
                    </div>
                </div>
                <hr class="mt-4 mb-5" />
            <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php
        // Resets the post query info, in case we need to refer to it elsewhere on the page.
        wp_reset_postdata();

        return ob_get_clean();
    }


    private static function _get_meta_args( array $atts ) : array
    {
        $name_args = [
            [
                'key' => 'graduate-lname',
                'compare' => 'EXISTS',
            ],
            [
                'key' => 'graduate-fname',
                'compare' => 'EXISTS',
            ],
        ];

        $args = [];

        foreach( $atts as $key => $value )
        {
            if( 'img_shape' === $key ) continue;

            $new_arg = [];
            if( 'program' !== $key && !empty( $value ) )
            {
                $new_arg = [
                    'key' => 'graduate-' . $key,
                    'value' => $value,
                    'compare' => '=',
                ];

                if( 'year' === $key || 'semester' == $key )
                {
                    $new_arg['type'] = 'NUMERIC';
                }
            }
            else if( 'year' === $key )
            {
                $new_arg = [
                    'key' => 'graduate-year',
                    'compare' => 'EXISTS',
                    'type' => 'NUMERIC',
                ];
            }

            $args[] = $new_arg;
        }

        if( count( $args ) > 1 )
        {
            $args['relation'] = 'AND';
        }

        return array_merge( $args, $name_args );
    }


    private static function _get_orderby( array $atts ) : array
    {
        $order = [
            'graduate-year' => 'DESC',
            'graduate-semester' => 'ASC',
            'graduate-lname' => 'ASC',
            'graduate-fname' => 'ASC',
        ];

        if( empty( $atts['program'] ) )
        {
            $order = array_merge( ['graduate-programs' => 'ASC'], $order );
        }

        return $order;
    }
}
?>