<?php
/**
 * Template Name: Graduate CPT Archive Template
 */

get_header();

$semester_lookup = [
    1 => 'Spring',
    2 => 'Summer',
    3 => 'Fall',
];

?>

<div class="container mt-5 mb-4">
    
    <h1 class="mb-4">Recent Graduates</h2>

<?php
// The Loop™
if( have_posts() ) :
    while( have_posts() ) :
        the_post();

        $meta = maybe_unserialize( get_post_meta( get_the_ID(), 'graduate-info', true ) );

        // Should give us $lname, $fname, $semester, and $year
        extract( $meta );
        ?>
        
        <div class="row my-2">
            <div class="col card py-3">
                <a href="<?= get_the_permalink() ?>">
                <div class="row">
                    <div class="col-2">
                        <?= get_the_post_thumbnail( get_the_ID(), 'thumbnail', [ 'class' => 'w-100  object-position-center object-fit-cover' ] ); ?>
                    </div>
                    <div class="col-10">
                        <h4 class="font-condensed card-title"><?= "$fname $lname" ?></h3>
                        <h6 class="font-slab-serif card-subtitle mb-3"><?= $semester_lookup[$semester] . " $year" ?></h4>
                        <div class="card-text">
                            <?= the_content(); ?>
                        </div>
                    </div>
                </div>
                </a>
            </div>
        </div>

        <?php
    endwhile;
endif;
?>

</div>

<?php
get_footer();
?>