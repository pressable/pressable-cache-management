/**
 * Pressable Cache Management — Toolbar JS
 *
 * Handles the combined "Flush Cache for This Page" admin bar button.
 *
 * Bug fixes vs previous version:
 *  1. data-edge on <li> is unsupported by WP add_menu() meta — now uses pcmToolbarData.flushEdge
 *     passed via wp_localize_script() which works on both admin and frontend.
 *  2. Event delegation now targets document.body and checks closest('li').attr('id') correctly,
 *     rather than querying a non-existent '#wp-admin-bar-*-default' wrapper selector.
 *  3. pcm_nonce was only available on frontend (wp_footer). Now comes from pcmToolbarData.nonce
 *     which is always present via wp_localize_script.
 *  4. AJAX handlers now return wp_send_json_success() so jQuery parses valid JSON.
 */

jQuery(document).ready(function($) {

    // Ensure the loader overlay exists once
    if ( !$('#revert-loader-toolbar').length ) {
        $('body').append('<div id="revert-loader-toolbar"></div>');
    }

    // Resolve AJAX URL: prefer localized value, fall back to WP global
    var ajaxUrl = ( typeof pcmToolbarData !== 'undefined' && pcmToolbarData.ajaxurl )
        ? pcmToolbarData.ajaxurl
        : ( typeof ajaxurl !== 'undefined' ? ajaxurl : '' );

    // Nonce: from wp_localize_script (works on both admin + frontend)
    var nonce = ( typeof pcmToolbarData !== 'undefined' ) ? pcmToolbarData.nonce : '';

    // Whether edge cache flush should also fire (passed from PHP via wp_localize_script)
    var flushEdge = ( typeof pcmToolbarData !== 'undefined' && pcmToolbarData.flushEdge === '1' );

    // ── IDs for both branding variants ───────────────────────────────────────
    var combinedIds = [
        'wp-admin-bar-pcm-toolbar-parent-flush-cache-of-this-page',
        'wp-admin-bar-pcm-toolbar-parent-remove-branding-flush-cache-of-this-page',
    ];

    // ── Fire a single AJAX GET, returns a jQuery deferred ────────────────────
    function sendRequest( action ) {
        return $.ajax({
            type    : 'GET',
            url     : ajaxUrl,
            data    : { action: action, path: window.location.pathname, nonce: nonce },
            dataType: 'json',
            cache   : false,
        });
    }

    // ── Sequential flush: Batcache first, then Edge Cache if active ───────────
    function flushCurrentPage() {
        $('#revert-loader-toolbar').show();

        sendRequest('pcm_delete_current_page_cache')
            .always(function() {
                if ( flushEdge ) {
                    sendRequest('pcm_purge_current_page_edge_cache')
                        .always(done);
                } else {
                    done();
                }
            });
    }

    function done() {
        if ( typeof pcmCacheStatics !== 'undefined' ) {
            pcmCacheStatics.update();
        } else {
            $('#revert-loader-toolbar').hide();
        }
    }

    // ── Event delegation on body — catches clicks anywhere inside the <li> ───
    // We delegate from body because the admin bar is injected late and the
    // submenu UL wrapper varies by WP version. Matching by the <li> id directly
    // is the most reliable approach and avoids the broken '-default' suffix.
    $('body').on('click', function(e) {
        // Walk up from the clicked element to find the nearest <li>
        var $li = $(e.target).closest('li');
        var id  = $li.attr('id') || '';

        if ( combinedIds.indexOf(id) !== -1 ) {
            e.preventDefault();
            flushCurrentPage();
        }
    });

});
