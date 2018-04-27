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
    var removeResultsTable = function() { jQuery( '.odwpasp-results_card' ).remove(); };
    var addCardMessage = function(msg) { jQuery( '#odwpasp-admin_page' ).append( '<div class="card odwpasp-card"><p class="no-search-term-msg">' + msg + '</p></div>' ); };
    var removeCardMessags = function(msg) { jQuery( '.odwpasp-card', '#odwpasp-admin_page' ).remove(); }

    // Test search form submit
    jQuery( '#odwpasp-search_submit_btn' ).click( function( e ) {
        e.preventDefault();

        // Get term
        var term = jQuery( '#odwpasp-search_term' ).val();
        if( ( new String(term) ).trim().length == 0 ) {
            jQuery( '#odwpasp-search_term' ).focus();
            return;
        }

        var status = jQuery( '#odwpasp-post_status' ).val();
        var rows = jQuery( '#odwpasp-rows_count' ).val();

        // Remove current table and add searching message
        removeResultsTable();
        removeCardMessags();
        addCardMessage( odwpasp.searching_msg );

        // Collect data and make Ajax call
        var args = {
            'action': 'odwpasp_test_search',
            '_wpnonce': odwpasp.nonce,
            'search_term': term,
            'post_status': status,
            'rows_count': rows,
        };

		jQuery.post( ajaxurl, args, function( response ) {
            removeCardMessags();

            var data = JSON.parse( response );
            if( !data || data.error == true ) {
                // Error occured...
                addCardMessage( odwpasp.ajax_error_msg );
            }
            else if( data.items.length == 0 ) {
                // No items...
                addCardMessage( data.message );
            }
            else {
                // Build card with form and table with search results
                var html = ''
                    + '<form action="" class="odwpasp-results_card" method="POST">'
                    +   '<input type="hidden" name="page" value="' + odwpasp.admin_page + '">'
                    +   '<input type="hidden" name="search_term" value="' + data.term + '">'
                    +   '<input type="hidden" name="_wpnonce" value="' + data.nonce + '">'
                    +   '<input type="hidden" name="post_status" value="' + data.status + '">'
                    +   '<input type="hidden" name="rows_count" value="' + data.rows + '">'
                    +   '<div class="form-container">'
                    +     '<div class="form-container-inner">'
                    +       '<div class="form-cell-left">'
                    +         '<input type="submit" id="odwpasp-priorities_submit" name="submit_priorities" value="' + odwpasp.saveprior_btn + '" class="button button-primary">'
                    +       '</div>'
                    +       '<div class="form-cell-right">'
                    +         odwpasp.results_count.replace( '%1$d', data.items.length )
                    +       '</div>'
                    +     '</div>'
                    +   '</div>'
                    +   '<table class="widefat fixed striped odwpasp-table">'
                    +     '<thead>'
                    +       '<tr>'
                    +         '<th class="col-1">' + odwpasp.table_col_index + '</th>'
                    +         '<th class="col-2 column-primary">' + odwpasp.table_col_prim + '</th>'
                    +         '<th class="col-3">' + odwpasp.table_col_prior + '</th>'
                    +       '</tr>'
                    +     '</thead>'
                    +     '<tbody>';

                for( var i = 0; i < data.items.length; i++ ) {
                    var item = data.items[i];
                    var primary = ''
                                + '<b>' + item.title + '</b> '
                                + '[' + odwpasp.id_text + '<code>' + item.post_ID + '</code> '
                                + '| ' + odwpasp.type_text + '<code>' + item.type + '</code> '
                                + '| ' + odwpasp.status_text + ': <em>' + item.status + '</em> '
                                + '| <a href="' + item.permalink + '" target="blank">' + odwpasp.show_text + ' <span class="dashicons dashicons-external"></span></a>'
                                + ']';
                    html += ''
                        +    '<tr>'
                        +      '<th class="col-1" scope="row">' + item.idx + '</th>'
                        +      '<td class="col-2">' + primary + '</td>'
                        +      '<td class="col-3"><input type="text" name="p[' + item.post_ID + ']" class="small-text" value="' + item.priority + '" class="input-priority"></td>'
                        +    '</tr>';
                }

                html += ''
                    +     '</tbody>'
                    +   '</table>'
                    +   '<p>'
                    +     '<input type="submit" id="odwpasp-priorities_submit" name="submit_priorities" value="' + odwpasp.saveprior_btn + '" class="button button-primary">'
                    +   '</p>'
                    + '</form>';

                jQuery( '#odwpasp-admin_page' ).append( html );
            }
		});

    } );

    // Test search form cancel
    jQuery( '#odwpasp-search_cancel_btn' ).click( function( e ) {
        e.preventDefault();

        if( jQuery( '.odwpasp-results_card' ).size() == 0 ) {
            return;
        }

        clearSearchForm();
        removeResultsTable();
        removeCardMessags();
        addCardMessage( odwpasp.no_term_msg );
    } );
})