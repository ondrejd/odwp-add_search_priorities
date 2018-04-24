<?php
/**
 * Plugin Name: Customize search results order
 * Plugin URI: https://github.com/ondrejd/odwp-add_search_priorities
 * Description: Plugin which customizes order of search results by additional priority value. It supports plain <a href="https://wordpress.org" target="blank">WordPress</a> as well as plugin <a href="https://www.relevanssi.com/" target="blank">Relevanssi</a>.
 * Version: 1.1.0
 * Author: Ondřej Doněk
 * Author URI: https://ondrejd.com/
 * License: GPLv3
 * Requires at least: 4.8
 * Tested up to: 4.8.5
 * Tags: search,meta box
 * Donate link: https://www.paypal.me/ondrejd
 * Text Domain: odwpasp
 * Domain Path: /languages/
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @link https://github.com/ondrejd/odwp-add_search_priorities for the canonical source repository
 * @package odwp-add_search_priorities
 * @since 1.0.0
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

defined( 'ODWPASP_ADMIN_PAGE' ) || define( 'ODWPASP_ADMIN_PAGE', 'odwpasp-admin_page' );
defined( 'ODWPASP_META_KEY' ) || define( 'ODWPASP_META_KEY', 'odwpasp-priority' );
defined( 'ODWPASP_FILE' ) || define( 'ODWPASP_FILE', basename( __FILE__ ) );


if( !function_exists( 'odwpasp_admin_enqueue_scripts' ) ) :
    /**
     * Hook for "admin_enqueue_scripts" action.
     * @param string $hook
     * @return void
     * @since 1.0.0
     * @uses plugins_url()
     * @uses wp_enqueue_script()
     * @uses wp_enqueue_style()
     * @uses wp_localize_script()
     */
    function odwpasp_admin_enqueue_scripts( $hook ) {
        $js_file = 'assets/js/admin.js';
        $js_path = dirname( __FILE__ ) . '/' . $js_file;

        if( file_exists( $js_path ) && is_readable( $js_path ) ) {
        wp_enqueue_script( 'odwpasp', plugins_url( $js_file, __FILE__ ), ['jquery'] );
            wp_localize_script( 'odwpasp', 'odwpasp', array(
                'admin_page_url' => admin_url( 'tools.php?page=odwpasp-admin_page' )
            ) );
        }

        $css_file = 'assets/css/admin.css';
        $css_path = dirname( __FILE__ ) . '/' .  $css_file;

        if( file_exists( $css_path ) && is_readable( $css_path ) ) {
            wp_enqueue_style( 'odwpasp', plugins_url( $css_file, __FILE__ ) );
        }
    }
endif;
add_action( 'admin_enqueue_scripts', 'odwpasp_admin_enqueue_scripts' );


if( !function_exists( 'odwpasp_add_admin_page' ) ) :
    /**
     * Adds our admin page.
     * @return void
     * @since 1.0.0
     * @uses add_submenu_page()
     */
    function odwpasp_add_admin_page() {
        add_submenu_page(
            'tools.php',
            __( 'Customize search results order', 'odwpasp' ),
            __( 'Customize search', 'odwpasp' ),
            'manage_options',
            ODWPASP_ADMIN_PAGE,
            'odwpasp_render_admin_page'
        );
    }
endif;
add_action( 'admin_menu', 'odwpasp_add_admin_page' );


if( !function_exists( 'odwpasp_render_admin_page' ) ) :
    /**
     * Adds our admin page.
     * @link https://wpdreams.gitbooks.io/ajax-search-pro-documentation/content/priority_settings/individual-priorities.html - ukázka řešení
     * @return void
     * @since 1.0.0
     * @todo Remove all inline styles and move them into proper CSS file!
     * @uses current_user_can()
     * @uses sanitize_text_field()
     * @uses wp_die()
     * @uses wp_nonce_field()
     * @uses wp_verify_nonce()
     * @uses update_post_meta()
     */
    function odwpasp_render_admin_page() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You have incuffient permissions for accessing this page.', 'odwpasp' ) );
        }

        $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : ( isset( $_POST['term'] ) ? sanitize_text_field( $_POST['term'] ) : '' );
        $post_status = isset( $_GET['post_status'] ) ? $_GET['post_status'] : ( isset( $_POST['post_status'] ) ? $_POST['post_status'] : '' );
        $submitted_priorities = isset( $_POST['submit_priorities'] );
        $submitted_search = ( isset( $_GET['submit_search'] ) || $submitted_priorities );
        $is_valid_nonce = false;
        $query = null;

        if( $submitted_priorities ) {
            $is_valid_nonce = ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'odwpasp_nonce' ) );
        }
        elseif( $submitted_search ) {
            $is_valid_nonce = ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'odwpasp_nonce' ) );
        }
        
        if( $submitted_priorities && $is_valid_nonce ) {
            foreach( $_POST['p'] as $id => $v ) {
                update_post_meta( $id, ODWPASP_META_KEY, $v );
            }
        }

        if( $submitted_search && $is_valid_nonce ) {
            $args = array(
                's' => $term,
                'post_type' => ['page', 'post'],
                'posts_per_page' => -1,
                'nopagination' => true,
                'meta_key' => ODWPASP_META_KEY,
                'orderby' => 'meta_value_num',
                'order' => 'DESC'
            );

            if( $post_status == 'published' ) {
                $args['post_status'] = 'publish';
            } elseif( $post_status == 'published_drafts' ) {
                $args['post_status'] = ['publish', 'draft'];
            } elseif( $post_status == 'private' ) {
                $args['post_status'] = 'private';
            } else {
                $args['post_status'] = 'any';
            }

            $query = new WP_Query( $args );
        }

