<?php

/**
 * get the paged template, based on post type/taxonomy and arg
 * we want to not only look for the current page's template. but also the
 * next highest tempalte available
 */
function tidyt_get_paged_template_functions($type, $arg=null){
    if($arg != null )
        $arg = '-'.$arg;

    $templates = array();
    $paged = array();
    $page = get_query_var('paged') ? get_query_var('paged') : '' ;

    if( is_paged() ){
        $templates[] = $type.$arg.'-paged-'.$page.'.php';
        $templates[] = tidyt_get_highest_available_page( $type.$arg, $page );

        //unset any empty keys till i clean up my code
        unset( $templates[array_search('', $templates)] );

        $templates[] = $type.$arg.'-paged'.'.php';
    }

    $templates[] = $type.$arg.'.php';


    return $templates;
}


/**
 * If we cant find a page template matching the current paged number,
 * then we look for hte next highest
 * @return [type] [description]
 */
function tidyt_get_highest_available_page( $string, $page ){


    $files = array();
    $path = tidyt_get_constant_path('TEMPLATE');

    if ( file_exists(STYLESHEETPATH . '/' . $path)) {
        $dir =  STYLESHEETPATH . '/' . $path;
    } else if ( file_exists(TEMPLATEPATH . '/' . $path) ) {
        $dir =  TEMPLATEPATH . '/' . $path;
    }

    // find all matching tempates
    foreach (glob($dir.$string."-paged-*.php") as $filename)
        $files[] = $filename;


    // return if none available
    if(!$files)
        return;

    // for each of the found templates we flag the highest number
    foreach($files as $file ):
        // get the highest numbered file
        $max = max($files);
        // strip the non number characters from it
        $num = preg_replace("/[^0-9]/","",$max);
        //  if the number is greater than the current page unset it
        if($num > $page)
            unset( $files[array_search($max, $files)] );
    endforeach;

    if(!$files)
        return;

    $max = max($files);

    $url = trim($max, '/');

    return substr($url, strrpos($url, '/')+1);

}


/**
 * This function will get simple feed templates
 *
 * simple being that they do not include any sort of taxonomies such as
 * tags, cats, or custom taxonomies
 *
 * $name string - name of the template ie: front-page, home, search
 */
function tidyt_get_simple_feed_templates($name){
    $templates = array();
    $paged = array();

    $paged = tidyt_get_paged_template_functions($name);

    $templates = array_merge($paged, $templates);

    return $templates;
}


/**
 * Gets the templates for a complex feed
 * @param  string $feed [description]
 * @return [type]       [description]
 */
function tidyt_get_complex_feed_templates($feed = ''){

    $object = get_queried_object();
    $name = $feed == 'taxonomy' ? 'taxonomy-' : '';
    $templates = array();
    $paged = array();

    if ( ! empty( $object->slug ) ) {

        $slug = $object->slug;
        $term_id = $feed == 'taxonomy' ? '' : $object->term_id;

        $cats = array($term_id, $slug);
        foreach ($cats as $k=>$arg):

            $paged = tidyt_get_paged_template_functions($name.$object->taxonomy, $arg);
            $templates = array_merge($paged, $templates);

        endforeach;

    }

    foreach(array($feed, 'archive') as $v){
        $paged = tidyt_get_paged_template_functions($v);
        $templates = array_merge($templates,$paged);
    }


    return $templates;
}


/**
 * Archive template functions
 * @return [type] [description]
 */
function tidyt_get_archive_template_functions(){

    $post_types = array_filter( (array) get_query_var( 'post_type' ) );

    $templates = array();
    $paged = array();

    // if the archive is of a CPT
    if ( count( $post_types ) == 1 ) {
        $post_type = reset( $post_types );
        $paged = tidyt_get_paged_template_functions('archive', $post_type);

    }

    $paged = tidyt_get_paged_template_functions('archive', $post_type);

    $templates = array_merge($paged, $templates);
    $templates[] = 'archive.php';
    if( is_paged() )
        $templates[] = 'paged.php';

    return $templates;
}


/**
 * Check to see whether or not a CPT has an archive
 * @return [type] [description]
 */
function tidyt_get_post_type_archive_template_functions() {

    $post_type = get_query_var( 'post_type' );
    if ( is_array( $post_type ) )
        $post_type = reset( $post_type );

    $obj = get_post_type_object( $post_type );
    if ( ! $obj->has_archive )
        return '';

    return tidyt_get_archive_template_functions();
}


/**
 * get the author templates
 * @return [type] [description]
 */
