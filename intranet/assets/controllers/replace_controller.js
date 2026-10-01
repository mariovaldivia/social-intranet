import { Controller } from '@hotwired/stimulus';

/*
 * Replaces this element with the HTML returned by the clicked link.
 * Used by the like buttons: like_post returns the re-rendered post actions
 * and like_comment the re-rendered comment, both with this controller on
 * their root so the new element keeps working.
 *
 *   <div data-controller="replace">
 *       <a href="/post/1/like" data-action="replace#load">Like</a>
 *   </div>
 */
export default class extends Controller {
    async load(event) {
        event.preventDefault();

        const response = await fetch(event.currentTarget.href);
        if (!response.ok) {
            console.error(`Request to ${response.url} failed with status ${response.status}`);
            return;
        }

        this.element.outerHTML = await response.text();
    }
}
