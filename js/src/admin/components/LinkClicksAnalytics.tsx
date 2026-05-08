import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Pagination from 'flarum/common/components/Pagination';
import Stream from 'flarum/common/utils/Stream';
import extractText from 'flarum/common/utils/extractText';
import dayjs from 'dayjs';
import type Mithril from 'mithril';
import LinkClickersModal from './LinkClickersModal';

function typeIcon(row: { is_attachment: boolean; is_internal: boolean }): { className: string; titleKey: string } {
  if (row.is_attachment) return { className: 'fas fa-paperclip', titleKey: 'datlechin-link-clicks.admin.analytics.attachment_label' };
  if (row.is_internal) return { className: 'fas fa-link', titleKey: 'datlechin-link-clicks.admin.analytics.internal_label' };
  return { className: 'fas fa-arrow-up-right-from-square', titleKey: 'datlechin-link-clicks.admin.analytics.external_label' };
}

interface StatRow {
  url: string;
  url_hash: string;
  is_internal: boolean;
  is_attachment: boolean;
  total_clicks: number;
  unique_users: number;
  first_clicked: string | null;
  last_clicked: string | null;
}

interface StatsResponse {
  rows: StatRow[];
  total: number;
  offset: number;
  limit: number;
}

const PAGE_SIZE = 25;

export default class LinkClicksAnalytics extends Component {
  protected loading = true;
  protected exporting = false;
  protected rows: StatRow[] = [];
  protected total = 0;
  protected pageNumber = 0;