function tidyt_get_author_template_functions(){

    $author = get_queried_object();

    $templates = array();

    if ( is_a( $author, 'WP_User' ) ) {

        $user_nicename = $author->user_nicename;
        $user_id = $author->ID;

        $users = array($user_id, $user_nicename);
        foreach ($users as $k=>$arg):

            $paged = tidyt_get_paged_template_functions('author', $arg);
            $templates = array_merge($paged, $templates);

        endforeach;

    }
    $templates[] = 'author'.'.php';
    $templates[] = 'archive'.'.php';

    return $templates;
}


/**
 * Get page templates
 * @return [type] [description]
 */
function tidyt_get_page_template_functions(){

    $post = get_queried_object();
    $id = get_queried_object_id();
    $template = get_page_template_slug($id);



    $pagename = get_query_var('pagename');

    if ( ! $pagename && $id ) {
        if ( $post )
            $pagename = $post->post_name;
    }

    $templates = array();
    if ( $template && 0 === validate_file( $template ) )
        $templates[] = $template;

    if ( $pagename )
        $templates[] = "page-".$pagename.'.php';
    if ( $id )
        $templates[] = "page-".$id.'.php';
    if( $post->post_parent ){
        $parent = get_post($post->post_parent);
        $templates[] = "page-parent-".$parent->post_name.'.php';
        $templates[] = "page-".$parent->post_name.'.php';
    }

    $templates[] = 'page'.'.php';
    $templates[] = "singlular".'.php';

    return $templates;
}

/**
 * Single post functions
 */
function tidyt_get_single_template_functions(){

    $object = get_queried_object();
    $templates = array();

    if ( !empty( $object->post_type ) ){
        $name = $object->post_name;

        $templates[] = "single-".$object->post_type.'-'.$name.'.php';
        $templates[] = "single-".$object->post_type.'-'.$object->ID.'.php';
        $templates[] = "single-".$object->post_type.'.php';

    }
    $templates[] = "single-".$name.'.php';
    $templates[] = "single-".$object->ID.'.php';
    $templates[] = "single".'.php';
    $templates[] = "singlular".'.php';

    return $templates;
}


/**
 * Get the attachment templates based on MIME type
 */
function tidyt_get_attachement_template_functions(){
    global $posts;
    $templates = array();

    if ( ! empty( $posts ) && isset( $posts[0]->post_mime_type ) ) {
        $type = explode( '/', $posts[0]->post_mime_type );

        if ( ! empty( $type ) ) {

            // personally I think we should be consistent and prepend the
            // templates with "attachment"
            $templates[] = $type[0].'_'.$type[1].'.php';
            // $templates[] = 'attachment-'.$type[0].'_'.$type[1].'.php';
            $templates[] = $type[1].'.php';
            // $templates[] = 'attachment-'.$type[1].'.php';
            $templates[] = $type[0].'.php';
            // $templates[] = 'attachment-'.$type[0].'.php';

        }
    }
    $templates[] = 'attachment'.'.php';
    $templates[] = 'single'.'.php';
    $templates[] = "singlular".'.php';
    return $templates;
}






/**
 * Get templates
 *
 * This starts to build the template tree
 * @return [type] [description]
 */
function tidyt_template($log = 'log--silent', $render = 'render-templates'){
    if( is_404() )
        $templates = array('404.php');
    elseif( is_search() )
        $templates = tidyt_get_simple_feed_templates('search');
    elseif( is_front_page() )
        $templates = tidyt_get_simple_feed_templates('front-page');
    elseif( is_home() )
        $templates = tidyt_get_simple_feed_templates('home');
    elseif( is_post_type_archive() )
         $templates = tidyt_get_post_type_archive_template_functions();
    elseif( is_tax() )
        $templates = tidyt_get_complex_feed_templates('taxonomy');
    elseif( is_attachment() )
        $templates = tidyt_get_attachement_template_functions();
    elseif( is_single() )
        $templates = tidyt_get_single_template_functions();
    elseif( is_page() )
        $templates = tidyt_get_page_template_functions();
    elseif( is_category() )
        $templates = tidyt_get_complex_feed_templates('category');
    elseif( is_tag() )
        $templates = tidyt_get_complex_feed_templates('tag');
    elseif( is_author() )
        $templates = tidyt_get_author_template_functions();
    elseif( is_date() )
        $templates = tidyt_get_simple_feed_templates('date');
    elseif( is_archive() )
        $templates = tidyt_get_archive_template_functions();
    elseif( is_comments_popup() )
        $templates = array('comments-popup');

    $templates[] = 'index.php';

    tidyt_log_template_hierarchy($log, $templates);
    if( $render !== 'dont-render-templates')
        tidyt_get_template($templates);

}
function kjd_identify_template($log = 'log--silent', $render = 'render-templates'){
    tidyt_template($log, $render);
}