?>
<div class="wrap odwpasp">
    <h1 class="wp-heading-inline"><?php _e( 'Customize search results order', 'odwpasp' ) ?></h1>
    <hr class="wp-header-end">
    <?php if( ( $submitted_priorities || $submitted_search ) && !$is_valid_nonce ) : ?>
    <div class="notice notice-error s-dismissible">
        <p><?php _e( 'Request was not processed correctly, something wrong with security&hellip;', 'odwpasp' ) ?></p>
    </div>
    <?php endif ?>
    <div class="card">
        <h2 class="title" style="margin-bottom:0;"><?php _e( 'Test search', 'odwpasp' ) ?></h2>
        <form action="" method="GET">
            <input type="hidden" name="page" value="<?php echo ODWPASP_ADMIN_PAGE ?>">
            <?php wp_nonce_field( 'odwpasp_nonce' ) ?>
            <span class="inline-input">
                <label for="odwpasp-search_term"><?php _e( 'Search term: ', 'odwpasp' ) ?></label>
                <input class="regular-text" id="odwpasp-search_term" name="term" type="text" value="<?php esc_attr( $term ) ?>">
            </span>
            <span class="inline-input">
                <label for="odwpasp-post_status"><?php _e( 'Post status: ', 'odwpasp' ) ?></label>
                <select class="" id="odwpasp-post_status" name="post_status" type="text" value="<?php echo $post_status ?>">
                    <option value="published" <?php selected( $post_status, 'published' ) ?>><?php _e( 'Published only', 'odwpasp' ) ?></option>
                    <option value="published_drafts" <?php selected( $post_status, 'published_drafts' ) ?>><?php _e( 'Published + Drafts', 'odwpasp' ) ?></option>
                    <option value="private" <?php selected( $post_status, 'private' ) ?>><?php _e( 'Private', 'odwpasp' ) ?></option>
                    <option value="all" <?php selected( $post_status, 'all' ) ?>><?php _e( 'All', 'odwpasp' ) ?></option>
                </select>
            </span>
            <input id="odwpasp-search_submit_btn" name="submit_search" type="submit" value="<?php esc_attr_e( 'Search', 'odwpasp' ) ?>" class="button button-primary">
            <span id="odwpasp-search_cancel_btn" href="<?php echo admin_url( 'tools.php?page=odwpasp-admin_page' ) ?>" class="button button-secondary"><?php _e( 'Cancel', 'odwpasp' ) ?></span>
        </form>
    </div>
    <?php if( !$submitted_search ) : ?>
    <div class="card">
        <p class="no-search-term-msg"><?php _e( 'Firstly enter any search term&hellip;', 'odwpasp' ) ?></p>
    </div>
    <?php else : ?>
    <?php if( !$query->have_posts() ) : ?>
    <div class="card">
        <p class="no-search-results-msg"><?php _e( 'No posts or pages found&hellip;', 'odwpasp' ) ?></p>
    </div>
    <?php else : $i = 0; ?>
    <form action="" method="POST">
        <input type="hidden" name="page" value="<?php echo ODWPASP_ADMIN_PAGE ?>">
        <input type="hidden" name="term" value="<?php echo $term ?>">
        <?php wp_nonce_field( 'odwpasp_nonce' ) ?>
        <div class="form-container">
            <div class="form-container-inner">
                <div class="form-cell-left">
                    <input type="submit" id="odwpasp-priorities_submit" name="submit_priorities" value="<?php esc_attr_e( 'Save priorities', 'odwpasp' ) ?>" class="button button-primary">
                </div>
                <div class="form-cell-right">
                    <?php printf( __( 'Results count: %1$d', 'odwpasp' ), $query->post_count ) ?>
                </div>
            </div>
        </div>
        <table class="widefat fixed striped odwpasp-table">
            <thead>
                <th class="col-1"><?php _e( 'I.', 'odwpasp' ) ?></th>
                <th class="col-2 column-primary"><?php _e( 'Search result', 'odwpasp' ) ?></th>
                <th class="col-3"><?php _e( 'Priority', 'odwpasp' ) ?></th>
            </thead>
            <tbody>
            <?php while ( $query->have_posts() ) :
                $i++;
                $query->the_post();
                $pid = get_the_ID();
                $val = (int) get_post_meta( $pid, ODWPASP_META_KEY, true );
            ?>  <tr>
                    <th class="" scope="row" style="text-align:center;"><?php echo $i ?></th>
                    <td class="column-primary">
                        <?php if( $post_status == 'publish' || $post_status == 'private' ) : ?>
                        <?php printf(
                            __( '<b>%1$s</b> [ID: <code>%2$d</code> | Type: <em>%3$s</em> | <a href="%4$s" target="blank">Show <span class="dashicons dashicons-external"></span></a>]', 'odwpasp' ),
                            get_the_title(), $pid, get_post_type(), esc_url( get_permalink( get_page_by_title( $pid ) ) )
                        ) ?>
                        <?php else: ?>
                        <?php printf(
                            __( '<b>%1$s</b> [ID: <code>%2$d</code> | Type: <em>%3$s</em> | Status: <em>%4$s</em> | <a href="%5$s" target="blank">Show <span class="dashicons dashicons-external"></span></a>]', 'odwpasp' ),
                            get_the_title(), $pid, get_post_type(), get_post_status(), esc_url( get_permalink( get_page_by_title( $pid ) ) )
                        ) ?>
                        <?php endif ?>
                    </td>
                    <td>
                        <input type="text" name="p[<?php echo $pid ?>]" class="small-text" value="<?php esc_attr_e( $val ) ?>" class="input-priority">
                    </td>
                </tr>
            <?php endwhile ?>
            <?php wp_reset_postdata() ?>
            </tbody>
        </table>
        <p>
            <input type="submit" id="odwpasp-priorities_submit" name="submit_priorities" value="<?php esc_attr_e( 'Save priorities', 'odwpasp' ) ?>" class="button button-primary">
        </p>
    </form>
    <?php endif ?>
    <?php endif ?>
