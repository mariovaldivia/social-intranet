import { Controller } from '@hotwired/stimulus';

/*
 * Submits the textarea's form on Enter; Shift+Enter inserts a line break.
 *
 *   <textarea data-controller="submit-on-enter"
 *             data-action="keydown->submit-on-enter#submit"></textarea>
 */
export default class extends Controller {
    submit(event) {
        if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
            return;
        }

        event.preventDefault();
        this.element.form.requestSubmit();
    }
}
