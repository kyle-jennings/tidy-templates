<?php



/**
 * This is where we actually find and load the file
 *
 * Its reused by a couple functions so its abstracted out
 * @param  array  $files The list of files to look for, can either be an array for failovers or a string
 * @param  string $path  the path inside the theme to the files
 * @param  array/string  $data  The data to extract, can either be an array or a string
 */
function tidyt_locate_file( $files = array(), $path = '' ){


    // look for the template
    foreach($files as $file){
        // if the filename does not have a .php, then add it to the end
        if( strpos($file, '.php') === false ){
            $file = $file.'.php';
        }

        if($file == 'index.php' && $path == '')
            return false;

        if ( file_exists(STYLESHEETPATH . '/' . $path . $file )) {
            return STYLESHEETPATH . '/' . $path . $file ;
            break;
        } elseif ( file_exists(TEMPLATEPATH . '/' . $path . $file )) {
            return TEMPLATEPATH . '/' . $path . $file ;
            break;
        }

    }
    return false;
}


/**
 * loads the correct template file by traversing the template tree and loading the
 * first found file.
 */
function tidyt_get_template($templates = array() ){

    global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;
    $GLOBALS['templates'] = $templates;

    if ( is_array( $wp_query->query_vars ) ) {
        extract( $wp_query->query_vars, EXTR_SKIP );
    }

    if ( isset( $s ) ) {
        $s = esc_attr( $s );
    }

    // if a string (single) view is supplied, throw it into an empty array
    // that way we can use the same code
    if(is_string($templates))
        $templates = array($templates);

    // get the path to the template files
    $path = tidyt_get_constant_path('TEMPLATE');


    /**
    * Filter the list of templates that should be passed to locate_template().
    *
    * The last element in the array should always be the fallback template for this query type.
    *
    * Possible values for `$type` include: 'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date',
    * 'home', 'front_page', 'page', 'paged', 'search', 'single', 'singular', and 'attachment'.
    *
    * @since 4.4.0
    *
    * @param array $templates A list of template candidates, in descending order of priority.
    */
    $templates = apply_filters( "{$type}_template_hierarchy", $templates ); 

    // find and load the template
    $template = tidyt_locate_file($templates, $path);

    if( tidyt_template_exists($template) )
      include($template);



    $auto_load = tidyt_autoload();

    if($auto_load !== true)
        die;

    if( $auto_load === true ){
        // get the path to the template files
        $path = tidyt_get_constant_path('VIEWS');
        // find and load the template
        $template = tidyt_locate_file($templates, $path);

        include($template);

    }

    // end the script once a template is loaded
    die;
}


function tidyt_autoload(){
    if(!defined('WP_AUTOLOAD') )
        return false;

    return true;

}

/**
 * loads the view or partial
 *
 * Takes in two arguments, the view (array for failover or string for strictness) and the data
 * the data should be an associatve array which gets unpacked, but you could pass in a
 * single variable too.
 */
function tidyt_render($files = array(), $data = array(), $rename_data = null) {

    $from_controller = tidyt_from_controller($path);
    if($from_controller && WP_AUTOLOAD)
        return;

    // extracts the packed up data
    if(is_array($data)){
        extract($data);
        unset($data);
    }elseif($rename_data && $data) {
        ${$rename_data} = $data;
        unset($data);
    }


    // if a string (single) view is supplied, throw it into an empty array
    // that way we can use the same code
    if(is_string($files)){
        $files = array($files);
    }

    $path = tidyt_get_constant_path('VIEWS');
    // find and load the template
    $template = tidyt_locate_file($files, $path);

    include($template);
}


/**
 * Checks to see if tidyt_render was called from the template Directory
 * as opposed to the views. This allows us to use the same function both for
 * partials and for building the initial view with a base template
 * @return [type] [description]
 */
function tidyt_from_controller($path){

    $path = $path[0]['file'];
    $path = pathinfo($path);
    $path = explode('/', $path['dirname']);
    $path = $path[count($path)-1];

    if($path === WP_TEMPLATE_DIRECTORY)
        return true;
    else
        return false;
}



/**
 * This checks to see if the WP_{*}_DIRECTORY constant was set.  This constant
 * is used to override the location wordpress looks for templates.
 *
 * if its not set, an empty string is returned, thereby falling back on the WP default
 * @return [type] [description]
 */
function tidyt_get_constant_path($constant){

    $constant = 'WP_'.$constant.'_DIRECTORY';
    if( !constant($constant) || !defined($constant) || is_null(constant($constant)) ){
        return '';
    }
    // get the constant's value
    $path = constant($constant);
    // if the path exists then return it

    if( file_exists( STYLESHEETPATH . '/' . $path)  ){
        return $path. '/';
    } else if ( file_exists(TEMPLATEPATH . '/' . $path)) {
        return $path. '/';
    }

    return '';
}


/**
 * Provides similar functions to get_header/get_footer/get_sidebar/get_template_part
 *
 */
function tidyt_build_template_part($slug, $name = null){

    $templates = array();

    if(!empty($name) && $name[0] !== ' ')
        $templates[] = $slug.'-'.$name;

    $templates[] = $slug;
    tidyt_get_template( $templates );
}


/**
 * Loads a template part - only useful in the templates(controllers)
 */
function tidyt_get_template_part($slug, $name = null){
    tidyt_build_template_part($slug, $name);
}


/**
 * Loads a sidebar, uses a prefix for specific loading
 */
function tidyt_get_sidebar($name = null){
    tidyt_build_template_part('sidebar', $name);
}


/**
 * Loads a footer, uses a prefix for specific loading
 */
function tidyt_get_footer($name = null){
    tidyt_build_template_part('footer', $name);
}


/**
 * Loads a header, uses a prefix for specific loading
 */
function tidyt_get_header($name = null){
    tidyt_build_template_part('header', $name);
}
