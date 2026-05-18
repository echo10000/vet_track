// assets/js/main.js

document.addEventListener('DOMContentLoaded', function () {
    const loader = document.getElementById('pageLoader');

    if (!loader) {
        return;
    }

    window.addEventListener('beforeunload', function () {
        loader.classList.remove('is-complete');
        loader.classList.add('is-active');
    });

    requestAnimationFrame(function () {
        loader.classList.add('is-complete');
    });
});
