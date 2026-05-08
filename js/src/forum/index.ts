import app from 'flarum/forum/app';
import addPopularLinksWidget from './addPopularLinksWidget';
import addUserPopularLinksWidget from './addUserPopularLinksWidget';
import extendPostControls from './extendPostControls';
import extendRealtime from './extendRealtime';
import extendSettingsPage from './extendSettingsPage';

app.initializers.add('datlechin-link-clicks', () => {
  addPopularLinksWidget();
  addUserPopularLinksWidget();
  extendSettingsPage();
  extendPostControls();

  if ('flarum-realtime' in flarum.extensions) {
    extendRealtime();
  }
});
