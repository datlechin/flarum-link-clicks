import app from 'flarum/forum/app';
import addPopularLinksWidget from './addPopularLinksWidget';
import addUserPopularLinksWidget from './addUserPopularLinksWidget';
import confirmExternalClicks from './confirmExternalClicks';
import extendDiscussionControls from './extendDiscussionControls';
import extendPostControls from './extendPostControls';
import extendRealtime from './extendRealtime';
import extendSettingsPage from './extendSettingsPage';

app.initializers.add('datlechin-link-clicks', () => {
  addPopularLinksWidget();
  addUserPopularLinksWidget();
  extendSettingsPage();
  extendPostControls();
  extendDiscussionControls();
  confirmExternalClicks();

  if ('flarum-realtime' in flarum.extensions) {
    extendRealtime();
  }
});
