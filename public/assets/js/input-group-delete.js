/**
 * Input Group Delete Button Handler
 * 
 * Manages delete/remove button interactions for input groups
 */

(function (window, document) {
    'use strict';

    class InputGroupDelete {
        constructor(options = {}) {
            this.options = {
                buttonSelector: '[data-remove-input]',
                inputGroupSelector: '.input-group',
                animationDuration: 300,
                callback: null,
                ...options,
            };

            this.init();
        }

        init() {
            this.setupEventListeners();
        }

        setupEventListeners() {
            document.addEventListener('click', (e) => {
                const button = e.target.closest(this.options.buttonSelector);
                if (!button) return;

                e.preventDefault();
                this.handleRemove(button);
            });
        }

        handleRemove(button) {
            const inputGroup = button.closest(this.options.inputGroupSelector);
            if (!inputGroup) return;

            const input = inputGroup.querySelector('input, select, textarea');
            if (!input) return;

            // Fire custom event before removal
            const beforeEvent = new CustomEvent('input-group:before-remove', {
                detail: { input, button, inputGroup },
                cancelable: true,
            });

            if (!inputGroup.dispatchEvent(beforeEvent)) {
                return; // Event was cancelled
            }

            // Option 1: Clear the input value (default)
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
            }

            // Trigger input event
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));

            // Option 2: Remove the entire input group (if data attribute set)
            if (inputGroup.dataset.removeGroup === 'true') {
                this.removeInputGroup(inputGroup);
            }

            // Fire custom event after removal
            const afterEvent = new CustomEvent('input-group:after-remove', {
                detail: { input, button, inputGroup },
            });
            inputGroup.dispatchEvent(afterEvent);

            // Call callback if provided
            if (this.options.callback && typeof this.options.callback === 'function') {
                this.options.callback(input, button, inputGroup);
            }
        }

        removeInputGroup(inputGroup) {
            inputGroup.classList.add('removing');

            setTimeout(() => {
                inputGroup.remove();
            }, this.options.animationDuration);
        }

        /**
         * Clear all inputs in a container
         */
        clearAll(container) {
            const inputs = container.querySelectorAll('input, select, textarea');
            inputs.forEach((input) => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        /**
         * Enable/disable all delete buttons
         */
        setEnabled(enabled = true) {
            const buttons = document.querySelectorAll(this.options.buttonSelector);
            buttons.forEach((btn) => {
                btn.disabled = !enabled;
                btn.style.opacity = enabled ? '1' : '0.65';
                btn.style.pointerEvents = enabled ? 'auto' : 'none';
            });
        }

        /**
         * Get all input groups
         */
        getInputGroups() {
            return document.querySelectorAll(this.options.inputGroupSelector);
        }

        /**
         * Get all inputs in groups
         */
        getInputs() {
            const groups = this.getInputGroups();
            const inputs = [];
            groups.forEach((group) => {
                const input = group.querySelector('input, select, textarea');
                if (input) inputs.push(input);
            });
            return inputs;
        }
    }

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.InputGroupDelete = new InputGroupDelete();
        });
    } else {
        window.InputGroupDelete = new InputGroupDelete();
    }

    // Export for module systems
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = InputGroupDelete;
    }
})(window, document);
