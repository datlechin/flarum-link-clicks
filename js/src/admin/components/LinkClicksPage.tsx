import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Form from 'flarum/common/components/Form';
import Button from 'flarum/common/components/Button';
import classList from 'flarum/common/utils/classList';
import LinkClicksAnalytics from './LinkClicksAnalytics';
import type Mithril from 'mithril';

type Tab = 'settings' | 'analytics' | 'webhook';

const TAB_STORAGE_KEY = 'datlechin-link-clicks.admin.tab';
const TABS: readonly Tab[] = ['settings', 'analytics', 'webhook'];

function loadTab(): Tab {
  try {
    const stored = sessionStorage.getItem(TAB_STORAGE_KEY);
    if (stored && TABS.includes(stored as Tab)) {
      return stored as Tab;
    }
  } catch {
    // sessionStorage may be blocked (private mode in some browsers).
  }
  return 'settings';
}

export default class LinkClicksPage extends ExtensionPage {
  protected activeTab: Tab = loadTab();

  protected setTab(tab: Tab): void {
    this.activeTab = tab;
    try {
      sessionStorage.setItem(TAB_STORAGE_KEY, tab);
    } catch {
      // Same as above; ignore quota / disabled storage failures.
    }
  }

  content(): Mithril.Vnode<any, any> {
    return (
      <div className="LinkClicksPage">
        <div className="LinkClicksPage-tabsBar">
          <div className="container">
            <ul className="LinkClicksPage-tabs">
              {this.renderTabButton('settings', 'fas fa-sliders')}
              {this.renderTabButton('analytics', 'fas fa-chart-bar')}
              {this.renderTabButton('webhook', 'fas fa-bolt')}
            </ul>
          </div>
        </div>

        {this.renderActiveTab()}
      </div>
    );
  }

  protected renderActiveTab(): Mithril.Vnode<any, any> {
    switch (this.activeTab) {
      case 'analytics':
        return this.renderAnalytics();
      case 'webhook':
        return this.renderWebhook();
      default:
        return this.renderSettings();
    }
  }

  protected renderTabButton(tab: Tab, icon: string): Mithril.Vnode<any, any> {
    return (
      <li>
        <button type="button" className={classList('LinkClicksPage-tab', { active: this.activeTab === tab })} onclick={() => this.setTab(tab)}>
          <i className={icon} />
          <span>{app.translator.trans(`datlechin-link-clicks.admin.tabs.${tab}`)}</span>
        </button>
      </li>
    );
  }

  protected renderSettings(): Mithril.Vnode<any, any> {
    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <Form>
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.enabled',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.enabled_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.enabled_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.track_internal',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.track_internal_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.track_internal_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.track_tag_mentions',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.track_tag_mentions_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.track_tag_mentions_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.track_user_mentions',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.track_user_mentions_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.track_user_mentions_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.track_post_mentions',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.track_post_mentions_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.track_post_mentions_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.trending_enabled',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.trending_enabled_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.trending_enabled_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.trending_min_clicks',
              type: 'number',
              min: 0,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.trending_min_clicks_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.trending_min_clicks_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.open_in_new_window',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.open_new_window_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.open_new_window_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.honor_dnt',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.honor_dnt_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.honor_dnt_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.skip_guests',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.skip_guests_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.skip_guests_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.min_display_count',
              type: 'number',
              min: 1,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.min_display_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.min_display_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.dedup_window_hours',
              type: 'number',
              min: 1,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.dedup_window_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.dedup_window_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.event_retention_days',
              type: 'number',
              min: 1,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.retention_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.retention_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.bot_user_agents',
              type: 'textarea',
              rows: 5,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.bot_ua_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.bot_ua_help'),
              placeholder: app.translator.trans('datlechin-link-clicks.admin.settings.bot_ua_placeholder'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.tracking_params_strip',
              type: 'textarea',
              rows: 4,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.tracking_params_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.tracking_params_help'),
              placeholder: app.translator.trans('datlechin-link-clicks.admin.settings.tracking_params_placeholder'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.attachment_path_prefixes',
              type: 'textarea',
              rows: 3,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.attachment_prefixes_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.attachment_prefixes_help'),
              placeholder: '/assets/files/\n/uploads/',
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.domain_blocklist',
              type: 'textarea',
              rows: 4,
              label: app.translator.trans('datlechin-link-clicks.admin.settings.domain_blocklist_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.domain_blocklist_help'),
              placeholder: 'spammer.example\n*.tracking.example',
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.confirm_external_clicks',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.confirm_external_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.confirm_external_help'),
            })}
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.digest_enabled',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.digest_enabled_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.digest_enabled_help'),
            })}
            <div className="Form-group Form-controls">{this.submitButton()}</div>
          </Form>
        </div>
      </div>
    );
  }

  protected renderWebhook(): Mithril.Vnode<any, any> {
    const v = this.setting('datlechin-link-clicks.webhook_enabled')();
    const enabled = v === true || v === 1 || v === '1';

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <Form>
            {this.buildSettingComponent({
              setting: 'datlechin-link-clicks.webhook_enabled',
              type: 'boolean',
              label: app.translator.trans('datlechin-link-clicks.admin.settings.webhook_enabled_label'),
              help: app.translator.trans('datlechin-link-clicks.admin.settings.webhook_enabled_help'),
            })}
            {enabled && [
              this.buildSettingComponent({
                setting: 'datlechin-link-clicks.webhook_url',
                type: 'text',
                label: app.translator.trans('datlechin-link-clicks.admin.settings.webhook_url_label'),
                help: app.translator.trans('datlechin-link-clicks.admin.settings.webhook_url_help'),
                placeholder: 'https://example.com/hook',
              }),
              this.buildSettingComponent({
                setting: 'datlechin-link-clicks.webhook_secret',
                type: 'text',
                label: app.translator.trans('datlechin-link-clicks.admin.settings.webhook_secret_label'),
                help: app.translator.trans('datlechin-link-clicks.admin.settings.webhook_secret_help'),
              }),
            ]}
            <div className="Form-group Form-controls">
              {this.submitButton()}
              {enabled && (
                <Button
                  className="Button"
                  icon="fas fa-paper-plane"
                  loading={(this as any).webhookTestLoading as boolean | undefined}
                  onclick={() => this.sendTestPing()}
                >
                  {app.translator.trans('datlechin-link-clicks.admin.settings.webhook_test_button')}
                </Button>
              )}
            </div>
          </Form>
        </div>
      </div>
    );
  }

  protected async sendTestPing(): Promise<void> {
    const self = this as any;
    self.webhookTestLoading = true;
    m.redraw();

    try {
      const res = await app.request<{ ok: boolean; status?: number; body?: string; error?: string }>({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats/webhook/test`,
      });

      if (res.ok) {
        app.alerts.show(
          { type: 'success' },
          app.translator.trans('datlechin-link-clicks.admin.settings.webhook_test_ok', { status: res.status ?? 0 })
        );
      } else {
        const detail = res.error ?? `HTTP ${res.status ?? 0}`;
        app.alerts.show({ type: 'error' }, app.translator.trans('datlechin-link-clicks.admin.settings.webhook_test_failed', { detail }));
      }
    } catch (e) {
      app.alerts.show({ type: 'error' }, (e as Error).message);
    } finally {
      self.webhookTestLoading = false;
      m.redraw();
    }
  }

  protected renderAnalytics(): Mithril.Vnode<any, any> {
    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <LinkClicksAnalytics />
        </div>
      </div>
    );
  }
}