</div>
<?php
    }
endif;


if( !function_exists( 'odwpasp_add_meta_to_all_posts' ) ) :
    /**
     * Adds our priority meta key to all posts/pages.
     * @return void
     * @since 1.0.0
     * @uses get_the_ID()
     * @uses update_post_meta()
     * @uses wp_reset_postdata()
     */
    function odwpasp_add_meta_to_all_posts() {
        $query = new WP_Query( array(
            'post_type' => ['page', 'post'],
            'posts_per_page' => -1,
            'nopagination' => true,
            'ignore_sticky_posts' => 1
        ) );
        
        while( $query->have_posts() ) {
            $query->the_post();
            $pid = get_the_ID();

            update_post_meta( $pid, ODWPASP_META_KEY, 0 );
            wp_reset_postdata();
        }
    }
endif;
register_activation_hook( __FILE__, 'odwpasp_add_meta_to_all_posts' );


if( !function_exists( 'odwpasp_relevanssi_modify_wp_query' ) ) :
    /**
     * Add order to Relevanssi query.
     * @global string $odwpasp_priority_sort
     * @param WP_Query $q
     * @return WP_Query
     * @since 1.0.0
     */
    function odwpasp_relevanssi_modify_wp_query( $q ) {
        global $odwpasp_priority_sort;

        if( isset( $q->query_vars['meta_key'] ) && $q->query_vars['meta_key'] == ODWPASP_META_KEY ) {
            $odwpasp_priority_sort = strtolower( $q->query_vars['order'] );
        }

        return $q;
    }
endif;
add_filter( 'relevanssi_modify_wp_query', 'odwpasp_relevanssi_modify_wp_query' );


if( !function_exists( 'odwpasp_pre_get_posts' ) ) :
    /**
     * Customizes order of search results by given priority.
     * @param WP_Query $q
     * @return void
     * @since 1.0.0
     * @uses is_admin()
     */
    function odwpasp_pre_get_posts( $q ) {
        if( !is_admin() && $q->is_main_query() ) {
            if( is_search() || $q->is_post_type_archive( ['page', 'post'] ) ) {
                $q->set( 'orderby', 'meta_value_num' );
                $q->set( 'order', 'DESC' );
                $q->set( 'meta_key', ODWPASP_META_KEY );
            }
        }
    }
endif;
add_filter( 'pre_get_posts', 'odwpasp_pre_get_posts', 99 );


