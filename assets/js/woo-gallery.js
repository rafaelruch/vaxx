/**
 * VAXX · Galeria da PDP
 *
 * O markup (thumbs com data-src, setas, contador) já existia em
 * woocommerce/single-product.php, mas nenhum script ligava as peças —
 * clicar no thumb não trocava a foto principal.
 */
(function () {
	'use strict';

	var main = document.getElementById('galleryMainImg');
	var thumbsWrap = document.getElementById('galleryThumbs');
	if (!main || !thumbsWrap) return;

	var thumbs = Array.prototype.slice.call(thumbsWrap.querySelectorAll('.gallery__thumb'));
	if (thumbs.length < 2) return;

	var counter = document.getElementById('galleryCounter');
	var prev = document.getElementById('galleryPrev');
	var next = document.getElementById('galleryNext');
	var atual = Math.max(0, thumbs.findIndex(function (t) {
		return t.classList.contains('is-active');
	}));

	// Pré-carrega as fotos grandes para a troca não piscar.
	thumbs.forEach(function (t) {
		var src = t.getAttribute('data-src');
		if (src) new Image().src = src;
	});

	function mostrar(i) {
		i = (i + thumbs.length) % thumbs.length;
		var src = thumbs[i].getAttribute('data-src');
		if (!src) return;

		main.src = src;
		atual = i;

		thumbs.forEach(function (t, n) {
			var on = n === i;
			t.classList.toggle('is-active', on);
			t.setAttribute('aria-selected', on ? 'true' : 'false');
		});

		if (counter) counter.textContent = (i + 1) + ' / ' + thumbs.length;

		thumbs[i].scrollIntoView({ block: 'nearest', inline: 'nearest' });
	}

	thumbs.forEach(function (t, i) {
		t.addEventListener('click', function () { mostrar(i); });
	});

	if (prev) prev.addEventListener('click', function () { mostrar(atual - 1); });
	if (next) next.addEventListener('click', function () { mostrar(atual + 1); });

	// Setas do teclado quando a galeria tem foco.
	var gal = document.getElementById('gallery');
	if (gal) {
		gal.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { mostrar(atual - 1); e.preventDefault(); }
			if (e.key === 'ArrowRight') { mostrar(atual + 1); e.preventDefault(); }
		});
	}

	// Swipe no mobile.
	var x0 = null;
	var mainBox = document.getElementById('galleryMain');
	if (mainBox) {
		mainBox.addEventListener('touchstart', function (e) {
			x0 = e.changedTouches[0].clientX;
		}, { passive: true });
		mainBox.addEventListener('touchend', function (e) {
			if (x0 === null) return;
			var dx = e.changedTouches[0].clientX - x0;
			if (Math.abs(dx) > 40) mostrar(dx < 0 ? atual + 1 : atual - 1);
			x0 = null;
		}, { passive: true });
	}
})();
