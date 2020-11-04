<?php
/**
 * Template Name: Graduate single template
 */

get_header();

the_post();

$meta = maybe_unserialize( get_post_meta( $post->ID, 'graduate-info', true ) );

// This should give us $lname, $fname, $semester, and $year
extract( $meta );


?>

<div class="container mt-4 mb-3">
    <a href="<?= get_post_type_archive_link( 'graduate' ) ?>" class="btn btn-primary btn-sm">See All</a>
</div>

<div class="container mb-4">
    <div class="row">
        <?php if( has_post_thumbnail() ) : ?>
        <div class="col-md-4">
            <?= get_the_post_thumbnail( get_the_ID(), 'medium' ) ?>
        </div>
        <div class="col-md-8">
        <?php else : ?>
        <div class="col">
        <?php endif; ?>
            <h1 class="font-condensed"><?= $fname ?> <?= $lname ?></h1>
            <?php the_content(); ?>
        </div>
    </div>
</div>

<?php
get_footer();
?>