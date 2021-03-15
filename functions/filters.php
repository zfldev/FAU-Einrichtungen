<?php

/**
* @package WordPress
* @subpackage FAU
* @since FAU 1.12.34
*/
/*-----------------------------------------------------------------------------------*/
/* Change default title
/*-----------------------------------------------------------------------------------*/
function fau_wp_title( $title, $sep ) {
	global $paged, $page;

	if ( is_feed() )
		return $title;

	// Add the site name.
	$title .= get_bloginfo( 'name' );

	// Add the site description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		$title = "$title $sep $site_description";

	// Add a page number if necessary.
	if ( $paged >= 2 || $page >= 2 )
		$title = "$title $sep " . sprintf( __( 'Seite %s', 'fau' ), max( $paged, $page ) );

	return $title;
}
add_filter( 'wp_title', 'fau_wp_title', 10, 2 );

/*-----------------------------------------------------------------------------------*/
/* Resets the Excerpt More
/*-----------------------------------------------------------------------------------*/
function fau_excerpt_more( $more ) {
    return get_theme_mod('default_excerpt_morestring');
}
add_filter('excerpt_more', 'fau_excerpt_more');


/*-----------------------------------------------------------------------------------*/
/* Changes default length for excerpt
/*-----------------------------------------------------------------------------------*/
function fau_excerpt_length( $length ) {    
    return get_theme_mod('default_excerpt_length');
}
add_filter( 'excerpt_length', 'fau_excerpt_length' );


/*-----------------------------------------------------------------------------------*/
/*  Refuse spam-comments on media
/*-----------------------------------------------------------------------------------*/
function filter_media_comment_status( $open, $post_id ) {
	$post = get_post( $post_id );
	if( $post->post_type == 'attachment' ) {
		return false;
	}
	return $open;
}
add_filter( 'comments_open', 'filter_media_comment_status', 10 , 2 );


/*-----------------------------------------------------------------------------------*/
/*  Search filter
/*-----------------------------------------------------------------------------------*/
function fau_searchfilter($query) {
    if ($query->is_search && !is_admin() ) {
	if(isset($_GET['post_type'])) {
	    $types = (array) $_GET['post_type'];
	} else {
	    $types = get_theme_mod('search_post_types');
	  //  $types = array("person", "post", "page", "attachment");
	  //  $types = array("attachment","person");
	}
	$allowed_types = get_post_types(array('public' => true, 'exclude_from_search' => false));
	foreach($types as $type) {
	    if( in_array( $type, $allowed_types ) ) { $filter_type[] = $type; }
	}
	if(count($filter_type)) {
	    $query->set('post_type',$filter_type);
	}	
        $query->set('post_status', array('publish','inherit'));

    }
}
add_filter("pre_get_posts","fau_searchfilter");


/*-----------------------------------------------------------------------------------*/
/*  Search sorting
/*-----------------------------------------------------------------------------------*/
add_filter('posts_orderby','fau_sort_custom',10,2);
function fau_sort_custom( $orderby, $query ){
    global $wpdb;

    if(!is_admin() && is_search())
    //    $orderby =  $wpdb->prefix."posts.post_type ASC, {$wpdb->prefix}posts.post_date DESC";
	 $orderby =  $wpdb->prefix."posts.post_modified DESC";

    return  $orderby;
}


/*-----------------------------------------------------------------------------------*/
/* wplink query args adjustment
/*-----------------------------------------------------------------------------------*/
function fau_wp_link_query_args( $query ) {
     // check to make sure we are not in the admin
   //  if ( !is_admin() ) {
          $query['post_type'] = array( 'post', 'page', 'person'  ); // show only posts and pages
   //  }
     return $query;
}
add_filter( 'wp_link_query_args', 'fau_wp_link_query_args' ); 


/*-----------------------------------------------------------------------------------*/
/*  display ids for pages columns and custom types
/*-----------------------------------------------------------------------------------*/
function fau_revealid_add_id_column( $columns ) {
   $columns['revealid_id'] = 'ID';
   return $columns;
}

function fau_revealid_id_column_content( $column, $id ) {
  if( 'revealid_id' == $column ) {
    echo $id;
  }
}
if (get_theme_mod('advanced_reveal_pages_id')) {
    add_filter( 'manage_pages_columns', 'fau_revealid_add_id_column', 5 );
    add_action( 'manage_pages_custom_column', 'fau_revealid_id_column_content', 5, 2 );
}

/*-----------------------------------------------------------------------------------*/
/* Filter bad paragraphs - fallback
/*-----------------------------------------------------------------------------------*/
add_filter('the_content', 'remove_empty_p', 20, 1);
function remove_empty_p($content){
    $content = force_balance_tags($content);
    return preg_replace('#<p>\s*+(<br\s*/*>)?\s*</p>#i', '', $content);
}

add_filter('the_content', 'remove_accordion_bad_br', 20, 1);
function remove_accordion_bad_br($content){
   // $content = force_balance_tags($content);
    return preg_replace('#<br\s*/*>\s*<div class="accordion#i', '<div class="accordion', $content);
}

add_filter('the_content', 'remove_bad_p', 20, 1);
function remove_bad_p($content){
   // $content = force_balance_tags($content);
    $content = preg_replace('#<p><div #i', '<div ', $content);
    return preg_replace('#</div></p>#i', '</div>', $content);
}

/*-----------------------------------------------------------------------------------*/
/* Filter for postcount
/*-----------------------------------------------------------------------------------*/
add_filter('wp_list_categories','categories_postcount_filter');
function categories_postcount_filter ($variable) {
   $variable = str_replace('(', '<span class="post_count">(', $variable);
   $variable = str_replace(')', ')</span>', $variable);
   return $variable;
}

/*-----------------------------------------------------------------------------------*/
/* Add rel lightbox to content images
/*-----------------------------------------------------------------------------------*/
add_filter('the_content', 'fau_addlightboxrel');
function fau_addlightboxrel ($content) {
    global $post;
    $pattern = '/<a href=\"([^\"]+)\.(bmp|gif|jpeg|jpg|png)(?![\w.\-_])\">/i';
    $replacement = '<a class="lightbox" href="$1.$2">';
    $content = preg_replace($pattern, $replacement, $content);
    return $content;
}

/*-----------------------------------------------------------------------------------*/
/* Remove post class, we dont need
/*-----------------------------------------------------------------------------------*/
add_filter('post_class', 'fau_remove_default_post_class', 10,3);
function fau_remove_default_post_class($classes, $class, $post_id) {
    
    if (is_admin() ) {
	// Do not change anything if we are in the backend
	return $classes;
    }
// adapted form https://www.forumming.com/question/21152/remove-extra-classes-from-post-title
    
    // Array that holds the undesired classes
    $removeClasses = array(
	'hentry',
	'type-',
	'post-',
	'status-',
        'category-',
        'tag-',
	'format'
    );


    $newClasses = array();
    foreach ($classes as $_class) {
        $hasClass = FALSE;
        foreach ($removeClasses as $_removeClass) {
            if (strpos($_class, $_removeClass) === 0) {
                $hasClass = TRUE;
                break;
            }
        }
        if (!$hasClass) {
            $newClasses[] = $_class;
        }
    }

    return ($newClasses);

}
