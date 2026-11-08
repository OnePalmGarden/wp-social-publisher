(function () {
	function setup() {
		const box = document.querySelector('.wsp-x-preview');
		if (!box || box.dataset.wspReady) {
			return;
		}
		box.dataset.wspReady = '1';
		const input = box.querySelector('textarea');
		const enabled = box.querySelector('.wsp-x-fields-enabled');
		const fields = Array.from(box.querySelectorAll('.wsp-x-field'));
		const preview = box.querySelector('.wsp-post-preview-text');
		const counter = box.querySelector('.wsp-x-count');
		let values;
		try {
			values = JSON.parse(box.dataset.values);
		} catch (error) {
			counter.textContent = 'Karakter sayacı yüklenemedi. Yazıyı yeniden açın.';
			return;
		}
		function render(template) {
			return template.replace(/\{title\}|\{excerpt\}|\{url\}|\{tags\}|\{hashtags\}|\{site_title\}/g, function (tag) {
				return values[tag];
			});
		}
		function update() {
			const parts = fields.map(function (field) {
				const value = render(field.value.trim());
				const count = field.parentElement.querySelector('.wsp-x-field-count');
				count.textContent = 'Karakter: ' + Array.from(value).length;
				return value;
			});
			const customFields = enabled && enabled.checked;
			const template = input.value !== '' ? input.value : box.dataset.template;
			let text = customFields ? parts.filter(function (part) { return part.trim() !== ''; }).join('\n\n') : render(template);
			if (!customFields && values['{url}'] && !text.includes(values['{url}'])) {
				text += '\n\n' + values['{url}'];
			}
			const count = Array.from(text).length;
			preview.textContent = text;
			counter.textContent = 'Tahmini karakter sayısı: ' + count + ' / 140' +
				(count > 140 ? ' — 140 karakterlik hedef aşıldı.' : '');
			counter.style.color = count > 140 ? '#b32d2e' : '';
		}
		input.addEventListener('input', update);
		fields.forEach(function (field) { field.addEventListener('input', update); });
		if (enabled) {
			enabled.addEventListener('change', update);
		}
		update();
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', setup);
	} else {
		setup();
	}
})();
