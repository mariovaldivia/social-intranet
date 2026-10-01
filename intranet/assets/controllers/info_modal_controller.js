import { Controller } from '@hotwired/stimulus';

/*
 * Shared <dialog> that shows the HTML returned by a link, titled with the
 * link's title (e.g. who liked a post). The controller sits on <body>, so
 * any link on the page can open it:
 *
 *   <a href="/post/1/likes" title="People who likes this post"
 *      data-action="info-modal#open">3</a>
 */
export default class extends Controller {
    static targets = ['dialog', 'title', 'body'];
    // Translated message shown when loading fails (data-info-modal-error-value)
    static values = { error: String };

    async open(event) {
        event.preventDefault();

        const link = event.currentTarget;
        this.titleTarget.textContent = link.title;
        this.bodyTarget.innerHTML = '<span class="loading loading-spinner"></span>';
        this.dialogTarget.showModal();

        const response = await fetch(link.href);
        if (response.ok) {
            this.bodyTarget.innerHTML = await response.text();
            return;
        }

        const error = document.createElement('p');
        error.className = 'text-error';
        error.textContent = this.errorValue;
        this.bodyTarget.replaceChildren(error);
    }
}
