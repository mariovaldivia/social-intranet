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
        }
    })
})

live('.comment-form form', 'submit', function(event){
    event.preventDefault();
    const form = this
    const formData = new FormData(form);
    axios.post(form.action, formData)
        .then(function(response){
            let parent = form.closest(".post")
            if(parent){
                let comments = parent.querySelector(".comments")
                let div = document.createElement('div');
                div.innerHTML = response.data
                comments.insertBefore(div, comments.firstChild)

                let input = parent.querySelector("textarea")
                input.value = ""
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
