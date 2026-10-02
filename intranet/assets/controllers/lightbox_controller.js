import { Controller } from '@hotwired/stimulus';

/*
 * Full-size photo viewer for an album, in a <dialog>, with previous/next
 * buttons and arrow keys. Each thumbnail is an item target carrying the
 * large image URL and caption:
 *
 *   <div data-controller="lightbox">
 *       <button data-lightbox-target="item" data-src="/media/..." data-caption="..."
 *               data-action="lightbox#open" data-lightbox-index-param="0">...</button>
 *       <dialog data-lightbox-target="dialog"
 *               data-action="keydown.left->lightbox#previous keydown.right->lightbox#next">
 *           <img data-lightbox-target="image">
 *           <p data-lightbox-target="caption"></p>
 *           <span data-lightbox-target="counter"></span>
 *       </dialog>
 *   </div>
 */
export default class extends Controller {
    static targets = ['item', 'dialog', 'image', 'caption', 'counter'];

    open(event) {
        this.index = event.params.index;
        this.show();
        this.dialogTarget.showModal();
    }

    next() {
        this.index = (this.index + 1) % this.itemTargets.length;
        this.show();
    }

    previous() {
        this.index = (this.index - 1 + this.itemTargets.length) % this.itemTargets.length;
        this.show();
    }

    show() {
        const item = this.itemTargets[this.index];
        this.imageTarget.src = item.dataset.src;
        this.imageTarget.alt = item.dataset.caption || '';
        this.captionTarget.textContent = item.dataset.caption || '';
        this.counterTarget.textContent = `${this.index + 1} / ${this.itemTargets.length}`;
    }
}
