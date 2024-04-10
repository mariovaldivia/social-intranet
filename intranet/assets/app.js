import './bootstrap.js';
import 'bootstrap/dist/css/bootstrap.min.css';

import 'bootstrap';
// import '@fortawesome/fontawesome-free/css/fontawesome.min.css';
// import '@fortawesome/fontawesome-free/fontawesome-free.index.js';
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

live('a.add-comment', 'click', function(event){
    event.preventDefault();
    console.log(event.target.href)
    axios.get(event.target.href).then(function(response){
        let parent = event.target.closest(".post")
        if(parent){
            let form_div = parent.querySelector(".comment-form")
            form_div.innerHTML = response.data
        }
        
    })
})

live('.comment-form form', 'submit', function(event){
    event.preventDefault();
    console.log(event.target.action)
    const formData = new FormData(event.target);
    axios.post(event.target.action, formData)
        .then(function(response){
            let parent = event.target.closest(".post")
            if(parent){
                let comments = parent.querySelector(".comments")
                let div = document.createElement('div');
                div.innerHTML = response.data
                comments.insertBefore(div, comments.firstChild)
            }
        })

})