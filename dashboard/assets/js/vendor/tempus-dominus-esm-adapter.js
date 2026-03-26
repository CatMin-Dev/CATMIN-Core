import '/rework/vendor/node_modules/@eonasdan/tempus-dominus/dist/js/tempus-dominus.js';

const tempusDominusNamespace = globalThis.tempusDominus || globalThis.window?.tempusDominus || {};
const TempusDominus = tempusDominusNamespace.TempusDominus || tempusDominusNamespace.default || tempusDominusNamespace;

export default TempusDominus;
export { TempusDominus };
