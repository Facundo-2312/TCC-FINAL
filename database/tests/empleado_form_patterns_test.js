'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

function fieldPattern(source, field, file) {
    const input = source.match(new RegExp('<input\\b(?=[^>]*\\bname="' + field + '")[^>]*>'));
    assert.ok(input, `${file}: missing ${field} input`);
    const pattern = input[0].match(/\bpattern="([^"]+)"/);
    assert.ok(pattern, `${file}: missing ${field} pattern`);
    return pattern[1];
}

for (const file of ['crear.php', 'Actualizar.php']) {
    const source = fs.readFileSync(path.join(__dirname, '..', '..', file), 'utf8');
    for (const flag of ['u', 'v']) {
        for (const field of ['Nombre', 'Apellido']) {
            const pattern = new RegExp('^(?:' + fieldPattern(source, field, file) + ')$', flag);
            for (const value of ['Ana', 'María-José', "O'Connor"]) {
                assert.ok(pattern.test(value), `${file}: ${field} rejected ${value}`);
            }
            for (const value of ['', 'Ana123', 'Pérez_']) {
                assert.equal(pattern.test(value), false, `${file}: ${field} accepted ${value}`);
            }
        }

        const username = new RegExp('^(?:' + fieldPattern(source, 'Usuario', file) + ')$', flag);
        for (const value of ['qa-1', 'mozo.caja', 'abc_xyz']) {
            assert.ok(username.test(value), `${file}: Usuario rejected ${value}`);
        }
        for (const value of ['ab', 'usuario invalido', 'usuario/1', 'ñandú', 'a'.repeat(51)]) {
            assert.equal(username.test(value), false, `${file}: Usuario accepted ${value}`);
        }
    }
}

console.log('PASS employee form patterns are valid and enforce the expected values');
