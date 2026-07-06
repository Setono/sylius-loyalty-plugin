/*
 * The collapsible expression reference panel, generated from the same catalog that feeds
 * autocompletion — custom variables and functions registered by plugin users appear
 * automatically. Clicking an example inserts it into the editor.
 */

import {availableVariables} from './completion.js';

export function renderReferencePanel(container, catalog, trigger, insert, translations) {
    const t = (key, fallback) => (translations && translations[key]) || fallback;

    const details = document.createElement('details');
    details.className = 'setono-sylius-loyalty-expression-reference';
    details.style.marginTop = '.5em';

    const summary = document.createElement('summary');
    summary.textContent = t('reference', 'Expression reference');
    summary.style.cursor = 'pointer';
    details.appendChild(summary);

    const body = document.createElement('div');
    body.style.cssText = 'font-size:.92em;padding:.5em .75em;border:1px solid rgba(34,36,38,.15);border-radius:.28em;margin-top:.25em;';

    body.appendChild(section(t('variables', 'Variables')));
    const variableList = document.createElement('ul');
    for (const [name, variable] of Object.entries(availableVariables(catalog, trigger))) {
        const item = document.createElement('li');
        const members = catalog.types[variable.type];
        const memberHint = members ? ' — ' + Object.keys(members).map((m) => name + '.' + m).slice(0, 4).join(', ') : '';
        item.innerHTML = `<code>${name}</code> <em>(${variable.type})</em>${memberHint}`;
        variableList.appendChild(item);
    }
    body.appendChild(variableList);

    body.appendChild(section(t('functions', 'Functions')));
    const functionList = document.createElement('ul');
    for (const fn of catalog.functions || []) {
        const item = document.createElement('li');
        item.innerHTML = `<code>${fn.signature}</code>`;
        functionList.appendChild(item);
    }
    body.appendChild(functionList);

    body.appendChild(section(t('syntax', 'Syntax')));
    const syntax = document.createElement('p');
    syntax.innerHTML = '<code>and</code> <code>or</code> <code>not</code> · <code>==</code> <code>!=</code> <code>&lt;</code> <code>&gt;=</code> · <code>a ? b : c</code> · <code>x in [1, 2]</code> · <code>name matches "/^A/"</code> · <code>"a" ~ "b"</code>';
    body.appendChild(syntax);

    body.appendChild(section(t('examples', 'Examples — click to insert')));
    const examples = [
        {label: t('example_double_points', 'Double points above 500'), expression: 'basis > 50000 ? floor(basis / 50) : floor(basis / 100)'},
        {label: t('example_weekend_bonus', 'Weekend bonus'), expression: 'day_of_week() in [6, 7]'},
        {label: t('example_first_order', 'First-order bonus'), expression: 'is_first_order() ? 500 : 0'},
    ];
    const exampleList = document.createElement('ul');
    for (const example of examples) {
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = '#';
        link.textContent = example.label;
        link.addEventListener('click', (event) => {
            event.preventDefault();
            insert(example.expression);
        });
        item.appendChild(link);
        item.appendChild(document.createTextNode(' — '));
        const code = document.createElement('code');
        code.textContent = example.expression;
        item.appendChild(code);
        exampleList.appendChild(item);
    }
    body.appendChild(exampleList);

    details.appendChild(body);
    container.appendChild(details);
}

function section(title) {
    const heading = document.createElement('h5');
    heading.textContent = title;
    heading.style.margin = '.5em 0 .25em';

    return heading;
}
