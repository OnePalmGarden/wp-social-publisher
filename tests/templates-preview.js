const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const input = {
	value: '{tags} | {hashtags}',
	addEventListener() {}
};
const panel = {
	hidden: false,
	querySelector() { return input; },
	querySelectorAll() { return [input]; }
};
const preview = { textContent: '' };
const builder = {
	querySelectorAll(selector) {
		return selector === '.wsp-platform-fields' ? [panel] : [];
	},
	querySelector() { return preview; }
};
const document = {
	addEventListener(event, callback) { callback(); },
	querySelector() { return builder; }
};
vm.runInNewContext(fs.readFileSync(require('path').join(__dirname, '../assets/templates.js'), 'utf8'), { document, Array });
assert.strictEqual(preview.textContent, 'WordPress, içerik, sosyal medya | #WordPress #içerik #sosyalmedya');
console.log('Template preview checks passed.');
