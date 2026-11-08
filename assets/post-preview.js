(function () {
	function setup() {
		const box = document.querySelector('.wsp-x-preview');
		if (!box || box.dataset.wspReady) {
			return;
		}
		box.dataset.wspReady = '1';
		const input = box.querySelector('textarea');
		const preview = box.querySelector('.wsp-post-preview-text');
		const counter = box.querySelector('.wsp-x-count');
		let values;
		try {
			values = JSON.parse(box.dataset.values);
		} catch (error) {
			counter.textContent = 'Karakter sayacı yüklenemedi. Yazıyı yeniden açın.';
			return;
		}
		function update() {
			const template = input.value !== '' ? input.value : box.dataset.template;
			const text = template.replace(/\{title\}|\{excerpt\}|\{url\}|\{tags\}|\{site_title\}/g, function (tag) {
				return values[tag];
			});
			const count = Array.from(text).length;
			preview.textContent = text;
			counter.textContent = 'Tahmini karakter sayısı: ' + count + ' / 140' +
				(count > 140 ? ' — 140 karakterlik hedef aşıldı.' : '');
			counter.style.color = count > 140 ? '#b32d2e' : '';
		}
		input.addEventListener('input', update);
		update();
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', setup);
	} else {
		setup();
	}
})();
