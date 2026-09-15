/**
 * Adds a "⤢ Ampliar" toggle to every <textarea data-expandable>. Clicking it
 * blows the textarea up to a near-fullscreen overlay so long notes can be
 * written comfortably; Esc or the button restores it in place. Works for
 * textareas that live inside a <dialog> too (the overlay sits above it).
 */
(function () {
    if (window.__bfTextareaExpandBound) return;
    window.__bfTextareaExpandBound = true;

    var active = null; // { textarea, placeholder, backdrop }

    function collapse() {
        if (!active) return;
        var ta = active.textarea;
        ta.classList.remove('textarea-fullscreen');
        if (active.placeholder && active.placeholder.parentNode) {
            active.placeholder.parentNode.insertBefore(ta, active.placeholder);
            active.placeholder.parentNode.removeChild(active.placeholder);
        }
        active.backdrop.remove();
        var btn = ta.parentNode ? ta.parentNode.querySelector('[data-textarea-expand]') : null;
        if (btn) btn.textContent = '⤢ Ampliar';
        active = null;
    }

    function expand(ta, btn) {
        // When the textarea lives inside an open <dialog> (a modal renders in
        // the browser's top layer), the overlay has to be appended into that
        // same dialog or it would paint behind it.
        var host = ta.closest('dialog[open]') || document.body;

        var backdrop = document.createElement('div');
        backdrop.className = 'textarea-expand-backdrop';
        host.appendChild(backdrop);

        // Leave a marker so we can put the textarea back where it was.
        var placeholder = document.createComment('textarea-expand');
        ta.parentNode.insertBefore(placeholder, ta);
        host.appendChild(ta);
        ta.classList.add('textarea-fullscreen');
        ta.focus();

        active = { textarea: ta, placeholder: placeholder, backdrop: backdrop };
        backdrop.addEventListener('click', collapse);
        btn.textContent = '× Cerrar';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-textarea-expand]');
        if (!btn) return;
        e.preventDefault();
        var ta = document.getElementById(btn.getAttribute('data-textarea-expand'));
        if (!ta) return;
        if (active && active.textarea === ta) collapse();
        else { if (active) collapse(); expand(ta, btn); }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && active) { e.stopPropagation(); collapse(); }
    });

    var seq = 0;
    function decorate(root) {
        (root || document).querySelectorAll('textarea[data-expandable]').forEach(function (ta) {
            if (ta.dataset.expandReady) return;
            ta.dataset.expandReady = '1';
            if (!ta.id) ta.id = 'bf-ta-' + (++seq);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'textarea-expand-btn';
            btn.setAttribute('data-textarea-expand', ta.id);
            btn.textContent = '⤢ Ampliar';
            ta.parentNode.insertBefore(btn, ta.nextSibling);
        });
    }

    // Exposed so content injected after load (e.g. on-demand edit forms) gets the toggle too.
    window.bfDecorateTextareas = decorate;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { decorate(document); });
    } else {
        decorate(document);
    }
})();
