import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type Mithril from 'mithril';

interface UserPopularLink {
  id: number;
  track_url: string;
  display: string;
  title: string;
  count: number;
}

interface UserPopularLinksWidgetAttrs {
  userId: string;
}

export default class UserPopularLinksWidget extends Component<UserPopularLinksWidgetAttrs> {
  protected loading = true;
  protected links: UserPopularLink[] = [];

  oninit(vnode: Mithril.Vnode<UserPopularLinksWidgetAttrs, this>) {
    super.oninit(vnode);
    this.load();
  }

  protected async load(): Promise<void> {
    try {
      this.links = await app.request<UserPopularLink[]>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/users/${this.attrs.userId}/popular-links`,
      });
    } catch {
      // hide silently on error
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  view(): Mithril.Children {
    if (this.loading || !this.links.length) {
      return null;
    }

    return (
      <div className="LinkClicks-popular UserPopularLinks">
        <h4 className="LinkClicks-popular-title">
          {app.translator.trans('datlechin-link-clicks.forum.user_popular_links_title')}
        </h4>
        <ul className="LinkClicks-popular-list">
          {this.links.map((link) => (
            <li key={link.id}>
              <a
                href={link.title}
                title={link.title}
                target="_blank"
                rel="noopener noreferrer"
                className="LinkClicks-popular-item"
                onclick={() => {
                  fetch(link.track_url, { keepalive: true }).catch(() => {});
                }}
              >
                <span className="LinkClicks-popular-host">{link.display}</span>
                <span className="LinkClicks-popular-count">{link.count}</span>
              </a>
            </li>
          ))}
        </ul>
      </div>
    );
  }
}
