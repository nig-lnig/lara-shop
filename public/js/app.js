/*
 * Lara-Shop — a little vanilla JavaScript.
 * Every feature also works WITHOUT JavaScript (normal links and forms);
 * the script only makes things faster and clearer.
 *
 *  1. Menus (popovers: account, Rayons; admin menu)
 *  2. Panels: mobile menu drawer and filter sheet
 *  3. Quantity steppers and auto-submit
 *  4. Add to cart without reloading
 *  5. Favorites without reloading
 *  6. Confirmations and "submit once"
 */
document.documentElement.classList.add('js');

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

/** Sends a form with fetch() and returns { ok, data }. Laravel answers in JSON. */
async function sendForm(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form), // contains the CSRF token (@csrf)
    });
    let data = {};
    try { data = await response.json(); } catch (e) { /* not JSON */ }
    return { ok: response.ok, status: response.status, data };
}

/** Reads a message aloud for screen readers (an invisible "live region"). */
function announce(message) {
    let region = document.getElementById('live-region');
    if (!region) {
        region = document.createElement('div');
        region.id = 'live-region';
        region.className = 'sr-only';
        region.setAttribute('role', 'status');
        document.body.append(region);
    }
    region.textContent = '';
    setTimeout(() => { region.textContent = message; }, 50);
}

