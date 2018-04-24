/**
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @link https://github.com/ondrejd/odwp-add_search_priorities for the canonical source repository
 * @package odwp-add_search_priorities
 * @since 1.0.0
 */

jQuery( document ).ready( function() {

    // Some simple functions
    var clearSearchForm = function() { jQuery( '#odwpasp-search_term' ).val( '' ); jQuery( '#odwpasp-post_status' ).val( 'published' ); };
    var removeResultsTable = function() { jQuery( '#odwpasp-results_table_form' ).remove(); };
    var addCardMessage = function(msg) { jQuery( '#odwpasp-admin_page' ).append( '<div class="card"><p class="no-search-term-msg">' + msg + '</p></div>' ); };

    // Test search form submit
    jQuery( '#odwpasp-search_submit_btn' ).click( function( e ) {
        console.info("TODO Finish this (submit form and re-render results table)!");
        //e.preventDefault();
        return;

        // Get term
        var term = jQuery( '#odwpasp-search_term' ).val();
        if( ( new String(term) ).trim().length == 0 ) {
            jQuery( '#odwpasp-search_term' ).focus();
            return;
        }

        // Remove current table and add searching message
        removeResultsTable();
        addCardMessage( odwpasp.searching_msg );

        // Collect data and make Ajax call
        // XXX Use NONCE!
        var data = {
			'action': 'odwpasp_test_search',
			'search_term': term
        };

		jQuery.post( ajaxurl, data, function( response ) {
            var data = JSON.parse( response );
            console.log( response, data );
            //...
		});

    } );

    // Test search form cancel
    jQuery( '#odwpasp-search_cancel_btn' ).click( function( e ) {
        e.preventDefault();

        if( jQuery( '#odwpasp-results_table_form' ).size() == 0 ) {
            return;
        }

        clearSearchForm();
        removeResultsTable();
        addCardMessage( odwpasp.no_term_msg );
    } );
})