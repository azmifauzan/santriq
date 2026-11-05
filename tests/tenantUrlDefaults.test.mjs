import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

function loadModule(path, imports = {}) {
    const source = ts.transpileModule(
        readFileSync(new URL(path, import.meta.url), 'utf8'),
        {
            compilerOptions: {
                module: ts.ModuleKind.CommonJS,
                target: ts.ScriptTarget.ES2022,
            },
        },
    ).outputText;
    const exports = {};
    runInNewContext(source, { exports, require: (name) => imports[name] });

    return exports;
}

test('tenant URL defaults are ready before the first page renders and follow navigation', () => {
    const wayfinder = loadModule('../resources/js/wayfinder/index.ts');
    let onNavigate;
    const { initializeTenantUrlDefaults } = loadModule(
        '../resources/js/lib/tenantUrlDefaults.ts',
        {
            '@inertiajs/vue3': {
                router: {
                    on: (event, callback) => {
                        assert.equal(event, 'navigate');
                        onNavigate = callback;
                    },
                },
            },
            '@/wayfinder': wayfinder,
        },
    );

    initializeTenantUrlDefaults({ props: { subdomain: 'tpq-pertama' } });
    assert.equal(
        wayfinder.applyUrlDefaults({ student: 7 }).subdomain,
        'tpq-pertama',
    );
    assert.equal(wayfinder.applyUrlDefaults({ student: 7 }).student, 7);
    assert.equal(
        wayfinder.applyUrlDefaults({ subdomain: 'explicit' }).subdomain,
        'explicit',
    );

    onNavigate({ detail: { page: { props: { subdomain: 'tpq-kedua' } } } });
    assert.equal(wayfinder.applyUrlDefaults({}).subdomain, 'tpq-kedua');

    onNavigate({ detail: { page: { props: { subdomain: null } } } });
    assert.equal(wayfinder.applyUrlDefaults({}).subdomain, undefined);
});
