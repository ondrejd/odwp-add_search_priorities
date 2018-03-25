<?php
/**
 * Plugin Name: Úprava zobrazení výsledků vyhledávání
 * Plugin URI: https://github.com/ondrejd/odwp-debug_log
 * Description: Úprava zobrazení výsledků vyhledávání dle priority. Podporuje buď defaultní chování systému <a href="https://wordpress.org" target="blank">WordPress</a> nebo plugin <a href="https://www.relevanssi.com/" target="blank">Relevanssi</a>.
 * Version: 1.0.0
 * Author: Ondřej Doněk
 * Author URI: https://ondrejd.com/
 * License: GPLv3
 * Requires at least: 4.8
 * Tested up to: 4.8.4
 * Tags: debug,log,development
 * Donate link: https://www.paypal.me/ondrejd
 *
 * Text Domain: odwpasp
 * Domain Path: /languages/
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-add_search_priorities
 * @since 1.0.0
 *
 * @todo vylepšit admin. stránku "Úprava vyhledávání" - přidat Ajax.
 * @todo u editace/přidávání příspěvků/stránek musí být meta box priority
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

defined( 'ODWPASP_ADMIN_PAGE' ) || define( 'ODWPASP_ADMIN_PAGE', 'odwpasp-admin_page' );
defined( 'ODWPASP_META_KEY' ) || define( 'ODWPASP_META_KEY', 'odwpasp-priority' );


if( !function_exists( 'odwpasp_admin_enqueue_scripts' ) ) :
    /**
     * Hook for "admin_enqueue_scripts" action.
     * @param string $hook
     * @return void
     * @since 1.0.0
     */
    function odwpasp_admin_enqueue_scripts( $hook ) {
        $js_file = 'assets/js/admin.js';
        $js_path = dirname( __FILE__ ) . '/' . $js_file;

        if( file_exists( $js_path ) && is_readable( $js_path ) ) {
        wp_enqueue_script( 'odwpasp', plugins_url( $js_file, __FILE__ ), ['jquery'] );
            wp_localize_script( 'odwpasp', 'odwpasp', [
                // Put variables you want to pass into JS here...
            ] );
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
     */
    function odwpasp_add_admin_page() {
        add_submenu_page(
            'tools.php',
            __( 'Úprava vyhledávání dle priority', 'odwpasp' ),
            __( 'Úprava vyhledávání', 'odwpasp' ),
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
     */
    function odwpasp_render_admin_page() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'Nemáte dostatečná oprávnění pro přístup k této stránce.', 'odwpasp' ) );
        }

        $term = isset( $_GET['term'] ) ? $_GET['term'] : ( isset( $_POST['term'] ) ? $_POST['term'] : '' );
        $submitted_priorities = isset( $_POST['submit_priorities'] );
        $submitted_search = ( isset( $_GET['submit_search'] ) || $submitted_priorities );
        $query = null;

        if( $submitted_priorities ) {
            foreach( $_POST['p'] as $id => $v ) {
                update_post_meta( $id, ODWPASP_META_KEY, $v );
            }
        }

        if( $submitted_search ) {
            if( function_exists( 'relevanssi_do_query' ) ) {
                $query = new WP_Query( array(
                    'post_type' => ['page', 'post'],
                    'posts_per_page' => -1,
                    'nopagination' => true
                ) );

                $query->query_vars['s'] = $term;
                $query->query_vars['post_status'] = 'publish';
                $query->query_vars['meta_key'] = ODWPASP_META_KEY;
                $query->query_vars['orderby'] = 'meta_value_num';
                $query->query_vars['order'] = 'DESC';

                relevanssi_do_query( $query );
            } else {
                $query = new WP_Query( array(
                    's' => $term,
                    'post_status' => 'publish',
                    'post_type' => ['page', 'post'],
                    'posts_per_page' => -1,
                    'nopagination' => true,
                    //'ignore_sticky_posts' => 1,
                    'meta_key' => ODWPASP_META_KEY,
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ) );
            }
        }

?>
<div class="wrap odwpasp">
    <h1 class="wp-heading-inline"><?php _e( 'Úprava vyhledávání dle priority', 'odwpasp' ) ?></h1>
    <hr class="wp-header-end">
    <div class="card" style="min-width:calc(100% - 4em);max-width:calc(100% - 4em);padding:0.7em 2em 1em 2em;">
        <h2 class="title"><?php _e( 'Zkušební vyhledávání', 'odwpasp' ) ?></h2>
        <form action="" method="GET">
            <input type="hidden" name="page" value="<?php echo ODWPASP_ADMIN_PAGE ?>">
            <div>
                <label for="odwpasp-search_term"><?php _e( 'Hledaný termín: ', 'odwpasp' ) ?></label>
                <input type="text" id="odwpasp-search_term" name="term" class="regular-text" value="<?php echo $term ?>">
                <input type="submit" id="odwpasp-search_submit" name="submit_search" value="<?php _e( 'Hledej', 'odwpasp' ) ?>" class="button button-primary">
            </div>
        </form>
    </div>
    <?php if( !$submitted_search ) : ?>
    <div class="card" style="min-width:calc(100% - 4em);max-width:calc(100% - 4em);padding:0.7em 2em 1em 2em;">
        <p style="font-size:120%;font-weight:500;"><?php _e( 'Nejprve musíte zadat hledaný termín&hellip;', 'odwpasp' ) ?></p>
    </div>
    <?php else : ?>
    <?php if( !$query->have_posts() ) : ?>
    <div class="card" style="min-width:calc(100% - 4em);max-width:calc(100% - 4em);padding:0.7em 2em 1em 2em;">
        <p><?php _e( 'Žádné stránky či příspěvky neodpovídají vašemu zadání&hellip;', 'odwpasp' ) ?></p>
    </div>
    <?php else : $i = 0; ?>
    <form action="" method="POST">
        <input type="hidden" name="page" value="<?php echo ODWPASP_ADMIN_PAGE ?>">
        <input type="hidden" name="term" value="<?php echo $term ?>">
        <div style="display:table;width:100%;margin:1em 0 1em 0;">
            <div style="display:table-row;">
                <div style="display:table-cell;text-align:left;vertical-align:middle;width:50%;">
                    <input type="submit" id="odwpasp-priorities_submit" name="submit_priorities" value="<?php _e( 'Uložit nastavení priorit', 'odwpasp' ) ?>" class="button button-primary">
                </div>
                <div style="display:table-cell;text-align:right;vertical-align:middle;width:50%;">
                    <?php printf( __( 'Počet výsledků: %d', 'odwpasp' ), $query->post_count ) ?>
                </div>
            </div>
        </div>
        <div style="clear:both;"></div>
        <table class="widefat fixed striped">
            <thead>
                <th class="" style="max-width:5em;min-width:5em;text-align:center;width:5em;"><?php _e( 'P.', 'odwpasp' ) ?></th>
                <th class="column-primary" style="width:auto;"><?php _e( 'Výsledek vyhledávání', 'odwpasp' ) ?></th>
                <th class="" style="max-width:5em;min-width:5em;width:5em;"><?php _e( 'Priorita', 'odwpasp' ) ?></th>
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
                        <?php printf(
                            __( '<b>%1$s</b> [ID: <code>%2$d</code> | Typ: <em>%3$s</em>]', 'odwpasp' ),
                            get_the_title(), $pid, get_post_type()
                        ) ?>
                    </td>
                    <td>
                        <input type="text" name="p[<?php echo $pid ?>]" class="small-text" value="<?php echo $val ?>" style="width:98%;max-width:5em;">
                    </td>
                </tr>
            <?php endwhile ?>
            <!-- TODO pagination here -->
            <?php wp_reset_postdata() ?>
            </tbody>
        </table>
        <p>
            <input type="submit" id="odwpasp-priorities_submit" name="submit_priorities" value="<?php _e( 'Uložit nastavení priorit', 'odwpasp' ) ?>" class="button button-primary">
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
     */
    function odwpasp_add_meta_to_all_posts() {
        $query = new WP_Query( array(
            'post_type' => ['page', 'post'],
            'posts_per_page' => -1,
            'nopagination' => true,
            'ignore_sticky_posts' => 1
        ) );
        
        while ( $query->have_posts() ) {
            $query->the_post();
            $pid = get_the_ID();
            update_post_meta( $pid, ODWPASP_META_KEY, 0 );

            if( function_exists( 'odwpdl_write_log' ) ) {
                odwpdl_write_log( "Updated [$pid]=>'" . get_post_type() . "'" );
            }

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
            //$q->query_vars['meta_key'] = '';
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
     */
    function odwpasp_pre_get_posts( $q ) {
        if( !is_admin() && $q->is_main_query() /*&& !function_exists( 'relevanssi_do_query' )*/ ) {
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
     * @param array $hits
     * @return array
     * @since 1.0.0
     * @link https://www.relevanssi.com/user-manual/relevanssi_hits_filter/
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
        //echo '<!-- odwpasp_add_query_var="' . print_r( $query_vars, true ) . '" -->'.PHP_EOL;
        $query_vars[] = ODWPASP_META_KEY;
        return $query_vars;
    }
endif;
add_filter( 'query_vars', 'odwpasp_add_query_var' );