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

    // find and load the template
    $template = tidyt_locate_file($templates, $path);

    if( tidyt_template_exists($template) )
      include($template);

    if( WP_AUTOLOAD ){
        // get the path to the template files
        $path = tidyt_get_constant_path('VIEWS');

        // find and load the template
        $template = tidyt_locate_file($templates, $path);
        include($template);
    }

    // end the script once a template is loaded
    die;
}


/**
 * loads the view or partial
 *
 * Takes in two arguments, the view (array for failover or string for strictness) and the data
 * the data should be an associatve array which gets unpacked, but you could pass in a
 * single variable too.
 */
function tidyt_render($files = array(), $data = array(), $base = null ) {

    $path = debug_backtrace();
    $from_controller = tidyt_from_controller($path);
    if($from_controller && WP_AUTOLOAD)
        return;

    // extracts the packed up data
    if(is_array($data))
        extract($data);

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
 * Automatically finds and loads view files which match the controller
 * For example. If the archive.php controller is loaded, then this loads the
 * archive.php view
 *
 * if the base directory and basefiles constants are defined, then this loads
 * the base templates to load the view into
 * @param  [array] $data [packaged up data from the controller]
 * @return [type]       [description]
 */
function tidyt_view($data){

    $files = $GLOBALS['templates'];

    $path = debug_backtrace();

    $from_controller = tidyt_from_controller($path);
    if($from_controller && !WP_AUTOLOAD)
        tidyt_base($files, $data);
    elseif($from_controller && WP_AUTOLOAD)
        return;
    else
        tidyt_render($templates, $data);

    // // extracts the packed up data
    // if(is_array($data))
    //     extract($data);
    //
    // // if a string (single) view is supplied, throw it into an empty array
    // // that way we can use the same code
    // if(is_string($files)){
    //     $files = array($files);
    // }
    //
    // $path = tidyt_get_constant_path('VIEWS');
    // // find and load the template
    // $template = tidyt_locate_file($files, $path);
    //
    // include($template);
    //
    // // end the script once the view is loaded
    // die;
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


function tidyt_base($template, $data = array() ){

    if( !defined('WP_BASE_TEMPLATES') )
        return false;

    $files = strpos(WP_BASE_TEMPLATES, ',') ? implode(',',WP_BASE_TEMPLATES) : array(WP_BASE_TEMPLATES);
    $base_path = defined('WP_BASE_DIRECTORY') ? WP_BASE_DIRECTORY : '';
    $path = tidyt_get_constant_path('VIEWS')  . $base_path . '/';
    $base_template = tidyt_locate_file($files, $path);
    if( !$base_template )
        return;

    include($base_template);
    die;
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
 * @param  string $slug the base name(header/footer ect)
 * @param  string $name base extension/identifier
 */
function tidyt_build_template_part($slug, $name = null){

    $templates = array();

    if(!empty($name) && $name[0] !== ' ')
        $templates[] = $slug.'-'.$name;

    $templates[] = $slug;
    tidyt_get_template( $templates );
}

function tidyt_get_template_part($slug, $name = null){
    tidyt_build_template_part($slug, $name);
}


function tidyt_get_sidebar($name = null){
    tidyt_build_template_part('sidebar', $name);
}

function tidyt_get_footer($name = null){
    tidyt_build_template_part('footer', $name);
}

function tidyt_get_header($name = null){
    tidyt_build_template_part('header', $name);
}
