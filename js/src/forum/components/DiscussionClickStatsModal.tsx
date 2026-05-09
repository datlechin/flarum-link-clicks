import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import dayjs from 'dayjs';
import type Mithril from 'mithril';

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

interface Attrs extends IInternalModalAttrs {
  discussionId: number;
  discussionTitle: string;
}

const PAGE_SIZE = 20;

export default class DiscussionClickStatsModal extends Modal<Attrs> {
  protected loading = true;
  protected rows: StatRow[] = [];
  protected total = 0;

  oninit(vnode: Mithril.Vnode<Attrs, this>): void {
    super.oninit(vnode);
    this.load();
  }

  className(): string {
    return 'DiscussionClickStatsModal Modal--large';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-link-clicks.forum.discussion_stats.title', { title: this.attrs.discussionTitle });
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        {this.loading ? (
          <LoadingIndicator />
        ) : this.rows.length === 0 ? (
          <div className="DiscussionClickStatsModal-empty">
            <i className="far fa-folder-open DiscussionClickStatsModal-emptyIcon" />
            <p>{app.translator.trans('datlechin-link-clicks.forum.discussion_stats.empty')}</p>
          </div>
        ) : (
          <table className="DiscussionClickStatsModal-table">
            <thead>
              <tr>
                <th>{app.translator.trans('datlechin-link-clicks.forum.discussion_stats.column_url')}</th>
                <th className="DiscussionClickStatsModal-num">{app.translator.trans('datlechin-link-clicks.forum.discussion_stats.column_total')}</th>
                <th className="DiscussionClickStatsModal-num">{app.translator.trans('datlechin-link-clicks.forum.discussion_stats.column_unique')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.forum.discussion_stats.column_last')}</th>
              </tr>
            </thead>
            <tbody>
              {this.rows.map((row) => (
                <tr>
                  <td className="DiscussionClickStatsModal-urlCell">
                    <a href={row.url} target="_blank" rel="noopener noreferrer" title={row.url}>
                      {row.url}
                    </a>
                  </td>
                  <td className="DiscussionClickStatsModal-num">{row.total_clicks}</td>
                  <td className="DiscussionClickStatsModal-num">{row.unique_users}</td>
                  <td>{row.last_clicked ? dayjs(row.last_clicked).format('YYYY-MM-DD HH:mm') : '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    );
  }

  protected async load(): Promise<void> {
    this.loading = true;
    m.redraw();
    try {
      const res = await app.request<StatsResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats`,
        params: {
          'filter[discussion_id]': this.attrs.discussionId,
          sort: '-total_clicks',
          'page[limit]': PAGE_SIZE,
        },
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
}
