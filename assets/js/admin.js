/**
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-add_search_priorities
 * @since 1.0.0
 */

jQuery( document ).ready( function() {

    jQuery( "#odwpasp-search_submit_btn" ).click( function( e ) {
        console.log( "Submit button pressed..." );
        //e.preventDefault();
    } );

    jQuery( "#odwpasp-search_cancel_btn" ).click( function( e ) {
        document.location = odwpasp.admin_page_url;
    } );
})