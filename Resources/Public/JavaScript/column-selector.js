/**
 * Module: @liquidlight/form-to-database/column-selector.js
 *
 * Opens the column selection of the form results module in a TYPO3 modal,
 * replicating the look and behavior of the core record list column selector
 * (@typo3/backend/column-selector-button.js). Replaces the former Bootstrap
 * modal markup, which is no longer supported since TYPO3 v14
 * (Breaking #107443).
 */
import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import RegularEvent from '@typo3/core/event/regular-event.js';

const Selectors = {
  columnsSelector: '.t3js-column-selector',
  columnsContainerSelector: '.t3js-column-selector-container',
  columnsFilterSelector: 'input[name="columns-filter"]',
  columnsSelectorActionsSelector: '.t3js-column-selector-actions',
};

class ColumnSelector {
  constructor() {
    new RegularEvent('click', (event, target) => {
      event.preventDefault();
      const template = document.getElementById('itemListSelect');
      if (template === null) {
        return;
      }
      const content = template.content.firstElementChild.cloneNode(true);
      const modal = Modal.advanced({
        title: target.dataset.modalTitle || 'Show columns',
        content: content,
        severity: Severity.notice,
        size: Modal.sizes.medium,
        buttons: [
          {
            text: target.dataset.buttonCloseText || 'Close',
            active: true,
            btnClass: 'btn-default',
            name: 'cancel',
            trigger: (e, currentModal) => currentModal.hideModal(),
          },
          {
            text: target.dataset.buttonOkText || 'OK',
            btnClass: 'btn-primary',
            name: 'update',
            trigger: (e, currentModal) => currentModal.querySelector('form')?.requestSubmit(),
          },
        ],
        callback: (currentModal) => this.initializeContent(currentModal),
      });
    }).delegateTo(document, '.t3js-form-to-database-column-selector');
  }

  static isColumnHidden(column) {
    return column.closest(Selectors.columnsContainerSelector)?.classList.contains('hidden');
  }

  static filterColumns(filterField, columns) {
    columns.forEach((column) => {
      const container = column.closest(Selectors.columnsContainerSelector);
      if (!column.disabled && container !== null) {
        const label = container.querySelector('.form-check-label')?.textContent;
        if (label && label.length) {
          container.classList.toggle(
            'hidden',
            filterField.value !== '' && !RegExp(filterField.value, 'i').test(
              label.trim().replace(/\[\]/g, '').replace(/\s+/g, ' ')
            )
          );
        }
      }
    });
  }

  static toggleSelectorActions(columns, selectAll, selectNone, initialize = false) {
    selectAll.classList.add('disabled');
    for (let i = 0; i < columns.length; i++) {
      if (!columns[i].disabled && !columns[i].checked && (initialize || !ColumnSelector.isColumnHidden(columns[i]))) {
        selectAll.classList.remove('disabled');
        break;
      }
    }
    selectNone.classList.add('disabled');
    for (let i = 0; i < columns.length; i++) {
      if (!columns[i].disabled && columns[i].checked && (initialize || !ColumnSelector.isColumnHidden(columns[i]))) {
        selectNone.classList.remove('disabled');
        break;
      }
    }
  }

  initializeContent(modal) {
    const form = modal.querySelector('form');
    if (form === null) {
      return;
    }
    const columns = modal.querySelectorAll(Selectors.columnsSelector);
    const columnsFilter = modal.querySelector(Selectors.columnsFilterSelector);
    const columnsSelectorActions = modal.querySelector(Selectors.columnsSelectorActionsSelector);
    if (!columns.length || columnsFilter === null || columnsSelectorActions === null) {
      return;
    }
    const selectAll = columnsSelectorActions.querySelector('button[data-action="select-all"]');
    const selectNone = columnsSelectorActions.querySelector('button[data-action="select-none"]');
    if (selectAll === null || selectNone === null) {
      return;
    }

    ColumnSelector.toggleSelectorActions(columns, selectAll, selectNone, true);
    columns.forEach((column) => {
      column.addEventListener('change', () => {
        ColumnSelector.toggleSelectorActions(columns, selectAll, selectNone);
      });
    });

    columnsFilter.addEventListener('keydown', (e) => {
      // Prevent implicit form submission from the search field,
      // clear it on Escape without closing the modal
      if (e.code === 'Enter') {
        e.preventDefault();
      }
      if (e.code === 'Escape') {
        e.stopImmediatePropagation();
        e.target.value = '';
      }
    });
    columnsFilter.addEventListener('keyup', (e) => {
      ColumnSelector.filterColumns(e.target, columns);
      ColumnSelector.toggleSelectorActions(columns, selectAll, selectNone);
    });
    columnsFilter.addEventListener('search', (e) => {
      ColumnSelector.filterColumns(e.target, columns);
      ColumnSelector.toggleSelectorActions(columns, selectAll, selectNone);
    });

    columnsSelectorActions.querySelectorAll('button[data-action]').forEach((button) => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        switch (button.dataset.action) {
          case 'select-toggle':
            columns.forEach((column) => {
              if (!column.disabled && !ColumnSelector.isColumnHidden(column)) {
                column.checked = !column.checked;
              }
            });
            break;
          case 'select-all':
            columns.forEach((column) => {
              if (!column.disabled && !ColumnSelector.isColumnHidden(column)) {
                column.checked = true;
              }
            });
            break;
          case 'select-none':
            columns.forEach((column) => {
              if (!column.disabled && !ColumnSelector.isColumnHidden(column)) {
                column.checked = false;
              }
            });
            break;
        }
        ColumnSelector.toggleSelectorActions(columns, selectAll, selectNone);
      });
    });
  }
}

export default new ColumnSelector();
