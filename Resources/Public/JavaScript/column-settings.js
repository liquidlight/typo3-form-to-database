import Modal from '@typo3/backend/modal.js';
import RegularEvent from '@typo3/core/event/regular-event.js';

/**
 * Opens the "Fields in result list" form (fetched on demand) in a modal.
 *
 * Not implemented via the generic `t3js-modal-trigger` markup convention:
 * that convention's auto-generated buttons have no way to be wired to a
 * `form` id via markup alone, and `data-target-form` looks up the form via
 * the trigger's `ownerDocument` — the backend module iframe — while the
 * modal itself is always appended to the parent document, so the lookup
 * silently finds nothing across that iframe boundary.
 */
new RegularEvent('click', (event, trigger) => {
    event.preventDefault();

    fetch(trigger.dataset.url)
        .then((response) => response.text())
        .then((html) => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const form = wrapper.querySelector('form');
            if (!form) {
                return;
            }
            // The modal is appended to the top-level document, but the form's action
            // is a "bare" backend module URL meant to be loaded inside the module
            // iframe. Without this, submitting navigates the whole backend shell
            // instead of just the module iframe, and TYPO3 redirects that stray
            // top-level navigation through /typo3/main to rebuild the shell — losing
            // the POST body in the process.
            form.target = trigger.ownerDocument.defaultView.name;

            const modal = Modal.advanced({
                title: trigger.dataset.title,
                content: form,
                size: Modal.sizes.medium,
                buttons: [
                    {
                        text: trigger.dataset.buttonCloseText,
                        btnClass: 'btn-default',
                        trigger: (buttonEvent, currentModal) => currentModal.hideModal(),
                    },
                    {
                        text: trigger.dataset.buttonOkText,
                        btnClass: 'btn-primary',
                        form: form.id,
                    },
                ],
            });

            // Submitting now targets the module iframe rather than navigating the
            // whole page, so the modal has to be closed explicitly once the "Save
            // and close" button's native submit fires.
            form.addEventListener('submit', () => modal.hideModal());
        });
}).delegateTo(document, '[data-item-list-select-trigger]');
