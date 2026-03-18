/**
 * Pressable Cache Management - Flush cache for individual page column
 * Branded modal popup replaces browser alert()
 */

if (window.attachEvent) {
    window.attachEvent('onload', flush_object_cache_column_button_action);
} else {
    if (window.onload) {
        var curronload_1 = window.onload;
        var newonload_1 = function(evt) { curronload_1(evt); flush_object_cache_column_button_action(evt); };
        window.onload = newonload_1;
    } else {
        window.onload = flush_object_cache_column_button_action;
    }
}

/* ── Branded modal (injected once) ─────────────────────────────────────── */
function pcmEnsureModal() {
    if (document.getElementById('pcm-col-modal-overlay')) return;

    var overlay = document.createElement('div');
    overlay.id  = 'pcm-col-modal-overlay';
    overlay.style.cssText =
        'display:none;position:fixed;inset:0;background:rgba(4,0,36,.45);'
        + 'z-index:999999;align-items:center;justify-content:center;';

    overlay.innerHTML =
        '<div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:420px;width:90%;'
        + 'box-shadow:0 8px 40px rgba(4,0,36,.18);font-family:sans-serif;position:relative;">'
        + '<div style="width:48px;height:4px;background:#03fcc2;border-radius:4px;margin-bottom:16px;"></div>'
        + '<p id="pcm-col-modal-msg" style="margin:0 0 22px;font-size:14px;color:#040024;line-height:1.6;"></p>'
        + '<button id="pcm-col-modal-ok" style="background:#dd3a03;color:#fff;border:none;border-radius:8px;'
        + 'padding:10px 28px;font-size:13.5px;font-weight:700;cursor:pointer;font-family:sans-serif;'
        + 'transition:background .2s;">OK</button>'
        + '</div>';

    document.body.appendChild(overlay);

    overlay.style.display = 'flex'; overlay.style.display = 'none'; // force style parse

    document.getElementById('pcm-col-modal-ok').addEventListener('click', function() {
        overlay.style.display = 'none';
    });
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });
    // hover on OK button
    var okBtn = document.getElementById('pcm-col-modal-ok');
    okBtn.addEventListener('mouseenter', function() { okBtn.style.background = '#b82f00'; });
    okBtn.addEventListener('mouseleave', function() { okBtn.style.background = '#dd3a03'; });
}

function pcmShowColumnModal(msg) {
    pcmEnsureModal();
    document.getElementById('pcm-col-modal-msg').textContent = msg;
    document.getElementById('pcm-col-modal-overlay').style.display = 'flex';
}

function flush_object_cache_column_button_action() {
    jQuery(document).ready(function($) {
        $("a[id^='flush-object-cache-url']").on('click', function(e) {
            e.preventDefault();
            var post_id = $(e.currentTarget).attr('data-id');
            var nonce   = $(e.currentTarget).attr('data-nonce');

            $('#flush-object-cache-url-' + post_id).css('cursor', 'wait');

            $.ajax({
                type:     'POST',
                url:      ajaxurl,
                data:     { action: 'pcm_flush_object_cache_column', id: post_id, nonce: nonce },
                dataType: 'json',
                cache:    false,
                success: function(data) {
                    $('#flush-object-cache-url-' + post_id).css('cursor', 'pointer');
                    if (typeof data.success !== 'undefined' && data.success === true) {
                        pcmShowColumnModal('Batcache flushed successfully \u2705');
                    } else {
                        pcmShowColumnModal('Something went wrong while trying to flush the cache for this page.');
                    }
                },
                error: function() {
                    $('#flush-object-cache-url-' + post_id).css('cursor', 'pointer');
                    pcmShowColumnModal('Request failed. Please try again.');
                }
            });
            return false;
        });
    });
}
