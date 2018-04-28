/**
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

jQuery( document ).ready( function() {

    // Some simple functions
    var clearSearchForm = function() { jQuery( '#odwpasp-search_term' ).val( '' ); jQuery( '#odwpasp-post_status' ).val( 'published' ); };
    var createResultsTable = function( d ) {
        // Build card with form and table with search results
        var html = ''
            + '<form action="" class="odwpasp-results_card" method="POST">'
            +   '<input type="hidden" name="page" value="' + odwpasp.admin_page + '">'
            +   '<input type="hidden" name="search_term" value="' + d.term + '">'
            +   '<input type="hidden" name="_wpnonce" value="' + d.nonce + '">'
            +   '<input type="hidden" name="post_status" value="' + d.status + '">'
            +   '<input type="hidden" name="rows_count" value="' + d.rows + '">'
            +   '<div class="form-container">'
            +     '<div class="form-container-inner">'
            +       '<div class="form-cell-left">'
            +         '<input type="submit" name="submit_priorities" value="' + odwpasp.saveprior_btn + '" class="button button-primary">'
            +       '</div>'
            +       '<div class="form-cell-right">'
            +         odwpasp.results_count.replace( '%1$d', d.items.length )
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

        for( var i = 0; i < d.items.length; i++ ) {
            var item = d.items[i];
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
                +      '<td class="col-3"><input type="text" name="p[' + item.post_ID + ']" class="small-text odwpasp-priority_input" value="' + item.priority + '" data-post_ID="' + item.post_ID + '" class="input-priority"></td>'
                +    '</tr>';
        }

        html += ''
            +     '</tbody>'
            +   '</table>'
            +   '<p>'
            +     '<input type="submit" name="submit_priorities" value="' + odwpasp.saveprior_btn + '" class="button button-primary">'
            +   '</p>'
            + '</form>';

        jQuery( '#odwpasp-admin_page' ).append( html );
        addSubmitPrioritiesEvent();
    };
    var removeResultsTable = function() { jQuery( '.odwpasp-results_card' ).remove(); };
    var addCardMessage = function(msg) { jQuery( '#odwpasp-admin_page' ).append( '<div class="card odwpasp-card"><p class="no-search-term-msg">' + msg + '</p></div>' ); };
    var removeCardMessages = function(msg) { jQuery( '.odwpasp-card', '#odwpasp-admin_page' ).remove(); }

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
        removeCardMessages();
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
            removeCardMessages();
            var data = JSON.parse( response );
            if( !data || data.error == true ) {
                addCardMessage( odwpasp.ajax_error_msg );
            } else if( data.items.length == 0 ) {
                addCardMessage( data.message );
            } else {
                createResultsTable( data );
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
        removeCardMessages();
        addCardMessage( odwpasp.no_term_msg );
    } );

    // Submit priorities
    var addSubmitPrioritiesEvent = function() {
        jQuery( 'input[name="submit_priorities"]' ).click( function( e ) {
            e.preventDefault();

            var priorities = new Array();
            jQuery( '.odwpasp-priority_input' ).each( function( idx, elm ) {
                priorities.push( {
                    post_ID: jQuery( elm ).attr( 'data-post_ID' ),
                    value: jQuery( elm ).val()
                } );
            } );

            var args = {
                'action': 'odwpasp_submit_priorities',
                '_wpnonce': odwpasp.nonce,
                'search_term': jQuery( 'input[name="search_term"]', '.odwpasp-results_card' ).val(),
                'post_status': jQuery( 'input[name="post_status"]', '.odwpasp-results_card' ).val(),
                'rows_count': jQuery( 'input[name="rows_count"]', '.odwpasp-results_card' ).val(),
                'priorities': priorities,
            };
            
            // Remove current table and add message
            // We need to remove it here because in previous step we need results table HTML
            removeResultsTable();
            removeCardMessages();
            addCardMessage( odwpasp.updating_msg );

            jQuery.post( ajaxurl, args, function( response ) {
                removeCardMessages();
                var data = JSON.parse( response );
                if( !data || data.error == true ) {
                    addCardMessage( odwpasp.ajax_error_msg );
                } else if( data.error == false && data.message != null ) {
                    addCardMessage( data.message );
                } else {
                    createResultsTable( data );
                }
            } );
        } );
    };
})