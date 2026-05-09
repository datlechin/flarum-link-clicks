import app from 'flarum/admin/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Pagination from 'flarum/common/components/Pagination';
import dayjs from 'dayjs';
import type Mithril from 'mithril';

interface TrailRow {
  url: string;
  discussion_id: number;
  post_id: number;
  is_internal: boolean;
  is_attachment: boolean;
  clicked_at: string;
}

interface TrailResponse {
  user: { id: number; username: string; displayName: string; avatarUrl: string | null };
  rows: TrailRow[];
  total: number;
  offset: number;
  limit: number;
}

interface Attrs extends IInternalModalAttrs {
  userId: number;
  username: string;
  displayName: string;
}

const PAGE_SIZE = 25;

function typeIcon(row: TrailRow): string {
  if (row.is_attachment) return 'fas fa-paperclip';
  if (row.is_internal) return 'fas fa-link';
  return 'fas fa-arrow-up-right-from-square';
}

export default class UserClickTrailModal extends Modal<Attrs> {
  protected loading = true;
  protected rows: TrailRow[] = [];
  protected total = 0;
  protected pageNumber = 0;

  oninit(vnode: Mithril.Vnode<Attrs, this>): void {
    super.oninit(vnode);
    this.load();
  }

  className(): string {
    return 'UserClickTrailModal Modal--large';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-link-clicks.admin.click_trail.title', {
      name: this.attrs.displayName,
    });
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        {this.loading && this.rows.length === 0 ? (
          <LoadingIndicator />
        ) : this.rows.length === 0 ? (
          <div className="LinkClickersModal-empty">
            <i className="far fa-folder-open LinkClickersModal-emptyIcon" />
            <p>{app.translator.trans('datlechin-link-clicks.admin.click_trail.empty')}</p>
          </div>
        ) : (
          <table className="LinkClickersModal-table">
            <colgroup>
              <col />
              <col style="width: 180px" />
            </colgroup>
            <thead>
              <tr>
                <th>{app.translator.trans('datlechin-link-clicks.admin.click_trail.column_link')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.click_trail.column_when')}</th>
              </tr>
            </thead>
            <tbody>
              {this.rows.map((row) => (
                <tr>
                  <td className="LinkClicksAnalytics-urlCell">
                    <i className={`${typeIcon(row)} LinkClicksAnalytics-typeIcon`} />
                    <a href={row.url} target="_blank" rel="noopener noreferrer" title={row.url}>
                      {row.url}
                    </a>
                  </td>
                  <td title={row.clicked_at}>{dayjs(row.clicked_at).format('YYYY-MM-DD HH:mm')}</td>
                </tr>
              ))}
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

  protected async load(): Promise<void> {
    this.loading = true;
    m.redraw();

    try {
      const res = await app.request<TrailResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/users/${this.attrs.userId}/click-trail`,
        params: {
          'page[offset]': this.pageNumber * PAGE_SIZE,
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
