import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('datlechin-link-clicks', () => {
  console.log('[datlechin/flarum-link-clicks] Hello, admin!');
});
