<?php

/**
 * Plugin Name: Tidy Templates
 * Plugin URI: https://github.com/kyle-jennings/tidy-templates/
 * Description: Adds lots of new templates, and allows custom placement of template files
 * Version: 1.2.0
 * Author: Kyle Jennings
 * License: GPLv2
 */

$files = array(
    'helpers',
    'template',
    'functions',
    'search-form'
);
foreach($files as $file)
    require_once('tidyt-'.$file.'.php');


defined( 'ABSPATH' ) or die();
