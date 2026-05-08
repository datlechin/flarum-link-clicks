import app from 'flarum/forum/app';

export { default as extend } from './extend';

app.initializers.add('datlechin-link-clicks', () => {
  console.log('[datlechin/flarum-link-clicks] Hello, forum!');
});
