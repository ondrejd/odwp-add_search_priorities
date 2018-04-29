<?php
/**
 * Plugin Name: Customize search results order
 * Plugin URI: https://github.com/ondrejd/odwp-add_search_priorities
 * Description: Plugin which customizes order of search results by additional priority value. It supports plain <a href="https://wordpress.org" target="blank">WordPress</a> as well as plugin <a href="https://www.relevanssi.com/" target="blank">Relevanssi</a>.
 * Version: 1.2.1
 * Author: Ondřej Doněk
 * Author URI: https://ondrejd.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Requires at least: 4.8
 * Tested up to: 4.9.5
 * Tags: search,meta box
 * Donate link: https://www.paypal.me/ondrejd
 * Text Domain: odwpasp
 * Domain Path: /languages/
 *
 * WordPress plugin odwp-add_search_priorities customizes search results order
 * by new meta value (priority).
 * 
 * Copyright (C) 2018 Ondřej Doněk
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
defined( 'ODWPASP_NONCE' ) || define( 'ODWPASP_NONCE', 'odwpasp_nonce' );
defined( 'ODWPASP_FILE' ) || define( 'ODWPASP_FILE', basename( __FILE__ ) );


if( !function_exists( 'odwpasp_admin_enqueue_scripts' ) ) :
    /**
     * Hook for "admin_enqueue_scripts" action.
     * @param string $hook
     * @return void
     * @since 1.0.0
     * @uses admin_url()
     * @uses plugins_url()
     * @uses wp_create_nonce()
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
                'admin_page'      => ODWPASP_ADMIN_PAGE,
                'admin_page_url'  => admin_url( 'tools.php?page=' . ODWPASP_ADMIN_PAGE ),
                'nonce'           => wp_create_nonce( ODWPASP_NONCE ),
                'no_term_msg'     => __( 'Firstly enter any search term…', 'odwpasp' ),
                'searching_msg'   => __( 'Searching…', 'odwpasp' ),
                'ajax_error_msg'  => __( 'There was an error so search can not be completed…', 'odwpasp' ),
                'saveprior_btn'   => __( 'Save priorities', 'odwpasp' ),
                'results_count'   => __( 'Results count: %1$d', 'odwpasp' ),
                'table_col_index' => __( 'Index', 'odwpasp' ),
                'table_col_prim'  => __( 'Search result', 'odwpasp' ),
                'table_col_prior' => __( 'Priority', 'odwpasp' ),
                'status_text'     => __( 'Status', 'odwpasp' ),
                'id_text'         => __( 'ID', 'odwpasp' ),
                'type_text'       => __( 'Type', 'odwpasp' ),
                'show_text'       => __( 'Show', 'odwpasp' ),
                'updating_msg'    => __( 'Updating priorities…', 'odwpasp' ),
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


if( !function_exists( 'odwpasp_create_test_search_wp_query' ) ) :
    /**
     * Creates {@see WP_Query} for the search terms.
     * @param string $search_term
     * @param string $post_status Optional.
     * @param integer $display_rows Optional.
     * @return WP_Query
     */
    function odwpasp_create_test_search_wp_query( $search_term, $post_status = 'published', $display_rows = -1 ) {
        $args = array(
            's' => $search_term,
            'post_type' => ['page', 'post'],
            'posts_per_page' => $display_rows,
            'nopagination' => true,
            'meta_key' => ODWPASP_META_KEY,
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        );

        if( $post_status == 'published' ) {
            $args['post_status'] = ['publish'];
        } elseif( $post_status == 'published_drafts' ) {
            $args['post_status'] = ['publish', 'auto-draft', 'draft'];
        } elseif( $post_status == 'private' ) {
            $args['post_status'] = ['private'];
        } else {
            $args['post_status'] = 'any';
        }

        return new WP_Query( $args );
    }
endif;


