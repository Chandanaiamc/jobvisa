/**
 * Sprint 4.8 – minimal accessibility helpers.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var skip = document.querySelector('.skip-link');
    var main = document.getElementById('main');
    if (!skip || !main) {
      return;
    }

    skip.addEventListener('click', function () {
      if (!main.hasAttribute('tabindex')) {
        main.setAttribute('tabindex', '-1');
      }
      window.setTimeout(function () {
        main.focus({ preventScroll: false });
      }, 0);
    });
  });
})();
