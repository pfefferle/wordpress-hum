<?php
/*
 Plugin Name: hum
 Plugin URI: http://github.com/willnorris/wordpress-hum
 Description: Whistle inspired URL shortener for WordPress
 Author: Will Norris
 Author URI: http://willnorris.com/
 Version: 1.0
 License: Dual GPL (http://www.fsf.org/licensing/licenses/info/GPLv2.html) and Modified BSD (http://www.fsf.org/licensing/licenses/index_html#ModifiedBSD)
 Text Domain: hum
 */


/**
 * Accept hum query variables.
 */
function hum_query_vars( $vars ) {
  $vars[] = 'hum';
  return $vars;
}
add_action('query_vars', 'hum_query_vars');


/**
 * Parse request for shortlink.
 *
 * @uses do_action() Calls 'hum_request_{$type}" action
 * @uses do_action() Calls 'hum_request" action
 */
function hum_parse_request( $wp ) {
  if ( array_key_exists( 'hum', $wp->query_vars ) ) {
    list($type, $id) = explode('/', $wp->query_vars['hum'], 2);
    do_action("hum_request_{$type}", $id);
    do_action('hum_request', $type, $id);
  }
}
add_action('parse_request', 'hum_parse_request');


/**
 * Redirect shortlinks that are for content hosted directly within WordPress.
 * The 'id' portion of these URLs is expected to be the sexagesimal post ID.
 *
 * @param string $code the content-type prefix
 */
function hum_redirect_local( $type, $id ) {
  $local_types = array('b', 't');

  if ( in_array($type, $local_types) ) {
    $p = sxg_to_num( $id );
    $redirect = add_query_arg( 'p', $p, home_url() );
    wp_redirect( $redirect, 301 );
    exit;
  }
}
add_filter('hum_request', 'hum_redirect_local', 20, 2);


/**
 * Allow for simple redirect rules for shortlink prefixes.  Users can provide a
 * filter to perform simple URL redirect for a given type prefix.  For example,
 * to redirect all /w/ shortlinks to your personal PBworks wiki, you could use:
 *
 *   add_filter('hum_redirect_base_w', 
 *     create_function('', 'return "http://willnorris.pbworks.com/";'));
 *
 * @uses apply_filters() Calls 'hum_redirect_base_{$type}' filter on redirect base URL
 */
function hum_redirect_request( $type, $id ) {
  $url = apply_filters("hum_redirect_base_{$type}", false);
  if ( $url ) {
    $url = trailingslashit($url) . $id;
    wp_redirect( $url );
    exit;
  }
}
add_action('hum_request', 'hum_redirect_request', 30, 2);


/**
 * Add rewrite rules for hum shortlinks.
 *
 * @param object $wp_rewrite
 */
function hum_rewrite_rules( $wp_rewrite ) {
  $hum_rules = array(
		'([a-z]/.+)' => 'index.php?hum=$matches[1]',
  );

  $wp_rewrite->rules = $hum_rules + $wp_rewrite->rules;
}
add_action('generate_rewrite_rules', 'hum_rewrite_rules');


/**
 * Get the base URL for hum shortlinks.  Defaults to the WordPress home url.
 * Users can provide a filter to use a custom domain for shortlinks.
 *
 * @uses apply_filters() Calls 'hum_shortlink_base' filter on base URL
 * @return string
 */
function hum_shortlink_base() {
  return apply_filters( 'hum_shortlink_base', home_url() );
}


/**
 * Get the shortlink for a post, page, attachment, or blog.
 *
 * @param string $link the current shortlink for the post
 * @param int $id post ID
 * @param string $context
 * @param boolean $allow_alugs
 * @return string
 */
function hum_get_shortlink($link, $id, $context, $allow_slugs) {
  $post_id = 0;
  if ( 'query' == $context ) {
    if ( is_front_page() ) {
      $link = hum_shortlink_base();
    } elseif ( is_singular() ) {
      $post_id = get_queried_object_id();
    }
  } elseif ( 'post' == $context ) {
    $post = get_post($id);
    $post_id = $post->ID;
  }

  if ( !empty($post_id) ) {
    $type = hum_type_prefix($post_id);
    $sxg_id = num_to_sxg($post_id);
    $link = trailingslashit( hum_shortlink_base() ) . $type . '/' . $sxg_id;
  }

  return $link;
}
add_filter('pre_get_shortlink', 'hum_get_shortlink', 10, 4);


/**
 * Get the content-type prefix for the specified post.
 *
 * @see http://ttk.me/w/Whistle#design
 * @uses apply_filters() Calls 'hum_type_prefix' on the content type prefix
 *
 * @param int|object $post A post
 * @return string the content type prefix for the post
 */
function hum_type_prefix( $post ) {
  $prefix = 'b';

  $post_format = get_post_format( $post );
  switch($post_format) {
    case 'aside': 
      $prefix = 't'; break;
    case 'status': 
      $prefix = 't'; break;
  }

  return apply_filters('hum_type_prefix', $prefix, $post);
}


// New Base 60 - see http://ttk.me/w/NewBase60
//
// slightly modified from Cassis Project (http://cassisproject.com/)
// Copyright 2010 Tantek Çelik, released under Creative Commons by-sa 3.0

if ( !function_exists( 'num_to_sxg' ) ):
/**
 * Convert base-10 number to sexagesimal.
 */
function num_to_sxg($n) {
  $s = "";
  $m = "0123456789ABCDEFGHJKLMNPQRSTUVWXYZ_abcdefghijkmnopqrstuvwxyz";
  if ($n===null || $n===0) { return 0; }
  while ($n>0) {
    $d = $n % 60;
    $s = $m[$d] . $s;
    $n = ($n-$d)/60;
  }
  return $s;
}
endif;


if ( !function_exists( 'sxg_to_num' ) ):
/**
 * Convert sexagesimal to base-10 number.
 */
function sxg_to_num($s) {
  $n = 0;
  $j = strlen($s);
  for ($i=0;$i<$j;$i++) { // iterate from first to last char of $s
    $c = ord($s[$i]); //  put current ASCII of char into $c  
    if ($c>=48 && $c<=57) { $c=$c-48; }
    else if ($c>=65 && $c<=72) { $c-=55; }
    else if ($c==73 || $c==108) { $c=1; } // typo capital I, lowercase l to 1
    else if ($c>=74 && $c<=78) { $c-=56; }
    else if ($c==79) { $c=0; } // error correct typo capital O to 0
    else if ($c>=80 && $c<=90) { $c-=57; }
    else if ($c==95) { $c=34; } // underscore
    else if ($c>=97 && $c<=107) { $c-=62; }
    else if ($c>=109 && $c<=122) { $c-=63; }
    else { $c = 0; } // treat all other noise as 0
    $n = 60*$n + $c;
  }
  return $n;
}
endif;