import { Controller } from '@hotwired/stimulus';

/*
 * Opens and closes a <dialog>. With data-dialog-open-value="true" it opens
 * on load (e.g. a form in the dialog came back with validation errors).
 *
 *   <div data-controller="dialog" data-dialog-open-value="false">
 *       <button type="button" data-action="dialog#open">...</button>
 *       <dialog data-dialog-target="dialog">... <button data-action="dialog#close"></dialog>
 *   </div>
 */
export default class extends Controller {
    static targets = ['dialog'];
    static values = { open: Boolean };

    connect() {
        if (this.openValue) {
            this.open();
        }
    }

    open() {
        this.dialogTarget.showModal();
        this.dialogTarget.querySelector('textarea, input:not([type=hidden])')?.focus();
    }

    close() {
        this.dialogTarget.close();
    }
}
