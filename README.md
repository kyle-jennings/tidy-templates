Tidy Templates
================================
This plugin allows developers to move the default location of page templates, and adds additional templates.

### Install
2 ways to install:
 - Clone this into your plugins folder and activate via wp-admin/plugins.php page
 - via composer (installed as a must use plugin), see below
```
{
    "name": "my wordpress site",
    "description": "your description here",
    "repositories":[
        {
            "type": "vcs",
            "url": "https://github.com/kyle-jennings/tidy-templates"
        }
    ],
    "require":{
        "kyle-jennings/tidy-templates": "dev-master",
    },
    "extra": {
        "wordpress-install-dir": "wordpress/app",
        "installer-paths": {
            "wordpress/wp-content/plugins/{$name}": ["type:wordpress-plugin"],
            "wordpress/wp-content/mu-plugins/{$name}": ["type:wordpress-muplugin"],
            "wordpress/wp-content/themes/{$name}": ["type:wordpress-theme"]
        }
    }
}
```

### About

By default, Wordpress looks for template files in the root of the theme folder and only the root of the theme folder. Wordpress also only supports a limited (albeit satisfactory) set of templates.

Requiring template files to live in the theme root is not ideal as it forces developers to pollute their theme and doesn't follow any established file organization best practices.

By moving the template files into their own folder, the root of our themes could be as clean as:

```
├── theme-name/
|───lib/
|   ├── function files
|
|── css/
|── js/
|
|── controllers/
|   ├── index.php
|   ├── archive.php
|   ├── single.php
|   ├── page.php
|   ├── ect.php
|
├── style.css
├── index.php
├── functions.php
```

Doesn't that look nice?

I find this to be particularly useful when using the terrific [Timber plugin](http://upstatement.com/timber/). With Timber and this plugin, we could have a folder called **'views'** (for the timber files) and another called **'controllers'**. See where I'm going with this?

I also added logic for two additional templates: template specific pagination and filename matched custom post type templates.


### Template specific pagination
Did you know that wordpress has templates for paged feeds? After pager 1 of an archive feed, wordpress will look for paged.php to use. But wordpress applies the paged.php template to every feed, that means your posts, search results, custom post types, taxonomies, terms ect, will all share the same template.

I've added template specific paged support, so every feed can have it's own paged template. Furthermore, you can specify templates for specific page numbers, and to take it further, this plugin will use the highest number paged template found if the current page isnt found.


### Filename matched custom post type templates
Finally, custom post types are great and all but sometimes you only need a handful of posts in any given post type. It would be great to be able style a specific post of a given CPT like you would a page.

For example, if you had a post type called "bio", and a post for "sam-jackson" (the slug) with the id of 454, wordpress will first look for a file **"single-bio-sam-jackson.php"**, then **"single-bio-454.php**, **single-bio.php** and then fallback on wordpress's defaul templates to use.


### TLDR:

We went from this:
![the old template hirearchy](https://raw.githubusercontent.com/kyle-jennings/tidy-templates/master/img/old%20template%20hirearchy.png)

To this:
![my new template hirearchy](https://raw.githubusercontent.com/kyle-jennings/tidy-templates/master/img/new%20template%20hirearchy.png)


How to Use
==========
First, you need to define the folder location for your templates. In your theme functions.php file, define a constant 'WP_TEMPLATE_DIRECTORY' with your desired folder name (must be relative to the theme root):

```define('WP_TEMPLATE_DIRECTORY', 'templates');```

Now create a folder in your theme root called **"templates"** and throw your templates in there.

Then, in your theme root index.php file, you'll need to call the function to make the magic happen:

```
if( function_exists('tidyt_template') )
    tidyt_template();
```

And that's it. If the plugin finds the defined directory, then it will look for your template files there. Otherwise it will fall back on using the default location (the theme root).

This plugin follows Wordpress's rules for the template hirearchy, so if your template isn't found it will use the standard fall backs.

Rendering Views
================
This plugin can be used to create a MVC type of setup, a separation of presentation and logic.  With this setup, we can collect the data (and handle all the messy login) we want to display in a "controller" and send it to a "view" to display it.  That might look like this:



```
├── theme-name/
|───lib/
|   ├── function files
|
|── css/
|── js/
|
|── controllers/
|   ├── index.php
|   ├── archive.php
|   ├── single.php
|   ├── page.php
|   ├── ect.php
|
|
|───views/
|   ├── index.php
|   |── archive.php
|   |── single.php
|   |── page.php
|   |── ect.php
|
├── style.css
├── index.php
├── functions.php
```



The easiest way to do this is by adding another constant to the wp-config file:

```define('WP_VIEWS_DIRECTORY', 'views');```

And then in your template files, call the view with:

```
tidyt_render( array('file-name1','file-name2','file-name3'), $data  );
```
or
```
tidyt_render( 'file-name', $data  );
```

The $data variable should be an associative array containing all the data you want to output into the view. tidyt_render() will extract the $data array into respective variables to display in the view. Tutorials to come.

## Autoloading Views

You can also autoload your views (instead of calling the view form the controller with the tidyt_render function) by adding yet another constant in the wp-config file:

``` define('WP_AUTOLOAD', true) ```

Things get a little different here.

#### View filenames
Your view filenames must now match the controller filename (standard WordPress template filenames), these files will get included in when loading the template controllers.

#### Packaging up you data
While you still need to declare your variables, you cannot package them up. The view files will now be in the same scope as the controller so the variables will be available in the view automatically.


## Future Features
1. Option to automatically wrap templates or views with header and footer files (essentially a template base)
2. Maybe inject the settings (template locations, view locations, autoload ect ect) in the tidy_template() function
3. Refactor this thing as a static class
