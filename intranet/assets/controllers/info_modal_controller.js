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

    async open(event) {
        event.preventDefault();

        const link = event.currentTarget;
        this.titleTarget.textContent = link.title;
        this.bodyTarget.innerHTML = '<span class="loading loading-spinner"></span>';
        this.dialogTarget.showModal();

        const response = await fetch(link.href);
        this.bodyTarget.innerHTML = response.ok
            ? await response.text()
            : '<p class="text-error">Could not load this content.</p>';
    }
}
