/*
 * The expression editor bootstrap. CodeMirror 6 has no official single-file browser build, so
 * it is loaded as version-pinned ESM imports from a CDN (the base URL comes from the bundle
 * config and can point at self-hosted files for intranet or strict-CSP setups). This glue is
 * hand-written, buildless ES modules — no Node toolchain.
 */

import {createElLanguage} from './el-language.js';
import {createCompletionSource} from './completion.js';
import {createLintSource} from './lint.js';
import {renderReferencePanel} from './reference-panel.js';

const CODEMIRROR_VERSION = '6.0.1';

async function loadCodeMirror(cdnBaseUrl) {
    const base = cdnBaseUrl.replace(/\/$/, '');
    const [codemirror, language, autocomplete, lint] = await Promise.all([
        import(`${base}/codemirror@${CODEMIRROR_VERSION}`),
        import(`${base}/@codemirror/language@6`),
        import(`${base}/@codemirror/autocomplete@6`),
        import(`${base}/@codemirror/lint@6`),
    ]);

    return {codemirror, language, autocomplete, lint};
}

export async function enhance(textarea) {
    if (textarea.dataset.setonoSyliusLoyaltyEnhanced === '1') {
        return;
    }
    textarea.dataset.setonoSyliusLoyaltyEnhanced = '1';

    const catalog = JSON.parse(textarea.dataset.catalog || '{}');
    const trigger = textarea.dataset.trigger || null;
    const {codemirror, language, autocomplete, lint} = await loadCodeMirror(textarea.dataset.cdnBaseUrl || 'https://esm.sh');

    const view = new codemirror.EditorView({
        doc: textarea.value,
        extensions: [
            codemirror.basicSetup,
            createElLanguage(language.StreamLanguage),
            autocomplete.autocompletion({override: [createCompletionSource(catalog, trigger)]}),
            lint.linter(createLintSource(textarea.dataset.lintUrl, trigger), {delay: 500}),
            codemirror.EditorView.updateListener.of((update) => {
                if (update.docChanged) {
                    textarea.value = update.state.doc.toString();
                }
            }),
        ],
    });

    textarea.style.display = 'none';
    textarea.after(view.dom);
    view.dom.classList.add('setono-sylius-loyalty-expression-editor');
    view.dom.style.cssText = 'border:1px solid rgba(34,36,38,.15);border-radius:.28em;background:#fff;min-height:3.5em;';

    renderReferencePanel(
        view.dom.parentElement,
        catalog,
        trigger,
        (expression) => {
            view.dispatch({changes: {from: 0, to: view.state.doc.length, insert: expression}});
            textarea.value = expression;
        },
        JSON.parse(textarea.dataset.referenceTranslations || '{}'),
    );
}

export function enhanceAll(root = document) {
    for (const textarea of root.querySelectorAll('textarea[data-setono-sylius-loyalty-expression]')) {
        enhance(textarea);
    }
}

enhanceAll();
