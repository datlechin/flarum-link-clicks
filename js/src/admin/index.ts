import app from 'flarum/admin/app';
import extendEditTagModal from './extendEditTagModal';

export { default as extend } from './extend';

app.initializers.add('datlechin-link-clicks', () => {
  app.registry.for('datlechin-link-clicks').registerPermission(
    {
      icon: 'fas fa-chart-bar',
      label: app.translator.trans('datlechin-link-clicks.admin.permissions.view_analytics_label'),
      permission: 'datlechin-link-clicks.viewAnalytics',
    },
    'moderate',
    50,
  );

  if ('flarum-tags' in flarum.extensions) {
    extendEditTagModal();
  }
});