if( !function_exists( 'odwpasp_sort_by_priority' ) ) :
    /**
     * @global string $odwpasp_priority_sort
     * @global WP_Query $wp_query
     * @link https://www.relevanssi.com/user-manual/relevanssi_hits_filter/
     * @param array $hits
     * @return array
     * @since 1.0.0
     * @uses get_post_meta()
     */
    function odwpasp_sort_by_priority( $hits ) {
        global $odwpasp_priority_sort, $wp_query;

        if( empty( $hits ) ) {
            return $hits;
        }

        if( isset( $odwpasp_priority_sort ) ) {
            $priorities = array();

            foreach( $hits[0] as $hit ) {
                $priority = (int) get_post_meta( $hit->ID, ODWPASP_META_KEY, true );

                if( !isset( $priorities[$priority] ) ) {
                    $priorities[$priority] = array();
                }

                array_push( $priorities[$priority], $hit );
            }

            if( $odwpasp_priority_sort == 'asc' ) {
                ksort( $priorities );
            } else {
                krsort( $priorities );
            }

            $sorted_hits = array();

            foreach( $priorities as $priority => $_hits ) {
                $sorted_hits = array_merge( $sorted_hits, $_hits );
            }

            $hits[0] = $sorted_hits;
        }
        
        return $hits;
    }
endif;
add_filter( 'relevanssi_hits_filter', 'odwpasp_sort_by_priority' );


if( !function_exists( 'odwpasp_add_query_var' ) ) :
    /**
     * Add our meta key into query vars.
     * @param array $query_vars
     * @return array
     * @since 1.0.0
     */
    function odwpasp_add_query_var( $query_vars ) {
        $query_vars[] = ODWPASP_META_KEY;
        return $query_vars;
    }
endif;
add_filter( 'query_vars', 'odwpasp_add_query_var' );


if( !function_exists( 'odwpasp_register_meta_boxes' ) ) :
    /**
     * Register priority meta box.
     * @return void
     * @since 1.0.0
     * @uses add_meta_box()
     */
    function odwpasp_register_meta_boxes() {
        add_meta_box(
            'odwpasp-priority_metabox',
            __( 'Search order priority', 'odwpasp' ),
            'odwpasp_priority_metabox_render',
            ['page', 'post'], 'side', 'high'
        );
    }
endif;
add_action( 'add_meta_boxes', 'odwpasp_register_meta_boxes' );


if( !function_exists( 'odwpasp_priority_metabox_render' ) ) :
    /**
     * Render priority meta box.
     * @param WP_Post $post
     * @return void
     * @since 1.0.0
     * @uses wp_nonce_field()
     */
    function odwpasp_priority_metabox_render( $post ) {
        $priority = get_post_meta( $post->ID, ODWPASP_META_KEY, true );
        wp_nonce_field( 'odwpasp_nonce' );

?>
<p>
    <label for="odwpasp-priority"><?php _e( 'Priority:', 'odwpasp' ) ?></label>
    <input class="short-text" id="odwpasp-priority" name="odwpasp-priority" type="text" value="<?php echo esc_attr( $priority ) ?>">
</p>
<?php
    }
endif;


if( !function_exists( 'odwpasp_priority_metabox_save' ) ) :
    /**
     * Save meta box content.
     * @param int $post_id
     * @param WP_Post $post
     * @return void
     * @since 1.0.0
     * @uses current_user_can()
     * @uses wp_verify_nonce()
     * @uses update_post_meta()
     * 
     */
    function odwpasp_priority_metabox_save( $post_id, $post ) {

        if( !current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if( 'post' != $post->post_type && 'page' != $post->post_type ) {
            return $post_id;
        }

        $is_valid_nonce = ( isset( $_POST['odwpasp_nonce'] ) && wp_verify_nonce( $_POST['odwpasp_nonce'], ODWPASP_FILE ) );

        if( !$is_valid_nonce ) {
            return $post_id;
        }

        if( isset($_POST['odwpasp-priority'] ) ) {
            update_post_meta( $post_id, ODWPASP_META_KEY, $_POST['odwpasp-priority'] );
        }
        
        return $post_id;
    }
endif;
add_action( 'save_post', 'odwpasp_priority_metabox_save', 99, 2 );


if( !function_exists( 'odwpasp_load_textdomain' ) ) :
    /**
     * Load plugin's localization.
     * @param int $post_id
     * @param WP_Post $post
     * @return void
     * @since 1.0.0
     * @uses load_plugin_textdomain()
     */
    function odwpasp_load_textdomain() {
        load_plugin_textdomain( 'odwpasp', false, 'odwp-add_search_priorities/languages/' );
    }
endif;
add_action( 'plugins_loaded', 'odwpasp_load_textdomain' );
