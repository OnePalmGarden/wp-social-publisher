const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const input = {
	value: '',
	addEventListener(event, callback) { this.callback = callback; }
};
const preview = { textContent: '' };
const counter = { textContent: '', style: {} };
function makeField(value) {
	const counter = { textContent: '' };
	return {
		value, counter,
		parentElement: { querySelector() { return counter; } },
		addEventListener(event, callback) { this.callback = callback; }
	};
}
const enabled = { checked: false, addEventListener(event, callback) { this.callback = callback; } };
const fields = [makeField('🔥 {title}'), makeField('içerik'), makeField('👉 {url}'), makeField('🔍 {hashtags}')];
const box = {
	dataset: {
		limit: '200',
		template: '{title}\n{url}\n{tags}',
		values: JSON.stringify({ '{title}': 'Başlık', '{url}': 'https://example.test/yazi', '{tags}': 'etiket', '{hashtags}': '#etiket #sosyalmedya' })
	},
	querySelector(selector) {
		return { textarea: input, '.wsp-post-preview-text': preview, '.wsp-x-count': counter, '.wsp-x-fields-enabled': enabled }[selector];
	},
	querySelectorAll() { return fields; }
};
const document = { readyState: 'complete', querySelector: () => box };
vm.runInNewContext(fs.readFileSync(require('path').join(__dirname, '../assets/post-preview.js'), 'utf8'), { document, Array, JSON });
assert.strictEqual(preview.textContent, 'Başlık\nhttps://example.test/yazi\netiket');
assert.strictEqual(counter.textContent, 'Tahmini karakter sayısı: 39 / 200');
input.value = '{hashtags}';
input.callback();
assert.strictEqual(preview.textContent, '#etiket #sosyalmedya\n\nhttps://example.test/yazi');
assert.strictEqual(counter.textContent, 'Tahmini karakter sayısı: 47 / 200');
input.value = '😀'.repeat(140);
input.callback();
assert.strictEqual(counter.textContent, 'Tahmini karakter sayısı: 167 / 200');
enabled.checked = true;
enabled.callback();
assert.strictEqual(preview.textContent, '🔥 Başlık\n\niçerik\n\n👉 https://example.test/yazi\n\n🔍 #etiket #sosyalmedya');
assert.strictEqual(fields[0].counter.textContent, 'Karakter: 8');
fields[0].value = '😀'.repeat(140);
fields.slice(1).forEach(function (field) { field.value = ''; });
fields[0].callback();
assert.strictEqual(counter.textContent, 'Tahmini karakter sayısı: 140 / 200');
fields[0].value += 'a';
fields[0].callback();
assert.strictEqual(counter.textContent, 'Tahmini karakter sayısı: 141 / 200');
fields[0].value = '😀'.repeat(201);
fields[0].callback();
assert.ok(counter.textContent.includes('201 / 200') && counter.textContent.includes('aşıldı'));
assert.strictEqual(counter.style.color, '#b32d2e');
enabled.checked = false;
input.value = '';
input.callback();
assert.strictEqual(preview.textContent, 'Başlık\nhttps://example.test/yazi\netiket');
console.log('X preview checks passed.');