if( !function_exists( 'odwpasp_render_admin_page' ) ) :
    /**
     * Adds our admin page.
     * @link https://wpdreams.gitbooks.io/ajax-search-pro-documentation/content/priority_settings/individual-priorities.html - ukázka řešení
     * @return void
     * @since 1.0.0
     * @uses current_user_can()
     * @uses get_permalink()
     * @uses get_post_meta()
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

        $nonce_new = wp_create_nonce( ODWPASP_NONCE );
        $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : null;
        $term = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : null;
        $post_status = isset( $_POST['post_status'] ) ? sanitize_text_field( $_POST['post_status'] ) : null;
        $rows_count = isset( $_POST['rows_count'] ) ? ( ( $_POST['rows_count'] == '-1' ) ? -1 : intval( $_POST['rows_count'] ) ) : -1;
        $submitted_priorities = isset( $_POST['submit_priorities'] );
        $submitted_search = ( isset( $_POST['submit_search'] ) || $submitted_priorities );
        $is_valid_nonce = wp_verify_nonce( $nonce, ODWPASP_NONCE );
        $query = null;
        
        // Update submitted priorities
        if( $submitted_priorities && $is_valid_nonce ) {
            $priorities = filter_input( INPUT_POST, 'p', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );

            foreach( $priorities as $id => $v ) {
                update_post_meta( intval( $id ), ODWPASP_META_KEY, intval( $v ) );
            }
        }

        // Search is submitted
        if( $submitted_search && $is_valid_nonce ) {
            $query = odwpasp_create_test_search_wp_query( $term, $post_status );
            $results = odwpasp_process_test_search_wp_query( $query );
        }

?>
<div id="odwpasp-admin_page" class="wrap odwpasp">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Customize search results order', 'odwpasp' ) ?></h1>
    <hr class="wp-header-end">
    <?php if( ( $submitted_priorities || $submitted_search ) && !$is_valid_nonce ) : ?>
    <div class="notice notice-error s-dismissible">
        <p><?php esc_html_e( 'Request was not processed correctly, something wrong with security…', 'odwpasp' ) ?></p>
    </div>
    <?php endif ?>
    <div id="odwpasp-test_search_form_card" class="card">
        <h2 class="title"><?php esc_html_e( 'Test search', 'odwpasp' ) ?></h2>
        <form action="" id="odwpasp-test_search_form" method="POST">
            <input type="hidden" name="page" value="<?php echo ODWPASP_ADMIN_PAGE ?>">
            <input type="hidden" name="_wpnonce" value="<?php echo $nonce_new ?>">
            <div class="row">
                <span class="inline-input">
                    <label for="odwpasp-search_term"><?php esc_html_e( 'Search term: ', 'odwpasp' ) ?></label>
                    <input class="regular-text" id="odwpasp-search_term" name="search_term" type="text" value="<?php echo esc_attr( $term ) ?>" placeholder="<?php esc_attr_e( 'Enter search term...', 'odwpasp' ) ?>">
                </span>
                <span class="inline-input">
                    <label for="odwpasp-post_status"><?php esc_html_e( 'Post status: ', 'odwpasp' ) ?></label>
                    <select id="odwpasp-post_status" name="post_status" type="text" value="<?php echo $post_status ?>">
                        <option value="published" <?php selected( $post_status, 'published' ) ?>><?php esc_html_e( 'Published only', 'odwpasp' ) ?></option>
                        <option value="published_drafts" <?php selected( $post_status, 'published_drafts' ) ?>><?php esc_html_e( 'Published + Drafts', 'odwpasp' ) ?></option>
                        <option value="private" <?php selected( $post_status, 'private' ) ?>><?php esc_html_e( 'Private', 'odwpasp' ) ?></option>
                        <option value="all" <?php selected( $post_status, 'all' ) ?>><?php esc_html_e( 'All', 'odwpasp' ) ?></option>
                    </select>
                </span>
                <span class="inline-input">
                    <label for="odwpasp-rows_count"><?php esc_html_e( 'Rows: ', 'odwpasp' ) ?></label>
                    <select id="odwpasp-rows_count" name="rows_count" type="text" value="<?php echo $rows_count ?>">
                        <option value="25" <?php selected( $rows_count, 25 ) ?>><?php esc_html_e( '25', 'odwpasp' ) ?></option>
                        <option value="50" <?php selected( $rows_count, 50 ) ?>><?php esc_html_e( '50', 'odwpasp' ) ?></option>
                        <option value="100" <?php selected( $rows_count, 100 ) ?>><?php esc_html_e( '100', 'odwpasp' ) ?></option>
                        <option value="-1" <?php selected( $rows_count, -1 ) ?>><?php esc_html_e( 'All', 'odwpasp' ) ?></option>
                    </select>
                </span>
                <span class="inline-input">
                    <input id="odwpasp-search_submit_btn" name="submit_search" type="submit" value="<?php esc_attr_e( 'Search', 'odwpasp' ) ?>" class="button button-primary">
                    <span id="odwpasp-search_cancel_btn" href="<?php echo admin_url( 'tools.php?page=odwpasp-admin_page' ) ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'odwpasp' ) ?></span>
                </span>
            </div>
        </form>
    </div>
    <?php if( !$submitted_search ) : ?>
    <div class="card odwpasp-card">
        <p class="no-search-term-msg"><?php esc_html_e( 'Firstly enter any search term…', 'odwpasp' ) ?></p>
    </div>
    <?php else : ?>
    <?php if( count( $results ) == 0 ) : ?>
    <div class="card odwpasp-card">
        <p class="no-search-results-msg"><?php esc_html_e( 'No posts or pages found…', 'odwpasp' ) ?></p>
    </div>
    <?php else : ?>
    <form action="" class="odwpasp-results_card" method="POST">
        <input type="hidden" name="page" value="<?php echo ODWPASP_ADMIN_PAGE ?>">
        <input type="hidden" name="search_term" value="<?php echo $term ?>">
        <input type="hidden" name="_wpnonce" value="<?php echo $nonce_new ?>">
        <input type="hidden" name="post_status" value="<?php echo $post_status ?>">
        <input type="hidden" name="rows_count" value="<?php echo $rows_count ?>">
        <div class="form-container">
            <div class="form-container-inner">
                <div class="form-cell-left">
                    <input type="submit" id="odwpasp-priorities_submit" name="submit_priorities" value="<?php esc_attr_e( 'Save priorities', 'odwpasp' ) ?>" class="button button-primary">
                </div>
                <div class="form-cell-right">
                    <?php printf( esc_html__( 'Results count: %1$d', 'odwpasp' ), $query->post_count ) ?>
                </div>
            </div>
        </div>
        <table class="widefat fixed striped odwpasp-table">
            <thead>
                <tr>
                    <th class="col-1"><?php esc_html_e( 'Index', 'odwpasp' ) ?></th>
                    <th class="col-2 column-primary"><?php esc_html_e( 'Search result', 'odwpasp' ) ?></th>
                    <th class="col-3"><?php esc_html_e( 'Priority', 'odwpasp' ) ?></th>
                </tr>
            </thead>
            <tbody><?php
            for( $i = 0; $i < count( $results ); $i++ ) {
                $result = $results[$i];
                $status = sprintf( '%1$s: <em>%2$s</em> | ', esc_html__( 'Status', 'odwpasp' ), $result['status'] );
                $primary = sprintf(
                    '<b>%1$s</b> [%2$s: <code>%3$d</code> | %4$s: <em>%5$s</em> | %6$s<a href="%7$s" target="blank">%8$s <span class="dashicons dashicons-external"></span></a>]',
                    $result['title'],
                    esc_html__( 'ID', 'odwpasp' ),
                    $result['post_ID'],
                    esc_html__( 'Type', 'odwpasp' ),
                    $result['type'],
                    $status,
                    esc_url( get_permalink( $result['post_ID'] ) ),
                    esc_html__( 'Show', 'odwpasp' )
                );
                echo <<<EOC
<tr>
    <th class="col-1" scope="row">{$result['idx']}</th>
    <td class="col-2 column-primary">$primary</td>
    <td class="col-3"><input type="text" name="p[{$result['post_ID']}]" data-post_ID="{$result['post_ID']}" class="small-text odwpasp-priority_input" value="{$result['priority']}" class="input-priority"></td>
</tr>
EOC;
            }
            ?></tbody>
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
        wp_nonce_field( ODWPASP_NONCE );

?>
<p>
    <label for="odwpasp-priority"><?php esc_html_e( 'Priority:', 'odwpasp' ) ?></label>
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

        $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
        $is_valid_nonce = wp_verify_nonce( $nonce, ODWPASP_NONCE );

        if( !$is_valid_nonce ) {
            return $post_id;
        }

        $priority = filter_input( INPUT_POST, 'odwpasp-priority', FILTER_VALIDATE_INT );

        if( $priority !== false ) {
            update_post_meta( $post_id, ODWPASP_META_KEY, (int) $priority );
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


if( !function_exists( 'odwpasp_ajax_test_search_action' ) ) :
    /**
     * Process Ajax call for test search.
     * @return void
     * @since 1.1.0
     * @uses sanitize_text_field()
     * @uses wp_die()
     * @uses wp_json_encode()
     * @uses wp_create_nonce()
     * @uses wp_verify_nonce()
     */
    function odwpasp_ajax_test_search_action() {
        $nonce = $_POST['_wpnonce'];
        $nonce_valid = (bool) wp_verify_nonce( $nonce, ODWPASP_NONCE );
        $nonce_new = wp_create_nonce( ODWPASP_NONCE );
        $term = sanitize_text_field( $_POST['search_term'] );
        $status = sanitize_text_field( $_POST['post_status'] );
        $rows = ( $_POST['rows_count'] == '-1' ) ? -1 : intval( $_POST['rows_count'] );
        $query = odwpasp_create_test_search_wp_query( $term, $status, $rows );
        $out = array(
            'message' => null,
            'error' => false,
            'items' => array(),
            'term' => $term,
            'nonce' => $nonce,
            'nonce_valid' => $nonce_valid,
            'nonce_new' => $nonce_new,
            'rows' => $rows,
            'status' => $status,
        );

        if( $nonce_valid !== true ) {
            $out['message'] = __( 'Request was not processed correctly, something wrong with security…', 'odwpasp' );
            $out['error'] = true;
        }
        elseif( !( $query instanceof \WP_Query ) ) {
            $out['message'] = __( 'Firstly enter any search term…', 'odwpasp' );
            $out['error'] = true;
        } else {
            if( !$query->have_posts() ) {
                $out['message'] = __( 'No posts or pages found…', 'odwpasp' );
            } else {
                $out['items'] = odwpasp_process_test_search_wp_query( $query );
            }
        }

        echo wp_json_encode( $out );
        wp_die();
    }
