/*
 * Variant-aware earn hint: the number swaps on variant change without XHR, using the
 * server-rendered per-variant map. Mirrors the selector mechanics of Sylius'
 * sylius-variants-prices.js for option-based selection and listens to the variant radios
 * for choice-based selection.
 */
(function () {
    'use strict';

    var hint = document.getElementById('setono-sylius-loyalty-earn-hint');
    if (!hint) {
        return;
    }

    var map = document.getElementById('setono-sylius-loyalty-earn-hint-map');
    var text = document.getElementById('setono-sylius-loyalty-earn-hint-text');
    var template = hint.getAttribute('data-template');

    function render(entry) {
        var points = entry ? entry.getAttribute('data-points') : '';
        if (points === '' || points === null) {
            hint.style.display = 'none';

            return;
        }
        hint.style.display = '';
        text.textContent = template.replace('%points%', points);
    }

    function onOptionsChange() {
        var selector = '';
        document.querySelectorAll('#sylius-product-adding-to-cart select[data-option]').forEach(function (select) {
            selector += '[data-' + select.getAttribute('data-option') + '="' + select.value + '"]';
        });
        if (selector !== '') {
            render(map.querySelector('span' + selector));
        }
    }

    function onVariantChoice(event) {
        render(map.querySelector('span[data-variant-code="' + event.target.value + '"]'));
    }

    document.querySelectorAll('#sylius-product-adding-to-cart select[data-option]').forEach(function (select) {
        select.addEventListener('change', onOptionsChange);
    });
    document.querySelectorAll('[name*="[variant]"][type="radio"]').forEach(function (radio) {
        radio.addEventListener('change', onVariantChoice);
    });
})();
