/* Shim over the vendored single-instance bundle — see bundle.js */
import {codemirror} from './bundle.js';
export const {EditorView, basicSetup} = codemirror;
