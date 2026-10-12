/**
 * LMS Kabupaten Pasuruan - Universal Interactive Button Loading Engine
 * Automatically applies smooth spinning animation and processing states
 * to form submit buttons, action links, and interactive controls.
 */
(function (window, document) {
    'use strict';

    /**
     * Activate animated loading state on a button element
     * @param {HTMLElement} btn
     * @param {string|null} customLoadingText
     */
    function setButtonLoading(btn, customLoadingText = null) {
        if (!btn || btn.classList.contains('is-loading')) return;

        // 1. Lock exact dimensions so the layout never jumps or shifts
        const rect = btn.getBoundingClientRect();
        if (rect.width > 0) {
            btn.style.minWidth = Math.ceil(rect.width) + 'px';
        }

        // 2. Backup original innerHTML if not already backed up
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }

        btn.classList.add('is-loading', 'pointer-events-none');
        btn.setAttribute('aria-busy', 'true');

        // 3. Find existing icon if present
        const existingIcon = btn.querySelector('i.fa, i.fa-solid, i.fa-regular, i.fa-brands, svg');

        // 4. Create animated spinner
        const spinner = document.createElement('i');
        spinner.className = 'fa-solid fa-circle-notch fa-spin btn-active-spinner inline-block text-current';

        if (existingIcon) {
            // Inherit icon color/margin/size classes if any
            const iconClasses = Array.from(existingIcon.classList)
                .filter(c => c.startsWith('text-') || c === 'mr-1' || c === 'mr-1.5' || c === 'mr-2' || c === 'text-xs' || c === 'text-sm' || c === 'text-base');
            if (iconClasses.length > 0) {
                spinner.classList.add(...iconClasses);
            } else {
                spinner.classList.add('mr-1.5');
            }
            existingIcon.style.display = 'none';
            existingIcon.parentNode.insertBefore(spinner, existingIcon);
        } else {
            spinner.classList.add('mr-2');
            if (btn.firstChild) {
                btn.insertBefore(spinner, btn.firstChild);
            } else {
                btn.appendChild(spinner);
            }
        }

        // 5. Update text if data-loading-text or customLoadingText is provided
        const loadingText = customLoadingText || btn.getAttribute('data-loading-text');
        if (loadingText) {
            const textSpan = btn.querySelector('span:not(.btn-active-spinner)');
            if (textSpan) {
                textSpan.textContent = loadingText;
            } else {
                const textNodes = Array.from(btn.childNodes).filter(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim().length > 0);
                if (textNodes.length > 0) {
                    textNodes[0].textContent = ' ' + loadingText;
                }
            }
        }
    }

    /**
     * Restore button from loading state
     * @param {HTMLElement} btn
     */
    function resetButtonLoading(btn) {
        if (!btn) return;
        if (btn.dataset.originalHtml) {
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
        btn.classList.remove('is-loading', 'pointer-events-none');
        btn.removeAttribute('aria-busy');
        btn.removeAttribute('disabled');
        btn.style.minWidth = '';
    }

    // Keep track of the last clicked submit button
    let lastClickedSubmitButton = null;

    document.addEventListener('click', function (e) {
        // Track the button clicked for form submissions
        const submitBtn = e.target.closest('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            lastClickedSubmitButton = submitBtn;
        }

        // Action buttons or links decorated with data-loading or .btn-loading
        const actionBtn = e.target.closest('[data-loading], .btn-loading');
        if (actionBtn && (actionBtn.tagName === 'A' || actionBtn.getAttribute('type') === 'button')) {
            setButtonLoading(actionBtn);
        }
    }, true);

    // Global listener for form submit events
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        // If validation prevented submission (e.g. Alpine or custom handler called preventDefault)
        if (e.defaultPrevented) return;

        // Check HTML5 validity
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        // Find the active submit button
        let btn = lastClickedSubmitButton;
        if (!btn || !form.contains(btn)) {
            btn = form.querySelector('button[type="submit"], input[type="submit"], button:not([type="button"]):not([type="reset"])');
        }

        if (btn) {
            // Apply loading state immediately on submit
            setButtonLoading(btn);

            // Also attach disabled attribute in next tick to avoid blocking form parameter submission
            setTimeout(function () {
                if (btn && btn.classList.contains('is-loading')) {
                    btn.setAttribute('disabled', 'disabled');
                }
            }, 20);
        }
    });

    // Reset buttons when navigating back via Back-Forward Cache (bfcache)
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('.is-loading').forEach(resetButtonLoading);
    });

    // Reset buttons if form reset event is fired
    document.addEventListener('reset', function (e) {
        const form = e.target;
        if (form) {
            form.querySelectorAll('.is-loading').forEach(resetButtonLoading);
        }
    });

    // Expose helpers globally for custom scripting / Alpine
    window.setButtonLoading = setButtonLoading;
    window.resetButtonLoading = resetButtonLoading;

})(window, document);
