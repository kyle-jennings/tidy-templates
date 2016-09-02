<?php
function tidyt_comments_template( $file = 'comments.php', $separate_comments = false ) {
    global $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;

    if ( !(is_single() || is_page() || $withcomments) || empty($post) )
        return;

    if ( empty($file) )
        $file = '/comments.php';

    $req = get_option('require_name_email');

    /*
     * Comment author information fetched from the comment cookies.
     */
    $commenter = wp_get_current_commenter();

    /*
     * The name of the current comment author escaped for use in attributes.
     * Escaped by sanitize_comment_cookies().
     */
    $comment_author = $commenter['comment_author'];

    /*
     * The email address of the current comment author escaped for use in attributes.
     * Escaped by sanitize_comment_cookies().
     */
    $comment_author_email = $commenter['comment_author_email'];

    /*
     * The url of the current comment author escaped for use in attributes.
     */
    $comment_author_url = esc_url($commenter['comment_author_url']);

    $comment_args = array(
        'orderby' => 'comment_date_gmt',
        'order' => 'ASC',
        'status'  => 'approve',
        'post_id' => $post->ID,
        'no_found_rows' => false,
        'update_comment_meta_cache' => false, // We lazy-load comment meta for performance.
    );

    if ( get_option('thread_comments') ) {
        $comment_args['hierarchical'] = 'threaded';
    } else {
        $comment_args['hierarchical'] = false;
    }

    if ( $user_ID ) {
        $comment_args['include_unapproved'] = array( $user_ID );
    } elseif ( ! empty( $comment_author_email ) ) {
        $comment_args['include_unapproved'] = array( $comment_author_email );
    }

    $per_page = 0;
    if ( get_option( 'page_comments' ) ) {
        $per_page = (int) get_query_var( 'comments_per_page' );
        if ( 0 === $per_page ) {
            $per_page = (int) get_option( 'comments_per_page' );
        }

        $comment_args['number'] = $per_page;
        $page = (int) get_query_var( 'cpage' );

        if ( $page ) {
            $comment_args['offset'] = ( $page - 1 ) * $per_page;
        } elseif ( 'oldest' === get_option( 'default_comments_page' ) ) {
            $comment_args['offset'] = 0;
        } else {
            // If fetching the first page of 'newest', we need a top-level comment count.
            $top_level_query = new WP_Comment_Query();
            $top_level_args  = array(
                'count'   => true,
                'orderby' => false,
                'post_id' => $post->ID,
                'status'  => 'approve',
            );

            if ( $comment_args['hierarchical'] ) {
                $top_level_args['parent'] = 0;
            }

            if ( isset( $comment_args['include_unapproved'] ) ) {
                $top_level_args['include_unapproved'] = $comment_args['include_unapproved'];
            }

            $top_level_count = $top_level_query->query( $top_level_args );

            $comment_args['offset'] = ( ceil( $top_level_count / $per_page ) - 1 ) * $per_page;
        }
    }

    /*
     * Filters the arguments used to query comments in comments_template().
     *
     * @since 4.5.0
     *
     * @see WP_Comment_Query::__construct()
     *
     * @param array $comment_args {
     *     Array of WP_Comment_Query arguments.
     *
     *     @type string|array $orderby                   Field(s) to order by.
     *     @type string       $order                     Order of results. Accepts 'ASC' or 'DESC'.
     *     @type string       $status                    Comment status.
     *     @type array        $include_unapproved        Array of IDs or email addresses whose unapproved comments
     *                                                   will be included in results.
     *     @type int          $post_id                   ID of the post.
     *     @type bool         $no_found_rows             Whether to refrain from querying for found rows.
     *     @type bool         $update_comment_meta_cache Whether to prime cache for comment meta.
     *     @type bool|string  $hierarchical              Whether to query for comments hierarchically.
     *     @type int          $offset                    Comment offset.
     *     @type int          $number                    Number of comments to fetch.
     * }
     */

    $comment_args = apply_filters( 'comments_template_query_args', $comment_args );
    $comment_query = new WP_Comment_Query( $comment_args );
    $_comments = $comment_query->comments;

    // Trees must be flattened before they're passed to the walker.
    if ( $comment_args['hierarchical'] ) {
        $comments_flat = array();
        foreach ( $_comments as $_comment ) {
            $comments_flat[]  = $_comment;
            $comment_children = $_comment->get_children( array(
                'format' => 'flat',
                'status' => $comment_args['status'],
                'orderby' => $comment_args['orderby']
            ) );

            foreach ( $comment_children as $comment_child ) {
                $comments_flat[] = $comment_child;
            }
        }
    } else {
        $comments_flat = $_comments;
    }

    /**
     * Filter the comments array.
     *
     * @since 2.1.0
     *
     * @param array $comments Array of comments supplied to the comments template.
     * @param int   $post_ID  Post ID.
     */
    $wp_query->comments = apply_filters( 'comments_array', $comments_flat, $post->ID );

    $comments = &$wp_query->comments;
    $wp_query->comment_count = count($wp_query->comments);
    $wp_query->max_num_comment_pages = $comment_query->max_num_pages;

    if ( $separate_comments ) {
        $wp_query->comments_by_type = separate_comments($comments);
        $comments_by_type = &$wp_query->comments_by_type;
    } else {
        $wp_query->comments_by_type = array();
    }

    $overridden_cpage = false;
    if ( '' == get_query_var( 'cpage' ) && $wp_query->max_num_comment_pages > 1 ) {
        set_query_var( 'cpage', 'newest' == get_option('default_comments_page') ? get_comment_pages_count() : 1 );
        $overridden_cpage = true;
    }

    if ( !defined('COMMENTS_TEMPLATE') )
        define('COMMENTS_TEMPLATE', true);

    $path = tidyt_get_constant_path('TEMPLATE');

    if ( file_exists(STYLESHEETPATH . '/' . $path . $file ))
        $comment_template =  STYLESHEETPATH . '/' . $path . $file ;
    elseif ( file_exists(TEMPLATEPATH . '/' . $path . $file ))
        $comment_template =  TEMPLATEPATH . '/' . $path . $file ;

    /**
     * Filter the path to the theme template file used for the comments template.
     *
     * @since 1.5.1
     *
     * @param string $theme_template The path to the theme template file.
     */
    $include = apply_filters( 'comments_template', $comment_template );

    include($include);
}

