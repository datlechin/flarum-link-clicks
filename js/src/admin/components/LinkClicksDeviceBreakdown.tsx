import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import dayjs from 'dayjs';
import type Mithril from 'mithril';

interface BreakdownResponse {
  total: number;
  devices: Record<string, number>;
  browsers: Record<string, number>;
  matrix: { device_class: string; browser_family: string; total_clicks: number }[];
}

const DEVICE_ICONS: Record<string, string> = {
  mobile: 'fas fa-mobile-screen',
  tablet: 'fas fa-tablet-screen-button',
  desktop: 'fas fa-desktop',
};

const BROWSER_ICONS: Record<string, string> = {
  chrome: 'fab fa-chrome',
  firefox: 'fab fa-firefox-browser',
  safari: 'fab fa-safari',
  edge: 'fab fa-edge',
  opera: 'fab fa-opera',
  samsung: 'fas fa-globe',
  other: 'fas fa-globe',
};

export default class LinkClicksDeviceBreakdown extends Component {
  protected loading = true;
  protected data: BreakdownResponse | null = null;

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
    this.load();
  }

  protected async load(): Promise<void> {
    try {
      const since = dayjs().subtract(30, 'day').format('YYYY-MM-DD');
      const until = dayjs().format('YYYY-MM-DD');
      this.data = await app.request<BreakdownResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats/devices`,
        params: { 'filter[since]': since, 'filter[until]': until },
      });
    } catch {
      this.data = null;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  view(): Mithril.Children {
    if (this.loading) {
      return (
        <div className="LinkClicksDeviceBreakdown">
          <LoadingIndicator />
        </div>
      );
    }
    if (!this.data || this.data.total === 0) {
      return null;
    }

    return (
      <div className="LinkClicksDeviceBreakdown">
        <h4 className="LinkClicksDeviceBreakdown-title">{app.translator.trans('datlechin-link-clicks.admin.devices.title')}</h4>
        <div className="LinkClicksDeviceBreakdown-grid">
          {this.renderColumn('devices', this.data.devices, DEVICE_ICONS, this.data.total)}
          {this.renderColumn('browsers', this.data.browsers, BROWSER_ICONS, this.data.total)}
        </div>
      </div>
    );
  }

  protected renderColumn(
    group: 'devices' | 'browsers',
    counts: Record<string, number>,
    icons: Record<string, string>,
    total: number
  ): Mithril.Children {
    const entries = Object.entries(counts).sort((a, b) => b[1] - a[1]);
    const max = Math.max(...entries.map(([, c]) => c), 1);

    return (
      <div className="LinkClicksDeviceBreakdown-col">
        <h5 className="LinkClicksDeviceBreakdown-colTitle">{app.translator.trans(`datlechin-link-clicks.admin.devices.${group}_label`)}</h5>
        <ul className="LinkClicksDeviceBreakdown-list">
          {entries.map(([key, count]) => {
            const pct = total > 0 ? Math.round((count / total) * 100) : 0;
            const barWidth = (count / max) * 100;
            return (
              <li className="LinkClicksDeviceBreakdown-row">
                <span className="LinkClicksDeviceBreakdown-label">
                  <i className={`${icons[key] ?? 'fas fa-globe'} LinkClicksDeviceBreakdown-icon`} aria-hidden="true" />
                  {app.translator.trans(`datlechin-link-clicks.admin.devices.${group}.${key}`)}
                </span>
                <span className="LinkClicksDeviceBreakdown-barTrack">
                  <span className="LinkClicksDeviceBreakdown-bar" style={{ width: `${barWidth}%` }} />
                </span>
                <span className="LinkClicksDeviceBreakdown-count">
                  {count.toLocaleString()} <span className="LinkClicksDeviceBreakdown-pct">({pct}%)</span>
                </span>
              </li>
            );
          })}
        </ul>
      </div>
    );
  }
}
