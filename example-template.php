<?php
/*
Template Name: Example Template
*/
?>

<?php get_header(); ?>

<div id="content" class="narrowcolumn">

    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <div class="post">
                        <div class="entrytext">
                                <?php the_content('<p class="serif">Read the rest of this page &raquo;</p>'); ?>

                                <?php link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>

                        </div>
                </div>
          <?php endwhile; endif; ?>

</div>	

<div id="sidebar">

<ul>

<?php if ( $parents = page_get_parents() ) { ?>
<li><h2>back to</h2>

<ul>
<?php echo $parents; ?>
</ul>

</li>
<?php } ?>

<?php if ( $toc = page_get_toc() ) { ?>
<li><h2>headings</h2>

<ul>
<?php echo $toc; ?>
</ul>

</li>
<?php } ?>

<?php

  if ( $children = page_get_children() ) {
?>

<li><h2>see also</h2>

<ul>
<?php echo $children; ?>
</ul>

</li>
<?php } ?>

</ul>

</div>

<?php get_footer(); ?>
