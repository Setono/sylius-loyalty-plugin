/*
 * Catalog-driven autocompletion: the data-catalog attribute carries the same typed
 * class-to-members map that drives the server-side sandbox, so completion can never suggest
 * a path the validator rejects. Dotted chains are resolved by walking the type graph.
 */

export function createCompletionSource(catalog, trigger) {
    const variables = availableVariables(catalog, trigger);

    return (context) => {
        const chain = context.matchBefore(/[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]*)*/);
        if (chain === null && !context.explicit) {
            return null;
        }

        const text = chain ? chain.text : '';
        const parts = text.split('.');

        if (parts.length === 1) {
            const options = [];
            for (const [name, variable] of Object.entries(variables)) {
                options.push({label: name, type: 'variable', detail: variable.type});
            }
            for (const fn of catalog.functions || []) {
                options.push({label: fn.name, type: 'function', detail: fn.signature, info: fn.description, apply: fn.name + '()'});
            }

            return {from: chain ? chain.from : context.pos, options, validFor: /^[a-zA-Z0-9_]*$/};
        }

        // Resolve the chain up to the last complete segment through the type graph
        let type = (variables[parts[0]] || {}).type;
        for (let i = 1; i < parts.length - 1 && type; i++) {
            const members = catalog.types[type] || {};
            type = members[parts[i]];
        }

        const members = type ? catalog.types[type] || {} : {};
        const from = chain.from + text.lastIndexOf('.') + 1;

        return {
            from,
            options: Object.entries(members).map(([name, memberType]) => ({
                label: name,
                type: 'property',
                detail: memberType,
            })),
            validFor: /^[a-zA-Z0-9_]*$/,
        };
    };
}

export function availableVariables(catalog, trigger) {
    const variables = {};
    for (const [name, variable] of Object.entries(catalog.variables || {})) {
        if (variable.triggers === null || !trigger || variable.triggers.includes(trigger)) {
            variables[name] = variable;
        }
    }

    return variables;
}
