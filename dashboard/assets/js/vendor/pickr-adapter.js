import '/rework/vendor/node_modules/@simonwep/pickr/dist/pickr.min.js';

const Pickr = globalThis.Pickr || (typeof window !== 'undefined' ? window.Pickr : undefined);

if (!Pickr || typeof Pickr.create !== 'function') {
  throw new Error('Pickr adapter failed: @simonwep/pickr did not expose a valid Pickr.create API.');
}

export default Pickr;
