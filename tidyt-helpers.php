<?php

/**
 * This will take an object or array and if not empty, display the contents
 * in a human readable way.
 *
 * @param  [object/array] $object [description]
 */
function tidyt_examine($object = null){
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
function tidyt_log_template_hierarchy($log = 'log--silent', $templates = null){

    if( $log == 'log--silent' ||  empty($templates ) )
        return;

    if($log == 'log--soft')
        error_log( print_r($templates, true) );
    elseif($log == 'log--hard')
        tidyt_examine_object($templates);

}