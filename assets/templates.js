document.addEventListener('DOMContentLoaded', function () {
	const builder = document.querySelector('.wsp-template-builder');
	if (!builder) {
		return;
	}

	const tabs = Array.from(builder.querySelectorAll('.wsp-platform-tab'));
	const panels = Array.from(builder.querySelectorAll('.wsp-platform-fields'));
	const previewText = builder.querySelector('.wsp-preview-text');
	const smartValues = {
		'{title}': 'Örnek yazı başlığı',
		'{excerpt}': 'Bu alanda yazının özeti gösterilir. Yazı metni ve görseller, platforma göre ayrıca biçimlendirilir.',
		'{url}': 'https://ornek-site.com/ornek-yazi/',
		'{tags}': 'WordPress, içerik, sosyal medya',
		'{hashtags}': '#WordPress #içerik #sosyalmedya',
		'{site_title}': 'Örnek Site'
	};
	let activePanel = panels.find(function (panel) {
		return !panel.hidden;
	});
	let lastFocusedInput = activePanel ? activePanel.querySelector('.wsp-template-input') : null;

	function updatePreview() {
		if (!activePanel || !previewText) {
			return;
		}
		const parts = Array.from(activePanel.querySelectorAll('.wsp-template-input'))
			.map(function (input) {
				return input.value.trim().replace(/\{title\}|\{excerpt\}|\{url\}|\{tags\}|\{hashtags\}|\{site_title\}/g, function (tag) {
					return smartValues[tag];
				});
			})
			.filter(Boolean);
		previewText.textContent = parts.join('\n\n');
	}

	tabs.forEach(function (tab) {
		tab.addEventListener('click', function () {
			tabs.forEach(function (item) {
				const selected = item === tab;
				item.classList.toggle('is-active', selected);
				item.setAttribute('aria-pressed', selected ? 'true' : 'false');
			});
			panels.forEach(function (panel) {
				panel.hidden = panel.dataset.platform !== tab.dataset.platform;
			});
			activePanel = panels.find(function (panel) {
				return panel.dataset.platform === tab.dataset.platform;
			});
			lastFocusedInput = activePanel ? activePanel.querySelector('.wsp-template-input') : null;
			updatePreview();
		});
	});

	panels.forEach(function (panel) {
		panel.querySelectorAll('.wsp-template-input').forEach(function (input) {
			input.addEventListener('focus', function () {
				lastFocusedInput = input;
			});
			input.addEventListener('input', updatePreview);
		});
	});

	builder.querySelectorAll('[data-smart-tag]').forEach(function (button) {
		button.addEventListener('click', function () {
			if (!activePanel) {
				return;
			}
			const target = lastFocusedInput && activePanel.contains(lastFocusedInput)
				? lastFocusedInput
				: activePanel.querySelector('.wsp-template-input');
			if (!target) {
				return;
			}
			const start = target.selectionStart ?? target.value.length;
			const end = target.selectionEnd ?? start;
			const tag = button.dataset.smartTag;
			target.value = target.value.slice(0, start) + tag + target.value.slice(end);
			target.focus();
			target.setSelectionRange(start + tag.length, start + tag.length);
			lastFocusedInput = target;
			target.dispatchEvent(new Event('input', { bubbles: true }));
		});
	});

	updatePreview();
});
