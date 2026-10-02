import { Controller } from '@hotwired/stimulus';

/*
 * Comment thread of a post: loads the comment form and submits it.
 * The form opens from the Comment button or from a click anywhere on the
 * post. post_comment answers 200 with the new comment (appended to the
 * list) or 422 with the form and its validation errors (shown in place of
 * the form).
 *
 *   <div data-controller="comments"
 *        data-comments-url-value="/post/1/comment"
 *        data-action="click->comments#openFromPost">
 *       <a href="/post/1/comment" data-action="comments#open">Comment</a>
 *       <div data-comments-target="list">...</div>
 *       <div data-comments-target="form"></div>
 *   </div>
 *
 * The loaded form needs data-action="comments#submit".
 */
export default class extends Controller {
    static targets = ['list', 'form'];
    static values = { url: String };

    // Elements that do their own thing when clicked
    static INTERACTIVE = 'a, button, input, textarea, select, label, [role="button"]';

    open(event) {
        event.preventDefault();
        this.load();
    }

    openFromPost(event) {
        if (event.target.closest(this.constructor.INTERACTIVE)
            || this.formTarget.contains(event.target)
            || window.getSelection()?.toString()) {
            return;
        }

        this.load();
    }

    async load() {
        // Already open: keep what was typed, just focus it
        const textarea = this.formTarget.querySelector('textarea');
        if (textarea) {
            textarea.focus();
            return;
        }
        if (this.loading) {
            return;
        }

        this.loading = true;
        try {
            const response = await fetch(this.urlValue);
            if (!response.ok) {
                console.error(`Request to ${response.url} failed with status ${response.status}`);
                return;
            }

            this.showForm(await response.text());
        } finally {
            this.loading = false;
        }
    }

    async submit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const button = form.querySelector('button');
        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: new FormData(form),
            });
            const html = await response.text();

            if (response.status === 422) {
                this.showForm(html);
            } else if (response.ok) {
                this.listTarget.insertAdjacentHTML('beforeend', html);
                form.reset();
                form.querySelector('textarea')?.focus();
            } else {
                console.error(`Request to ${response.url} failed with status ${response.status}`);
            }
        } finally {
            button.disabled = false;
        }
    }

    showForm(html) {
        this.formTarget.innerHTML = html;
        this.formTarget.querySelector('textarea')?.focus();
    }
}
