import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import dayjs from 'dayjs';
import type Mithril from 'mithril';

interface DomainRow {
  domain: string;
  url_count: number;
  total_clicks: number;
  unique_users: number;
  first_clicked: string | null;
  last_clicked: string | null;
}

interface DomainsResponse {
  rows: DomainRow[];
  total: number;
  offset: number;
  limit: number;
}

export default class LinkClicksDomains extends Component {
  protected loading = true;
  protected rows: DomainRow[] = [];

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
    this.load();
  }

  protected async load(): Promise<void> {
    try {
      const since = dayjs().subtract(30, 'day').format('YYYY-MM-DD');
      const until = dayjs().format('YYYY-MM-DD');
      const res = await app.request<DomainsResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats/domains`,
        params: {
          'filter[since]': since,
          'filter[until]': until,
          'page[limit]': 10,
        },
      });
      this.rows = res.rows;
    } catch {
      this.rows = [];
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  view(): Mithril.Children {
    if (this.loading) {
      return (
        <div className="LinkClicksDomains">
          <LoadingIndicator />
        </div>
      );
    }
    if (!this.rows.length) {
      return null;
    }

    return (
      <div className="LinkClicksDomains">
        <h4 className="LinkClicksDomains-title">{app.translator.trans('datlechin-link-clicks.admin.domains.title')}</h4>
        <table className="LinkClicksAnalytics-table">
          <thead>
            <tr>
              <th>{app.translator.trans('datlechin-link-clicks.admin.domains.column_domain')}</th>
              <th className="LinkClicksAnalytics-num">{app.translator.trans('datlechin-link-clicks.admin.domains.column_urls')}</th>
              <th className="LinkClicksAnalytics-num">{app.translator.trans('datlechin-link-clicks.admin.analytics.column_total')}</th>
              <th className="LinkClicksAnalytics-num">{app.translator.trans('datlechin-link-clicks.admin.analytics.column_unique')}</th>
            </tr>
          </thead>
          <tbody>
            {this.rows.map((row) => (
              <tr>
                <td>{row.domain}</td>
                <td className="LinkClicksAnalytics-num">{row.url_count}</td>
                <td className="LinkClicksAnalytics-num">{row.total_clicks}</td>
                <td className="LinkClicksAnalytics-num">{row.unique_users}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }
}