document.addEventListener('DOMContentLoaded', () => {
    /* ---------------------------------------------------------------------
     * 1. Menus
     * ------------------------------------------------------------------- */

    // Admin sidebar on small screens: the button shows / hides the links.
    document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
        const nav = document.getElementById(button.getAttribute('aria-controls'));
        button.addEventListener('click', () => {
            const open = nav.classList.toggle('open');
            button.setAttribute('aria-expanded', String(open));
        });
    });

    // Popovers (account, Rayons): open under their button, close with Escape,
    // a click outside, or when the keyboard focus leaves them.
    document.querySelectorAll('[data-menu]').forEach((wrap) => {
        const trigger = wrap.querySelector('[data-menu-trigger]');
        const panel = wrap.querySelector('[data-menu-panel]');

        const setOpen = (open, returnFocus = false) => {
            panel.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', String(open));
            if (open) panel.querySelector('a, button')?.focus();
            if (!open && returnFocus) trigger.focus();
        };

        trigger.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
        wrap.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) setOpen(false, true);
        });
        wrap.addEventListener('focusout', (event) => {
            if (!wrap.contains(event.relatedTarget)) setOpen(false);
        });
        document.addEventListener('click', (event) => {
            if (!wrap.contains(event.target)) setOpen(false);
        });
    });

    /* ---------------------------------------------------------------------
     * 2. Panels: the mobile menu (drawer, from the left) and the filter
     *    sheet (from the bottom). Same behaviour for both:
     *    - buttons with data-panel-open="id" open the panel <… id="id" data-panel>
     *    - the rest of the page becomes "inert" (no focus, no clicks)
     *    - Escape, the close button or the dark backdrop closes it
     *    - the keyboard focus goes back to the button that opened it
     * ------------------------------------------------------------------- */
    const backdrop = document.querySelector('[data-panel-backdrop]');
    let openPanel = null;
    let opener = null;

    // Makes everything outside the panel inert (or active again).
    const setInertOutside = (panel, inert) => {
        let node = panel;
        while (node && node !== document.body) {
            [...node.parentElement.children].forEach((sibling) => {
                if (sibling !== node && sibling !== backdrop && sibling.tagName !== 'SCRIPT') sibling.inert = inert;
            });
            node = node.parentElement;
        }
    };

    const closePanel = () => {
        if (!openPanel) return;
        openPanel.classList.remove('is-open');
        openPanel.removeAttribute('role');
        openPanel.removeAttribute('aria-modal');
        backdrop.classList.remove('is-open');
        document.body.classList.remove('panel-open');
        setInertOutside(openPanel, false);
        document.querySelectorAll(`[data-panel-open="${openPanel.id}"]`).forEach((b) => b.setAttribute('aria-expanded', 'false'));
        openPanel = null;
        opener?.focus();
    };

    const showPanel = (panel, button) => {
        if (openPanel) closePanel();
        openPanel = panel;
        opener = button;
        panel.classList.add('is-open');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        backdrop.classList.add('is-open');
        document.body.classList.add('panel-open');
        setInertOutside(panel, true);
        document.querySelectorAll(`[data-panel-open="${panel.id}"]`).forEach((b) => b.setAttribute('aria-expanded', 'true'));
        panel.querySelector('[data-panel-close]')?.focus();
    };

    document.querySelectorAll('[data-panel-open]').forEach((button) => {
        const panel = document.getElementById(button.dataset.panelOpen);
        if (panel) button.addEventListener('click', () => showPanel(panel, button));
    });
    document.querySelectorAll('[data-panel] [data-panel-close]').forEach((button) => button.addEventListener('click', closePanel));
    backdrop?.addEventListener('click', closePanel);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && openPanel) closePanel();
    });
    // Large screen again: the filter sheet becomes a sidebar, the drawer is not needed.
    window.matchMedia('(min-width: 1025px)').addEventListener('change', (event) => {
        if (event.matches) closePanel();
    });
    // The filter button of other pages leads to /catalogue#filtres: open the sheet directly.
    const filterPanel = document.getElementById('filter-panel');
    if (filterPanel && location.hash === '#filtres' && window.matchMedia('(max-width: 1024px)').matches) {
        showPanel(filterPanel, document.querySelector('[data-panel-open="filter-panel"]'));
    }

    /* ---------------------------------------------------------------------
     * 3. Quantity steppers [-] [2] [+] and auto-submit
     * ------------------------------------------------------------------- */
    document.querySelectorAll('[data-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('input');
        stepper.querySelectorAll('[data-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const min = Number(input.min || 1);
                const max = Number(input.max || 99);
                const next = Math.min(max, Math.max(min, Number(input.value || min) + Number(button.dataset.step)));
                if (next === Number(input.value)) return;
                input.value = next;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    // Fields with data-auto-submit send their form when they change.
    // A short delay lets the customer press [+] several times first.
    document.querySelectorAll('[data-auto-submit]').forEach((field) => {
        let timer;
        field.addEventListener('change', () => {
            clearTimeout(timer);
            timer = setTimeout(() => field.form.requestSubmit(), field.tagName === 'SELECT' ? 0 : 450);
        });
    });

    /* ---------------------------------------------------------------------
     * 4. Add to cart without reloading
     * ------------------------------------------------------------------- */
    const checkIcon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5 9.5 17 19 7.5"/></svg>';

    const updateCartCount = (count) => {
        document.querySelectorAll('[data-cart-count]').forEach((badge) => {
            badge.textContent = count;
            badge.dataset.empty = count === 0 ? 'true' : 'false';
            if (!reduceMotion.matches) {
                // A small "bump" so the eye notices the new number.
                badge.animate(
                    [{ transform: 'scale(1)' }, { transform: 'scale(1.3)' }, { transform: 'scale(1)' }],
                    { duration: 260, easing: 'cubic-bezier(0.23, 1, 0.32, 1)' },
                );
            }
        });
        document.querySelectorAll('[data-cart-link]').forEach((link) => {
            link.setAttribute('aria-label', `Panier, ${count} article(s)`);
        });
    };

    document.querySelectorAll('form[data-add-to-cart]').forEach((form) => {
        const compact = form.hasAttribute('data-compact'); // round button on product cards
        const button = form.querySelector('button[type=submit]');
        const label = form.querySelector('[data-label]');
        const feedback = form.querySelector('[data-feedback]');
        const originalLabel = label.innerHTML;
        let resetTimer;

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearTimeout(resetTimer);
            button.classList.add('is-busy');
            button.disabled = true;

            try {
                const { ok, data } = await sendForm(form);
                feedback.classList.toggle('is-success', ok);
                feedback.classList.toggle('is-error', !ok);

                if (ok) {
                    updateCartCount(data.cartCount);
                    button.classList.add('is-done');
                    label.innerHTML = compact ? checkIcon : `${checkIcon} Ajouté`;
                    feedback.innerHTML = compact
                        ? 'Ajouté au panier'
                        : `Ajouté au panier. <a href="${form.dataset.cartUrl}">Voir le panier</a>`;
                } else {
                    feedback.textContent = data.message || 'Impossible d’ajouter cet article.';
                }
                if (compact) feedback.classList.add('is-visible'); // small bubble above the button
            } catch (error) {
                form.submit(); // network problem: fall back to a normal form post
                return;
            }

            button.classList.remove('is-busy');
            button.disabled = false;
            resetTimer = setTimeout(() => {
                label.innerHTML = originalLabel;
                button.classList.remove('is-done');
                if (compact) feedback.classList.remove('is-visible');
            }, compact ? 2200 : 1800);
        });
    });

    /* ---------------------------------------------------------------------
     * 5. Favorites without reloading
     * ------------------------------------------------------------------- */
    document.querySelectorAll('form[data-favorite]').forEach((form) => {
        const button = form.querySelector('button');
        const text = button.querySelector('[data-text]');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            // Answer the click immediately, then confirm with the server.
            const wanted = button.getAttribute('aria-pressed') !== 'true';
            const apply = (on) => {
                const label = on ? button.dataset.labelOn : button.dataset.labelOff;
                button.setAttribute('aria-pressed', String(on));
                if (text) text.textContent = label; else { button.setAttribute('aria-label', label); button.title = label; }
            };
            apply(wanted);
            if (wanted && !reduceMotion.matches) {
                // The heart "pops" once when it is filled.
                button.querySelector('.icon').animate(
                    [{ transform: 'scale(1)' }, { transform: 'scale(1.25)' }, { transform: 'scale(1)' }],
                    { duration: 240, easing: 'cubic-bezier(0.23, 1, 0.32, 1)' },
                );
            }
            try {
                const { ok, data } = await sendForm(form);
                if (!ok) throw new Error();
                apply(data.favorite);
                announce(data.message);
            } catch (error) {
                apply(!wanted); // undo
                announce('Le favori n’a pas pu être enregistré.');
            }
        });
    });

    /* ---------------------------------------------------------------------
     * 5b. Admin: preview a product picture BEFORE saving it
     *     URL.createObjectURL() shows the chosen file without uploading it.
     *     Type and size are checked here too (the server checks again).
     * ------------------------------------------------------------------- */
    document.querySelectorAll('[data-image-upload]').forEach((box) => {
        const input = box.querySelector('input[type=file]');
        const dropzone = box.querySelector('.dropzone');
        const preview = box.querySelector('[data-preview]');
        const newImage = box.querySelector('[data-preview-img]');
        const badge = box.querySelector('[data-preview-badge]');
        const info = box.querySelector('[data-file-info]');
        const error = box.querySelector('[data-file-error]');
        const clear = box.querySelector('[data-clear-file]');
        const remove = box.querySelector('[data-remove-image]');
        const maxBytes = Number(box.dataset.maxBytes);
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        let objectUrl = null;

        const reset = () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            newImage.hidden = true;
            badge.hidden = true;
            clear.hidden = true;
            info.textContent = '';
        };
        const sizeText = (bytes) => (bytes < 1024 * 1024
            ? `${Math.round(bytes / 1024)} Ko`
            : `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} Mo`);

        input.addEventListener('change', () => {
            reset();
            error.hidden = true;
            const file = input.files[0];
            if (!file) return;

            let problem = '';
            if (!allowed.includes(file.type)) problem = 'Format non accepté : choisissez une image JPG, PNG ou WebP.';
            else if (file.size > maxBytes) problem = `Image trop lourde (${sizeText(file.size)}) : ${sizeText(maxBytes)} maximum.`;
            if (problem) {
                error.textContent = problem;
                error.hidden = false;
                input.value = ''; // nothing will be sent
                return;
            }

            objectUrl = URL.createObjectURL(file);
            newImage.src = objectUrl;
            newImage.hidden = false;
            badge.hidden = false;
            clear.hidden = false;
            info.textContent = `${file.name} · ${sizeText(file.size)}`;
            if (remove) remove.checked = false;
            preview.classList.remove('is-removed');
        });

        clear.addEventListener('click', () => { input.value = ''; reset(); input.focus(); });
        remove?.addEventListener('change', () => preview.classList.toggle('is-removed', remove.checked && newImage.hidden));

        // Visual hint while a file is dragged over the box
        ['dragenter', 'dragover'].forEach((type) => input.addEventListener(type, () => dropzone.classList.add('is-dragover')));
        ['dragleave', 'drop'].forEach((type) => input.addEventListener(type, () => dropzone.classList.remove('is-dragover')));
    });

    /* ---------------------------------------------------------------------
     * 5c. Password fields: show / hide, and live list of the rules
     *     (the server checks the same rules: Password::defaults()).
     * ------------------------------------------------------------------- */
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
    });

    document.querySelectorAll('[data-password-rules]').forEach((list) => {
        const input = document.getElementById(list.dataset.passwordRules);
        const items = list.querySelectorAll('[data-rule]');
        const tests = {
            length: (v, li) => v.length >= Number(li.dataset.min),
            case: (v) => /\p{Ll}/u.test(v) && /\p{Lu}/u.test(v),
            digit: (v) => /\d/.test(v),
            symbol: (v) => /[^\p{L}\p{N}\s]/u.test(v),
        };
        const update = () => items.forEach((li) => li.classList.toggle('is-met', tests[li.dataset.rule](input.value, li)));
        input.addEventListener('input', update);
        update();
    });

    /* ---------------------------------------------------------------------
     * 6. Confirmations and "submit once"
     * ------------------------------------------------------------------- */

    // <form data-confirm="Sûr ?">: ask before dangerous actions.
    // With data-confirm-if="cancelled", only ask when that option is selected.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const onlyIf = form.dataset.confirmIf;
            if (onlyIf && form.querySelector('select')?.value !== onlyIf) return;
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    // Checkout: after the first click the button shows it is working and
    // cannot be clicked again. (The server is protected too: checkout_token.)
    document.querySelectorAll('form[data-once]').forEach((form) => {
        form.addEventListener('submit', () => {
            const buttons = [...form.querySelectorAll('[type=submit]'), ...document.querySelectorAll(`[type=submit][form="${form.id}"]`)];
            buttons.forEach((button) => {
                button.classList.add('is-busy');
                setTimeout(() => {
                    button.disabled = true;
                    const label = button.querySelector('.btn-label') || button;
                    if (button.dataset.loadingText) label.textContent = button.dataset.loadingText;
                }, 0);
            });
        });
    });
});
