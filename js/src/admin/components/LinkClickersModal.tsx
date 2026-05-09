import app from 'flarum/admin/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Pagination from 'flarum/common/components/Pagination';
import dayjs from 'dayjs';
import type Mithril from 'mithril';
import UserClickTrailModal from './UserClickTrailModal';

interface ClickerRow {
  user: { id: number; username: string; displayName: string; avatarUrl: string | null } | null;
  ip_address: string | null;
  anonymized: boolean;
  click_count: number;
  first_click: string;
  last_click: string;
}

interface ClickersResponse {
  rows: ClickerRow[];
  total: number;
  offset: number;
  limit: number;
}

interface Attrs extends IInternalModalAttrs {
  urlHash: string;
  url: string;
}

const PAGE_SIZE = 25;

export default class LinkClickersModal extends Modal<Attrs> {
  protected loading = true;
  protected rows: ClickerRow[] = [];
  protected total = 0;
  protected pageNumber = 0;

  oninit(vnode: Mithril.Vnode<Attrs, this>): void {
    super.oninit(vnode);
    this.load();
  }

  className(): string {
    return 'LinkClickersModal Modal--large';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-link-clicks.admin.clickers.title');
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        <a className="LinkClickersModal-url" href={this.attrs.url} target="_blank" rel="noopener noreferrer" title={this.attrs.url}>
          <i className="fas fa-arrow-up-right-from-square" /> {this.attrs.url}
        </a>

        {this.loading && this.rows.length === 0 ? (
          <LoadingIndicator />
        ) : this.rows.length === 0 ? (
          <div className="LinkClickersModal-empty">
            <i className="far fa-folder-open LinkClickersModal-emptyIcon" />
            <p>{app.translator.trans('datlechin-link-clicks.admin.clickers.empty')}</p>
          </div>
        ) : (
          <table className="LinkClickersModal-table">
            <colgroup>
              <col />
              <col style="width: 80px" />
              <col style="width: 140px" />
              <col style="width: 140px" />
            </colgroup>
            <thead>
              <tr>
                <th>{app.translator.trans('datlechin-link-clicks.admin.clickers.column_who')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.clickers.column_clicks')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.clickers.column_first')}</th>
                <th>{app.translator.trans('datlechin-link-clicks.admin.clickers.column_last')}</th>
              </tr>
            </thead>
            <tbody>{this.rows.map((row) => this.renderRow(row))}</tbody>
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

  protected renderRow(row: ClickerRow): Mithril.Children {
    let who: Mithril.Children;
    if (row.user) {
      const u = row.user;
      who = (
        <button
          type="button"
          className="LinkClickersModal-userBtn"
          onclick={() => app.modal.show(UserClickTrailModal, { userId: u.id, username: u.username, displayName: u.displayName })}
          title={app.translator.trans('datlechin-link-clicks.admin.clickers.view_trail_tooltip').toString()}
        >
          {u.avatarUrl ? (
            <img className="Avatar LinkClickersModal-avatar" src={u.avatarUrl} alt="" />
          ) : (
            <span className="Avatar LinkClickersModal-avatar LinkClickersModal-avatarFallback">{u.displayName.charAt(0).toUpperCase()}</span>
          )}
          <span>{u.displayName}</span>
        </button>
      );
    } else if (row.anonymized) {
      who = (
        <span className="LinkClickersModal-anon">
          <i className="fas fa-user-slash" /> <em>{app.translator.trans('datlechin-link-clicks.admin.clickers.anonymized')}</em>
        </span>
      );
    } else {
      who = (
        <span className="LinkClickersModal-guest">
          <i className="fas fa-user" /> {app.translator.trans('datlechin-link-clicks.admin.clickers.guest')}
          <code>{row.ip_address}</code>
        </span>
      );
    }

    return (
      <tr>
        <td>{who}</td>
        <td className="LinkClickersModal-count">{row.click_count}</td>
        <td title={row.first_click}>{dayjs(row.first_click).format('YYYY-MM-DD HH:mm')}</td>
        <td title={row.last_click}>{dayjs(row.last_click).format('YYYY-MM-DD HH:mm')}</td>
      </tr>
    );
  }

  protected async load(): Promise<void> {
    this.loading = true;
    m.redraw();

    try {
      const res = await app.request<ClickersResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats/${this.attrs.urlHash}/clickers`,
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
