/**
 * Module: @liquidlight/form-to-database/column-selector.js
 *
 * Opens the column selection of the form results module in a TYPO3 modal.
 * Replaces the former Bootstrap modal markup (data-bs-toggle/data-bs-target),
 * which is no longer supported since TYPO3 v14 (Breaking #107443).
 */
import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import RegularEvent from '@typo3/core/event/regular-event.js';

class ColumnSelector {
  constructor() {
    new RegularEvent('click', (event, target) => {
      event.preventDefault();
      const template = document.getElementById('itemListSelect');
      if (template === null) {
        return;
      }
      const content = template.content.firstElementChild.cloneNode(true);
      Modal.advanced({
        title: target.dataset.modalTitle || 'Columns',
        content: content,
        severity: Severity.notice,
        size: Modal.sizes.medium,
        buttons: [
          {
            text: target.dataset.buttonCloseText || 'Close',
            active: true,
            btnClass: 'btn-default',
            trigger: (e, modal) => modal.hideModal(),
          },
          {
            text: target.dataset.buttonOkText || 'Save',
            btnClass: 'btn-primary',
            icon: 'actions-document-save-close',
            trigger: (e, modal) => modal.querySelector('form')?.requestSubmit(),
          },
        ],
      });
    }).delegateTo(document, '.t3js-form-to-database-column-selector');
  }
}

export default new ColumnSelector();
