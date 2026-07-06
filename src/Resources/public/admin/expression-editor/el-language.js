/*
 * A pragmatic StreamLanguage tokenizer for Symfony ExpressionLanguage — strings, numbers,
 * operators (incl. and/or/not/in/matches), identifiers, function calls, and properties.
 * Deliberately not a full Lezer grammar.
 */

const KEYWORD_OPERATORS = ['and', 'or', 'not', 'in', 'matches', 'starts', 'ends', 'with', 'contains'];
const CONSTANTS = ['true', 'false', 'null'];

export function createElLanguage(streamLanguage) {
    return streamLanguage.define({
        name: 'expression-language',
        token(stream) {
            if (stream.eatSpace()) {
                return null;
            }

            if (stream.match(/"([^"\\]|\\.)*"/) || stream.match(/'([^'\\]|\\.)*'/)) {
                return 'string';
            }

            if (stream.match(/\d+(\.\d+)?/)) {
                return 'number';
            }

            if (stream.match(/[a-zA-Z_][a-zA-Z0-9_]*/)) {
                const word = stream.current();
                if (KEYWORD_OPERATORS.includes(word)) {
                    return 'operatorKeyword';
                }
                if (CONSTANTS.includes(word)) {
                    return 'atom';
                }
                if (stream.peek() === '(') {
                    return 'function';
                }

                return 'variableName';
            }

            if (stream.match(/\.[a-zA-Z_][a-zA-Z0-9_]*/)) {
                return 'propertyName';
            }

            if (stream.match(/(===|!==|==|!=|<=|>=|&&|\|\||\*\*|\.\.|[+\-*/%<>!?:~])/)) {
                return 'operator';
            }

            stream.next();

            return null;
        },
    });
}