endif;
add_action( 'wp_ajax_odwpasp_test_search', 'odwpasp_ajax_test_search_action' );


if( !function_exists( 'odwpasp_ajax_submit_priorities' ) ) :
    /**
     * Process Ajax query with submit priorities.
     * @return void
     * @since 1.1.0
     * @uses sanitize_text_field()
     * @uses update_post_meta()
     * @uses wp_die()
     * @uses wp_json_encode()
     * @uses wp_create_nonce()
     * @uses wp_verify_nonce()
     */
    function odwpasp_ajax_submit_priorities() {
        $nonce = $_POST['_wpnonce'];
        $nonce_valid = (bool) wp_verify_nonce( $nonce, ODWPASP_NONCE );
        $nonce_new = wp_create_nonce( ODWPASP_NONCE );
        $term = sanitize_text_field( $_POST['search_term'] );
        $status = sanitize_text_field( $_POST['post_status'] );
        $rows = ( $_POST['rows_count'] == '-1' ) ? -1 : intval( $_POST['rows_count'] );
        $priorities = $_POST['priorities'];
        $out = array(
            'message' => null,
            'error' => false,
            'items' => array(),
            'term' => $term,
            'nonce' => $nonce,
            'nonce_valid' => $nonce_valid,
            'nonce_new' => $nonce_new,
            'rows' => $rows,
            'status' => $status,
        );

        if( $nonce_valid !== true ) {
            $out['message'] = __( 'Request was not processed correctly, something wrong with security…', 'odwpasp' );
            $out['error'] = true;
        }
        elseif( count( $priorities ) < 1 ) {
            $out['message'] = __( 'No priorities - nothing to save…', 'odwpasp' );
        }
        else {
            // Firstly save priorities
            for( $i = 0; $i < count( $priorities ); $i++ ) {
                $p = $priorities[$i];
                update_post_meta( $p['post_ID'], ODWPASP_META_KEY, $p['value'] );
            }
        }

        if( $out['error'] !== true ) {
            // Initialize WP_Query
            $query = odwpasp_create_test_search_wp_query( $term, $status, $rows );

            // Process WP_Query
            if( !( $query instanceof \WP_Query ) ) {
                $out['message'] = __( 'Firstly enter any search term…', 'odwpasp' );
                $out['error'] = true;
            } else {
                if( !$query->have_posts() ) {
                    $out['message'] = __( 'No posts or pages found…', 'odwpasp' );
                } else {
                    $out['items'] = odwpasp_process_test_search_wp_query( $query );
                }
            }
        }

        echo wp_json_encode( $out );
        wp_die();
    }
