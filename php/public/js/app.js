


'use strict';

document.addEventListener('DOMContentLoaded', () => {
    
    const flash = document.querySelector('.flash');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity 0.5s';
            setTimeout(() => flash.remove(), 500);
        }, 5000);
    }

    
    const currentRoute = new URLSearchParams(window.location.search).get('route') || 'feed';
    document.querySelectorAll('.nav-links a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(`route=${currentRoute}`)) {
            link.classList.add('nav-active');
        }
    });
});
