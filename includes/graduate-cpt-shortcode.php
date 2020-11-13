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
        add_shortcode( 'graduate-list', [ __CLASS__, 'shortcode' ] );
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

        if( !empty( $a['program'] ) && term_exists( $a['program'], 'category' ) ) // should work for both category slug and ID
        {
            if( is_numeric( $a['program'] ) )
            {
                $args['cat'] = $a['program'];
            }
            else
            {
                $args['category_name'] = $a['program'];
            }
        }

        $query = new \WP_Query( $args );

        ob_start();

        // For Debug
        //var_dump( $query );

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
                        <img src="<?= get_the_post_thumbnail_url(); ?>" class="img-fluid size-small alignnone" width="150" height="150" alt="<?= "$fname $lname" ?>">
                    </div>
                    <div class="col-10">
                        <h2 class="mb-2"><?= "$fname $lname" ?></h2>
                        <h4 class="font-weight-normal font-italic"><?= $semester_lookup[$semester] . " $year" ?></h4>
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
            $order = array_merge( ['category' => 'ASC'], $order );
        }

        return $order;
    }
}
?>