  protected since!: Stream<string>;
  protected until!: Stream<string>;
  protected scope!: Stream<string>;
  protected tagSlug!: Stream<string>;
  protected sort!: Stream<string>;

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);

    this.since = Stream(dayjs().subtract(30, 'day').format('YYYY-MM-DD'));
    this.until = Stream(dayjs().format('YYYY-MM-DD'));
    this.scope = Stream('all');
    this.tagSlug = Stream('');
    this.sort = Stream('-total_clicks');

    this.load();
  }

  protected async load(): Promise<void> {
    this.loading = true;
    m.redraw();

    const params: Record<string, string | number> = {
      ...this.filterParams(),
      sort: this.sort(),
      'page[offset]': this.pageNumber * PAGE_SIZE,
      'page[limit]': PAGE_SIZE,
    };

    try {
      const res = await app.request<StatsResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats`,
        params,
      });
      this.rows = res.rows;
      this.total = res.total;
    } catch {
      this.rows = [];
      this.total = 0;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  protected filterParams(): Record<string, string> {
    let since = this.since();
    let until = this.until();
    if (since && until && since > until) {
      [since, until] = [until, since];
    }

    const params: Record<string, string> = {
      'filter[since]': since,
      'filter[until]': until,
    };
    switch (this.scope()) {
      case 'external':
        params['filter[is_internal]'] = '0';
        break;
      case 'internal':
        params['filter[is_internal]'] = '1';
        params['filter[is_attachment]'] = '0';
        break;
      case 'attachments':
        params['filter[is_attachment]'] = '1';
        break;
    }
    if (this.tagSlug()) {
      params['filter[tag]'] = this.tagSlug();
    }
    return params;
  }

  protected async exportCsv(): Promise<void> {
    this.exporting = true;
    m.redraw();

    try {
      const qs = new URLSearchParams(this.filterParams()).toString();
      const url = `${app.forum.attribute('apiUrl')}/link-click-stats/export?${qs}`;

      const response = await fetch(url, { credentials: 'include' });
      if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw new Error(body.error ?? `Export failed (HTTP ${response.status}).`);
      }

      const blob = await response.blob();
      const objectUrl = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = objectUrl;
      a.download = `link-clicks-${dayjs().format('YYYY-MM-DD')}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(objectUrl);
    } catch (e) {
      app.alerts.show({ type: 'error' }, (e as Error).message);
    } finally {
      this.exporting = false;
      m.redraw();
    }
  }

  view(): Mithril.Children {
    return (
      <div className="LinkClicksAnalytics">
        <div className="LinkClicksAnalytics-filters">
          <label>
            {app.translator.trans('datlechin-link-clicks.admin.analytics.since')}
            <input
              type="date"
              className="FormControl"
              value={this.since()}
              onchange={(e: Event) => {
                this.since((e.target as HTMLInputElement).value);
                this.pageNumber = 0;
                this.load();
              }}
            />
          </label>
          <label>
            {app.translator.trans('datlechin-link-clicks.admin.analytics.until')}
            <input
              type="date"
              className="FormControl"
              value={this.until()}
              onchange={(e: Event) => {
                this.until((e.target as HTMLInputElement).value);
                this.pageNumber = 0;
                this.load();
              }}
            />
          </label>
          <label>
            {app.translator.trans('datlechin-link-clicks.admin.analytics.scope')}
            <select
              className="FormControl"
              value={this.scope()}
              onchange={(e: Event) => {
                this.scope((e.target as HTMLSelectElement).value);
                this.pageNumber = 0;
                this.load();
              }}
            >
              <option value="all">{app.translator.trans('datlechin-link-clicks.admin.analytics.scope_all')}</option>
              <option value="external">{app.translator.trans('datlechin-link-clicks.admin.analytics.scope_external')}</option>
              <option value="internal">{app.translator.trans('datlechin-link-clicks.admin.analytics.scope_internal')}</option>
              <option value="attachments">{app.translator.trans('datlechin-link-clicks.admin.analytics.scope_attachments')}</option>
            </select>
          </label>
          <label>
            {app.translator.trans('datlechin-link-clicks.admin.analytics.tag')}
            <input
              type="text"
              className="FormControl"
              placeholder={extractText(app.translator.trans('datlechin-link-clicks.admin.analytics.tag_placeholder'))}
              value={this.tagSlug()}
              oninput={(e: InputEvent) => {
                this.tagSlug((e.target as HTMLInputElement).value);
              }}
              onchange={() => {
                this.pageNumber = 0;
                this.load();
              }}
            />
          </label>
          <Button
            className="Button LinkClicksAnalytics-export"
            icon="fas fa-file-csv"
            loading={this.exporting}
            disabled={this.loading || this.rows.length === 0}
            onclick={() => this.exportCsv()}
          >
            {app.translator.trans('datlechin-link-clicks.admin.analytics.export_csv')}
          </Button>
        </div>

        {this.loading && this.rows.length === 0 ? (
          <LoadingIndicator />
        ) : this.rows.length === 0 ? (
          <div className="LinkClicksAnalytics-empty">
            <i className="far fa-folder-open LinkClicksAnalytics-emptyIcon" />
            <p>{app.translator.trans('datlechin-link-clicks.admin.analytics.empty')}</p>
          </div>
        ) : (
          <table className="LinkClicksAnalytics-table">
            <thead>
              <tr>
                <th>{app.translator.trans('datlechin-link-clicks.admin.analytics.column_url')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.analytics.column_total')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.analytics.column_unique')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.analytics.column_first')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.analytics.column_last')}</th>
              </tr>
            </thead>
            <tbody>
              {this.rows.map((row) => {
                const t = typeIcon(row);
                return (
                  <tr>
                    <td className="LinkClicksAnalytics-urlCell">
                      <i className={`${t.className} LinkClicksAnalytics-typeIcon`} title={extractText(app.translator.trans(t.titleKey))} />
                      <a href={row.url} target="_blank" rel="noopener noreferrer" title={row.url}>
                        {row.url}
                      </a>
                    </td>
                    <td className="LinkClicksAnalytics-num">{row.total_clicks}</td>
                    <td className="LinkClicksAnalytics-num">
                      <button
                        type="button"
                        className="LinkClicksAnalytics-drilldown"
                        onclick={() => app.modal.show(LinkClickersModal, { urlHash: row.url_hash, url: row.url })}
                        title={extractText(app.translator.trans('datlechin-link-clicks.admin.analytics.drilldown_tooltip'))}
                      >
                        {row.unique_users}
                      </button>
                    </td>
                    <td>{row.first_clicked ? dayjs(row.first_clicked).format('YYYY-MM-DD') : '—'}</td>
                    <td>{row.last_clicked ? dayjs(row.last_clicked).format('YYYY-MM-DD') : '—'}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}

        {this.total > PAGE_SIZE && (
          <Pagination
            currentPage={this.pageNumber + 1}
            total={this.total}
            perPage={PAGE_SIZE}
            onChange={(page: number) => {
              this.pageNumber = page - 1;
              this.load();
            }}
          />
        )}
      </div>
    );
  }
}
