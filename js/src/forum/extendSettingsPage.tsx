import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Switch from 'flarum/common/components/Switch';
import type SettingsPage from 'flarum/forum/components/SettingsPage';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function extendSettingsPage(): void {
  extend(
    'flarum/forum/components/SettingsPage',
    'privacyItems',
    function (this: SettingsPage & Record<string, unknown>, items: ItemList<Mithril.Children>) {
      const user = this.user;
      if (!user) return;

      const key = 'datlechin-link-clicks.opt_out';
      const loadingKey = 'linkClicksOptOutLoading' as const;

      items.add(
        'linkClicksOptOut',
        [
          <Switch
            state={!!(user.preferences() as Record<string, boolean | undefined> | null)?.[key]}
            loading={this[loadingKey] as boolean | undefined}
            onchange={(value: boolean) => {
              this[loadingKey] = true;
              m.redraw();

              user.savePreferences({ [key]: value }).then(
                () => {
                  this[loadingKey] = false;
                  m.redraw();
                },
                () => {
                  this[loadingKey] = false;
                  app.alerts.show(
                    { type: 'error' },
                    app.translator.trans('datlechin-link-clicks.forum.settings.opt_out_save_failed')
                  );
                  m.redraw();
                }
              );
            }}
          >
            {app.translator.trans('datlechin-link-clicks.forum.settings.opt_out_label')}
          </Switch>,
          <p className="helpText">{app.translator.trans('datlechin-link-clicks.forum.settings.opt_out_help')}</p>,
        ],
        40
      );
    }
  );
}
