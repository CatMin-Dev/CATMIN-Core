import '/rework/vendor/node_modules/jszip/dist/jszip.min.js';

const JSZip = globalThis.JSZip || globalThis.window?.JSZip;

export default JSZip;
export { JSZip };
