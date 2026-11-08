const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const input = {
	value: '',
	addEventListener(event, callback) { this.callback = callback; }
};
const preview = { textContent: '' };
const counter = { textContent: '', style: {} };
const box = {
	dataset: {
		template: '{title}\n{url}\n{tags}',
		values: JSON.stringify({ '{title}': 'Başlık', '{url}': 'https://example.test/yazi', '{tags}': 'etiket' })
	},
	querySelector(selector) {
		return { textarea: input, '.wsp-post-preview-text': preview, '.wsp-x-count': counter }[selector];
	}
};
const document = { readyState: 'complete', querySelector: () => box };
vm.runInNewContext(fs.readFileSync(require('path').join(__dirname, '../assets/post-preview.js'), 'utf8'), { document, Array, JSON });
assert.strictEqual(preview.textContent, 'Başlık\nhttps://example.test/yazi\netiket');
assert.strictEqual(counter.textContent, 'Tahmini karakter sayısı: 39 / 140');
input.value = '😀'.repeat(140);
input.callback();
assert.strictEqual(counter.textContent, 'Tahmini karakter sayısı: 140 / 140');
input.value += 'a';
input.callback();
assert.ok(counter.textContent.includes('141 / 140') && counter.textContent.includes('aşıldı'));
assert.strictEqual(counter.style.color, '#b32d2e');
input.value = '';
input.callback();
assert.strictEqual(preview.textContent, 'Başlık\nhttps://example.test/yazi\netiket');
console.log('X preview checks passed.');
