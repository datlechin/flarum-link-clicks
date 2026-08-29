// The shared config's setup file boots a whole Flarum app from `@flarum/core`,
// which a standalone extension repo has no way to resolve — it only exists
// inside the framework monorepo. These tests are DOM-level and need none of
// it, so the setup is dropped rather than stubbed. Restore it (and vendor
// `@flarum/core`) if a test ever needs `app`, the store, or the translator.
module.exports = require('@flarum/jest-config')({ setupFilesAfterEnv: [] });
