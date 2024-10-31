<?php

/*

Plugin Name: Page Tools
Version: 1.2
Plugin URI: http://mcnicks.org/wordpress/page-tools/
Description: Provides PHP functions that help to manage pages in WordPress.
Author: David McNicol
Author URI: http://mcnicks.org/

Copyright (c) 2005
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

This file is part of WordPress.
WordPress is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/



/*
 * page_get_parents
 *
 *  $before, $after - chunks of HTML that will be placed before
 *  and after the link to each page.
 *
 * Returns HTML with links to the parents of the current page.
 */

function page_get_parents ( $before = "<li>", $after = "</li>" ) {

  $child = page_post_id();
  $links = array();

  while ( $parent = page_get_parent_id( $child ) ) {

    $link = get_permalink( $parent );
    $title = get_the_title( $parent );
    $links[] = "<a href=\"$link\">$title</a>";
    $child = $parent;
  }

	$links = array_reverse( $links );
	
  $s = "";

  foreach ( $links as $link ) {
    $s .=  $before.$link.$after."\n";
  }

  return $s;
}



/*
 * page_get_children
 *
 *  $before, $after - chunks of HTML that will be placed before
 *  and after the link to each page.
 *
 * Returns HTML with links to the children of the current page.
 */

function page_get_children ( $before = "<li>", $after = "</li>" ) {
	return page_get_children_of( page_post_id() );
}



/*
 * page_get_siblings
 *
 *  $before, $after - are chunks of HTML that will be placed before
 *  and after the link to each page.
 *
 * Returns HTML with links to the siblings of the current page (ie. all of
 * the children of the parent page).
 */

function page_get_siblings ( $before = "<li>", $after = "</li>" ) {
	return page_get_children_of( page_get_parent_id ( page_post_id() ) );
}



/*
 * page_get_children_of
 *
 *  $parent_id - ID of the parent page whose children we will search for.
 *  $before, $after - chunks of HTML that will be placed before
 *  and after the link to each page.
 *
 * Returns HTML with links to the children of the current page.
 */

function page_get_children_of ( $parent_id, $before = "<li>", $after = "</li>" ) {

  $children = page_get_child_ids( $parent_id );
  $links = array();

  if ( ! $children ) return;

  foreach ( $children as $child ) {

    $cid = $child->ID;

    if ( $cid == 0 ) next;

    $link = get_permalink( $cid );
    $title = get_the_title( $cid );
    $links[] = "<a href=\"$link\">$title</a>";
  }

  $s = "";

  foreach ( $links as $link ) {
    $s .= $before.$link.$after."\n";
  }

  return $s;
}



/*
 * page_get_toc
 *
 *  $minimum - the minimum number of headings required to
 *  trigger the making of the list.
 *
 * Returns HTML containing links to each of the headings.
 */

function page_get_toc ( $minimum = 2 ) {
  global $page_headings;

  $page_headings = array();

  // Get the post contents and create the table of contents.

  if ( $content = page_post_content() )
    page_make_toc( $content );

  // Check that we have the minimum amount of headings.

  if ( count( $page_headings ) < $minimum ) return;

  // Go through each heading and build a list in HTML.
  
  $html = "";
  $current_level = 0;
  $lowest_level = 1000;

  foreach ( $page_headings as $heading ) {

    // Change the level of indentation if neccessary.

    for ( $i = $heading[1]; $i < $current_level; $i++ ) $html .= "</ul>";
    for ( $i = $current_level; $i < $heading[1]; $i++ ) $html .= "<ul>";
    $current_level = $heading[1];

    // Make a note of the lowest level.

    if ( $heading[1] < $lowest_level ) $lowest_level = $heading[1];

    // Add a list item with the appropriate link for the heading.

    $html .= '<li>';
    $html .= '<a href="#heading-'.$heading[2].'">'.$heading[0].'</a>';
    $html .= '</li>';
  }

  // Close any remaining indendation.

  for ( $i = 0; $i < $current_level; $i++ ) $html .= "</ul>";

  // Remove extraneous <ul> tags and return.

  for ( $i = 0; $i < $lowest_level; $i++ )
    $html = preg_replace( '/^<ul>(.*)<\/ul>$/', '\1', $html, 1 );

  return $html;
}



/*
 * page_make_toc
 *
 *  $content - the current contents of the post.
 *
 * This function is a content filter that collects any headings
 * in the post and stores information about them for page_get_toc()
 * to display later.
 */

add_action( 'the_content', 'page_make_toc', 10 );

function page_make_toc ( $content ) {
  global $page_headings;

  // Clear the array of headings.

  $page_headings = array();

  // Find the headings in the content and collect them using
  // a callback function.

  $pattern = '/(<h\d>)(.*?)(<\/h\d>)/is';

  return preg_replace_callback( $pattern, 'page_mt_callback' , $content );
}



/*
 * page_mt_callback
 *
 * Callback that analyzes the matches from the preg_replace_callback()
 * call in page_make_toc(), above.
 */

function page_mt_callback ( $matches ) {
  global $page_headings;

  // Get the level and the text of the heading that has been found.

  $level = preg_replace( '/<h(\d)>/i', '\1',  $matches[1] );
  $text = preg_replace( '/<\/?([^>]+)>/', '', $matches[2] );

  // Increment the index and store the information relating to this
  // heading.

  $index = count( $page_headings );
  $page_headings[] = array( $text, $level, $index );

  // Return the entire match wrapped in an named anchor tag. The
  // tag uses the index value to make a unique name that will be linked
  // to when page_get_toc() is called.

  return '<a name="heading-'.$index.'">'.$matches[0].'</a>';
}



/*
 * page_get_parent_id
 *
 *  $child - the ID of a page.
 *
 * Returns the ID of the parent of the given page.
 */

function page_get_parent_id ( $child = 0 ) {
  global $wpdb;

  // Return if no ID was specified.

  if ( $child == 0 ) return 0;

  // Get the ID of the parent.

  return $wpdb->get_var("
    SELECT post_parent
      FROM $wpdb->posts
     WHERE ID = $child
  ");
}



/*
 * page_get_child_ids
 *
 *  $parent - ID of the parent page.
 *
 * Returns an associative array containing the IDs of the children
 * of the parent page.
 */

function page_get_child_ids ( $parent = 0 ) {
  global $wpdb;

  // Return if no ID was specified.

  if ( $parent == 0 ) return 0;

  // Get the ID of the parent.

  return $wpdb->get_results("
    SELECT ID
      FROM $wpdb->posts
      WHERE post_parent = $parent
      ORDER BY menu_order, post_title
  ");
}



/*
 * page_post_id
 *
 * Returns the ID of the current post, even outside of the loop.
 */

function page_post_id () {
  global $wp_query;

  $pages = $wp_query->get_posts();

  if ( count( $pages ) )
    return $pages[0]->ID;
}



/*
 * page_post_content
 *
 * Returns the content of the current post, even outside of the loop.
 */

function page_post_content () {
  global $wp_query;

  $pages = $wp_query->get_posts();

  if ( count( $pages ) )
    return $pages[0]->post_content;
}

?>
