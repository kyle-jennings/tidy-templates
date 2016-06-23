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
|   ├── lib/
|   |   ├── function files
|   ├── css/
|   ├── js
|   ├── templates
|   |   ├── index.php
|   |   ├── archive.php
|   |   ├── single.php
|   |   ├── page.php
|   |   ├── ect.php
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
if( function_exists('tidyt_identify_template') )
    tidyt_identify_template();
```

And that's it. If the plugin finds the defined directory, then it will look for your template files there. Otherwise it will fallback on using the default location (the theme root).

This plugin follows Wordpress's rules for the template hirearchy, so if your template isnt found it will use the standard fallbacks.

I would pretty up the index.php a little in case the folder/template isnt found. Just in case.


A couple notes
--------------
Header and Footer:
The get_header() and get_footer() functions are unfortuately stuck in the the theme root. So you'll either have to leave the header.php and footer.php in your theme root and call get_header() and get_footer(),  or simply use include or require in your template files can call files local to that foler.

I prefer the latter so I can keep things clean.

Logging:
While in development, a logging value can be passed into the tidyt_identify_template() function to display the template tree. "log--hard" prints the template hirearchy to the page, "log--soft" prints to php's error log, and "log--silent" does nothing.


Custom Page Templates:
Custom page templates will always trump other templates, and is loaded before index.php (despite hiding it in a folder), so if youre logging out the template tree you wont see a thing.
