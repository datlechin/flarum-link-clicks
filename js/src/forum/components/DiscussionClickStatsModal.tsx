import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import humanTime from 'flarum/common/helpers/humanTime';
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

function typeIcon(row: StatRow): string {
  if (row.is_attachment) return 'fas fa-paperclip';
  if (row.is_internal) return 'fas fa-link';
  return 'fas fa-arrow-up-right-from-square';
}

/**
 * Best-effort URL parsing for the host/path split. Falls back to showing the
 * raw URL so we never crash on a tracker URL the browser dislikes.
 */
function splitUrl(raw: string): { host: string; tail: string } {
  try {
    const u = new URL(raw);
    const tail = u.pathname + u.search + u.hash;
    return { host: u.host, tail: tail === '/' ? '' : tail };
  } catch {
    return { host: raw, tail: '' };
  }
}

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
    if (this.loading) {
      return (
        <div className="Modal-body DiscussionClickStatsModal-body">
          <LoadingIndicator />
        </div>
      );
    }
    if (this.rows.length === 0) {
      return (
        <div className="Modal-body DiscussionClickStatsModal-body">
          <div className="DiscussionClickStatsModal-empty">
            <i className="far fa-folder-open DiscussionClickStatsModal-emptyIcon" />
            <p>{app.translator.trans('datlechin-link-clicks.forum.discussion_stats.empty')}</p>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body DiscussionClickStatsModal-body">
        <ul className="DiscussionClickStatsModal-list">
          {this.rows.map((row) => {
            const { host, tail } = splitUrl(row.url);
            return (
              <li className="DiscussionClickStatsModal-row">
                <i className={`${typeIcon(row)} DiscussionClickStatsModal-typeIcon`} aria-hidden="true" />
                <a className="DiscussionClickStatsModal-url" href={row.url} target="_blank" rel="noopener noreferrer" title={row.url}>
                  <span className="DiscussionClickStatsModal-host">{host}</span>
                  {tail && <span className="DiscussionClickStatsModal-path">{tail}</span>}
                </a>
                <span className="DiscussionClickStatsModal-stats">
                  <span
                    className="DiscussionClickStatsModal-stat"
                    title={app.translator.trans('datlechin-link-clicks.forum.discussion_stats.column_total').toString()}
                  >
                    <i className="fas fa-mouse-pointer" /> {row.total_clicks.toLocaleString()}
                  </span>
                  <span
                    className="DiscussionClickStatsModal-stat"
                    title={app.translator.trans('datlechin-link-clicks.forum.discussion_stats.column_unique').toString()}
                  >
                    <i className="fas fa-user" /> {row.unique_users.toLocaleString()}
                  </span>
                </span>
                <span className="DiscussionClickStatsModal-time">{row.last_clicked ? humanTime(new Date(row.last_clicked)) : ', '}</span>
              </li>
            );
          })}
        </ul>
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
