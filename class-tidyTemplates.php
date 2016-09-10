<?php

class tidyTemplates{

    public static $base_template;
    public static $template_to_load;
    public static $tempaltes_order = array();
    public static $current_wp_template;

    public static $settings = array();
    public static $data = array();

    static function init($settings){
        self::$settings = $settings;
        self::tidyt_template();
    }


    /**
     * This will take an object or array and if not empty, display the contents
     * in a human readable way.
     *
     * @param  [object/array] $object [description]
     */
    static function tidyt_examine($object = null){
        if($object === null )
            return false;

        echo '<pre>';
        print_r($object);
        die;
    }


    /**
     * This logs the template hierarchy to either the console or page for debugging
     * @return [type] [description]
     */
    static function tidyt_log_template_hierarchy($log = 'log--silent', $templates = null){

        if( $log == 'log--silent' ||  empty($templates ) )
            return;

        if($log == 'log--soft')
            error_log( print_r($templates, true) );
        elseif($log == 'log--hard')
            self::tidyt_examine($templates);

    }






    /**
     * get the paged template, based on post type/taxonomy and arg
     * we want to not only look for the current page's template. but also the
     * next highest tempalte available
     */
    static function tidyt_get_paged_template_functions($type, $arg=null){
        if($arg != null )
            $arg = '-'.$arg;

        $templates = array();
        $paged = array();
        $page = get_query_var('paged') ? get_query_var('paged') : '' ;

        if( is_paged() ){
            $templates[] = $type.$arg.'-paged-'.$page.'.php';
            $templates[] = self::tidyt_get_highest_available_page( $type.$arg, $page );

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
    static function tidyt_get_highest_available_page( $string, $page ){


        $files = array();
        $path = self::tidyt_get_constant_path('TEMPLATE');

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
    static function tidyt_get_simple_feed_templates($name){
        $templates = array();
        $paged = array();

        $paged = self::tidyt_get_paged_template_functions($name);

        $templates = array_merge($paged, $templates);

        return $templates;
    }


    /**
     * Gets the templates for a complex feed
     * @param  string $feed [description]
     * @return [type]       [description]
     */
    static function tidyt_get_complex_feed_templates($feed = ''){

        $object = get_queried_object();
        $name = $feed == 'taxonomy' ? 'taxonomy-' : '';
        $templates = array();
        $paged = array();

        if ( ! empty( $object->slug ) ) {

            $slug = $object->slug;
            $term_id = $feed == 'taxonomy' ? '' : $object->term_id;

            $cats = array($term_id, $slug);
            foreach ($cats as $k=>$arg):

                $paged = self::tidyt_get_paged_template_functions($name.$object->taxonomy, $arg);
                $templates = array_merge($paged, $templates);

            endforeach;

        }

        foreach(array($feed, 'archive') as $v){
            $paged = self::tidyt_get_paged_template_functions($v);
            $templates = array_merge($templates,$paged);
        }


        return $templates;
    }


    /**
     * Archive template functions
     * @return [type] [description]
     */
    static function tidyt_get_archive_template_functions(){

        $post_types = array_filter( (array) get_query_var( 'post_type' ) );

        $templates = array();
        $paged = array();

        // if the archive is of a CPT
        if ( count( $post_types ) == 1 ) {
            $post_type = reset( $post_types );
            $paged = self::tidyt_get_paged_template_functions('archive', $post_type);

        }

        $paged = self::tidyt_get_paged_template_functions('archive', $post_type);

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
    static function tidyt_get_post_type_archive_template_functions() {

        $post_type = get_query_var( 'post_type' );
        if ( is_array( $post_type ) )
            $post_type = reset( $post_type );

        $obj = get_post_type_object( $post_type );
        if ( ! $obj->has_archive )
            return '';

        return self::tidyt_get_archive_template_functions();
    }


    /**
     * get the author templates
     * @return [type] [description]
     */
    static function tidyt_get_author_template_functions(){

        $author = get_queried_object();

        $templates = array();

        if ( is_a( $author, 'WP_User' ) ) {

            $user_nicename = $author->user_nicename;
            $user_id = $author->ID;

            $users = array($user_id, $user_nicename);
            foreach ($users as $k=>$arg):

                $paged = self::tidyt_get_paged_template_functions('author', $arg);
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
    static function tidyt_get_page_template_functions(){

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
    static function tidyt_get_single_template_functions(){

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
    static function tidyt_get_attachement_template_functions(){
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
     * If the template is not found we need to do something
     * @param  [type] $template [description]
     * @return [type]           [description]
     */
    static function tidyt_template_exists($template){
        if(!file_exists($template))
            return false;

        return true;
    }















    /**
     * Check to see whether or not the WP_TEMPLATE_DIRECTORY constant was set
     * and display a warning if it has not.
     * @return [type] [description]
     */
    static function tidyt_configured(){
      if( !defined('WP_TEMPLATE_DIRECTORY') )
        exit('<h1>WP_TEMPLATE_DIRECTORY was not defined.</h1>  Add it to your wp-config.php file!');
    }


    /**
     * This is where we actually find and load the file
     *
     * Its reused by a couple functions so its abstracted out
     * @param  array  $files The list of files to look for, can either be an array for failovers or a string
     * @param  string $path  the path inside the theme to the files
     * @param  array/string  $data  The data to extract, can either be an array or a string
     */
    static function tidyt_locate_file( $files = array(), $path = '' ){


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
    static function tidyt_get_template($templates = array() ){

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
        $path = self::tidyt_get_constant_path('TEMPLATE');

        // find and load the template
        $template = self::tidyt_locate_file($templates, $path);

        if( self::tidyt_template_exists($template) )
          include($template);

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
    static function tidyt_render($files = array(), $data = array(), $base = null ) {

        // extracts the packed up data
        if(is_array($data))
            extract($data);

        // if a string (single) view is supplied, throw it into an empty array
        // that way we can use the same code
        if(is_string($files)){
            $files = array($files);
        }

        $path = self::tidyt_get_constant_path('VIEWS');
        // find and load the template
        $template = self::tidyt_locate_file($files, $path);

        include($template);
    }



    /**
     * Checks to see if tidyt_render was called from the template Directory
     * as opposed to the views. This allows us to use the same function both for
     * partials and for building the initial view with a base template
     * @return [type] [description]
     */
    static function tidyt_from_controller($path){

        $path = $path[0]['file'];
        $path = pathinfo($path);
        $path = explode('/', $path['dirname']);
        $path = $path[count($path)-1];

        if($path === WP_TEMPLATE_DIRECTORY)
            return true;
        else
            return false;
    }


    static function tidyt_base($template, $data = array() ){

        if( !defined('WP_BASE_TEMPLATES') )
            return false;

        $files = strpos(WP_BASE_TEMPLATES, ',') ? implode(',',WP_BASE_TEMPLATES) : array(WP_BASE_TEMPLATES);
        $base_path = defined('WP_BASE_DIRECTORY') ? WP_BASE_DIRECTORY : '';
        $path = self::tidyt_get_constant_path('VIEWS')  . $base_path . '/';
        $base_template = self::tidyt_locate_file($files, $path);
        if( !$base_template )
            return;

        include($base_template);
        die;
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
    static function tidyt_view($data){

        $files = $GLOBALS['templates'];

        $path = debug_backtrace();

        if(self::tidyt_from_controller($path))
            self::tidyt_base($files, $data);

        // extracts the packed up data
        if(is_array($data))
            extract($data);

        // if a string (single) view is supplied, throw it into an empty array
        // that way we can use the same code
        if(is_string($files)){
            $files = array($files);
        }

        $path = self::tidyt_get_constant_path('VIEWS');
        // find and load the template
        $template = self::tidyt_locate_file($files, $path);

        include($template);

        // end the script once the view is loaded
        die;
    }


    /**
     * This checks to see if the WP_{*}_DIRECTORY constant was set.  This constant
     * is used to override the location wordpress looks for templates.
     *
     * if its not set, an empty string is returned, thereby falling back on the WP default
     * @return [type] [description]
     */
    static function tidyt_get_constant_path($constant){

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
    static function tidyt_build_template_part($slug, $name = null){

        $templates = array();

        if(!empty($name) && $name[0] !== ' ')
            $templates[] = $slug.'-'.$name;

        $templates[] = $slug;
        self::tidyt_get_template( $templates );
    }

    static function tidyt_get_template_part($slug, $name = null){
        self::tidyt_build_template_part($slug, $name);
    }


    static function tidyt_get_sidebar($name = null){
        self::tidyt_build_template_part('sidebar', $name);
    }

    static function tidyt_get_footer($name = null){
        self::tidyt_build_template_part('footer', $name);
    }

    static function tidyt_get_header($name = null){
        self::tidyt_build_template_part('header', $name);
    }


    /**
     * Get templates
     *
     * This starts to build the template tree
     * @return [type] [description]
     */
    public static function tidyt_template($log = 'log--silent', $render = 'render-templates', $args = array() ){

        self::tidyt_configured();

        if( is_404() )
            $templates = array('404.php');
        elseif( is_search() )
            $templates = self::tidyt_get_simple_feed_templates('search');
        elseif( is_front_page() )
            $templates = self::tidyt_get_simple_feed_templates('front-page');
        elseif( is_home() )
            $templates = self::tidyt_get_simple_feed_templates('home');
        elseif( is_post_type_archive() )
             $templates = self::tidyt_get_post_type_archive_template_functions();
        elseif( is_tax() )
            $templates = self::tidyt_get_complex_feed_templates('taxonomy');
        elseif( is_attachment() )
            $templates = self::tidyt_get_attachement_template_functions();
        elseif( is_single() )
            $templates = self::tidyt_get_single_template_functions();
        elseif( is_page() )
            $templates = self::tidyt_get_page_template_functions();
        elseif( is_category() )
            $templates = self::tidyt_get_complex_feed_templates('category');
        elseif( is_tag() )
            $templates = self::tidyt_get_complex_feed_templates('tag');
        elseif( is_author() )
            $templates = self::tidyt_get_author_template_functions();
        elseif( is_date() )
            $templates = self::tidyt_get_simple_feed_templates('date');
        elseif( is_archive() )
            $templates = self::tidyt_get_archive_template_functions();
        elseif( is_comments_popup() )
            $templates = array('comments-popup');

        $templates[] = 'index.php';

        self::tidyt_log_template_hierarchy($log, $templates);
        if( $render !== 'dont-render-templates')
            self::tidyt_get_template($templates);

    }


}