endif;
add_action( 'wp_ajax_odwpasp_submit_priorities', 'odwpasp_ajax_submit_priorities' );


if( !function_exists( 'odwpasp_process_test_search_wp_query' ) ) :
    /**
     * Process given {@see WP_Query} and returns array of results (or empty array).
     * @param \WP_Query $query
     * @return array
     * @since 1.1.0
     * @uses esc_url()
     * @uses get_permalink()
     * @uses get_post_meta()
     * @uses get_post_status()
     * @uses get_post_type()
     * @uses get_the_ID()
     * @uses get_the_title()
     * @uses wp_reset_postdata()
     */
    function odwpasp_process_test_search_wp_query( \WP_Query $query ) {
        $out = array();
        $i = 0;

        while( $query->have_posts() ) {
            $i++;
            $query->the_post();
            $pid = get_the_ID();
            $val = (int) get_post_meta( $pid, ODWPASP_META_KEY, true );
            $out[] = array(
                'idx'       => $i,
                'title'     => get_the_title(),
                'post_ID'   => $pid,
                'type'      => get_post_type(),
                'status'    => get_post_status(),
                'permalink' => esc_url( get_permalink( $pid ) ),
                'priority'  => $val,
            );
            wp_reset_postdata();
        }

        return $out;
    }
endif;