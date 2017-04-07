<?php get_header(); ?>

<?php if (have_posts()) while (have_posts()) : the_post(); ?>

    <h1><?php the_title(); ?></h1>
    <?php the_content(); ?>
    <?php the_tags('Тэги: ', ' | ', ''); ?>

<?php endwhile; ?>

<?php previous_post_link('%link', '<span class="meta-nav">' . _x('&larr;', 'Previous post link', 'twentyten') . '</span> %title'); ?>
<?php next_post_link('%link', '%title <span class="meta-nav">' . _x('&rarr;', 'Next post link', 'twentyten') . '</span>'); ?>
<?php comments_template('', true); ?>

<?php get_footer(); ?>