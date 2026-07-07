/*
 * Debounced inline linting through the admin-only XHR route, which runs the same server-side
 * parse + whitelist used on save.
 */

export function createLintSource(lintUrl, trigger) {
    return async (view) => {
        const expression = view.state.doc.toString();
        if (expression.trim() === '') {
            return [];
        }

        const response = await fetch(lintUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({expression, trigger: trigger || null}),
        });

        if (!response.ok) {
            return [];
        }

        const payload = await response.json();

        return (payload.diagnostics || []).map((diagnostic) => ({
            from: 0,
            to: view.state.doc.length,
            severity: 'error',
            message: diagnostic.message,
        }));
    };
}
