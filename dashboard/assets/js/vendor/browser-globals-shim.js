(function () {
  if (!globalThis.process) {
    globalThis.process = {};
  }

  if (!globalThis.process.env) {
    globalThis.process.env = {};
  }

  if (!globalThis.process.env.NODE_ENV) {
    globalThis.process.env.NODE_ENV = 'production';
  }
})();