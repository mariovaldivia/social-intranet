import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

import {live} from './js/event.js'
import axios from 'axios'
console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// live() calls the handler with `this` set to the element matching the
// selector; event.target may be a child such as the link's <i> icon.

live('a.add-comment', 'click', function(event){
    event.preventDefault();
    const link = this
    axios.get(link.href).then(function(response){
        let parent = link.closest(".post")
        if(parent){
            let form_div = parent.querySelector(".comment-form")
            form_div.innerHTML = response.data
            form_div.querySelector("textarea")?.focus()
        }
    })
})

// Enter sends the post or comment, Shift+Enter inserts a line break
live('textarea[data-submit-on-enter]', 'keydown', function(event){
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault();
        this.form.requestSubmit();
    }
})

live('.comment-form form', 'submit', function(event){
    event.preventDefault();
    const form = this
    const formData = new FormData(form);
    // Symfony answers an invalid form with 422, which axios rejects by default
    axios.post(form.action, formData, {validateStatus: (status) => status === 200 || status === 422})
        .then(function(response){
            let parent = form.closest(".post")
            if(!parent){
                return
            }
            // A valid submit returns the new comment; an invalid one returns
            // the form again, with its errors
            const template = document.createElement('template')
            template.innerHTML = response.data.trim()
            const element = template.content.firstElementChild
            if (element && element.classList.contains('comment')) {
                parent.querySelector(".comments").appendChild(element)
                const input = form.querySelector("textarea")
                input.value = ""
                input.focus()
            } else {
                const form_div = parent.querySelector(".comment-form")
                form_div.innerHTML = response.data
                form_div.querySelector("textarea")?.focus()
            }
        })
})

live('a.comment-like', 'click', function(event){
    event.preventDefault();
    const link = this
    axios.get(link.href)
        .then(function(response){
            let parent = link.closest(".comment")
            if(parent){
                parent.outerHTML = response.data
            }
        })
})

live('a.post-like', 'click', function(event){
    event.preventDefault();
    const link = this
    axios.get(link.href)
        .then(function(response){
            let actions = link.closest(".post-actions")
            if(actions){
                actions.outerHTML = response.data
            }
        })
})

// Links with data-info-modal load their href into the shared <dialog>
live('a[data-info-modal]', 'click', function(event){
    event.preventDefault();
    const link = this
    const infoModal = document.getElementById('info-modal')
    if (!infoModal) {
        return
    }
    const modalBody = infoModal.querySelector('.modal-body')
    infoModal.querySelector('.modal-title').textContent = link.title
    modalBody.innerHTML = '<span class="loading loading-spinner"></span>'
    infoModal.showModal()
    axios.get(link.href)
        .then(function(response){
            modalBody.innerHTML = response.data
        })
})
