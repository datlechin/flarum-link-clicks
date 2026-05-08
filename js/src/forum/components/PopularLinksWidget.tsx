import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type Mithril from 'mithril';

interface PopularLink {
  id: number;
  track_url: string;
  display: string;
  title: string;
  count: number;
}

interface PopularLinksWidgetAttrs {
  discussionId: string;
}

export default class PopularLinksWidget extends Component<PopularLinksWidgetAttrs> {
  protected loading = true;
  protected links: PopularLink[] = [];

  oninit(vnode: Mithril.Vnode<PopularLinksWidgetAttrs, this>) {
    super.oninit(vnode);
    this.load();
  }

  protected async load(): Promise<void> {
    try {
      this.links = await app.request<PopularLink[]>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/discussions/${this.attrs.discussionId}/popular-links`,
      });
    } catch {
      // 404 (invisible discussion) or transport error: hide silently.
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
      <div className="LinkClicks-popular">
        <h4 className="LinkClicks-popular-title">{app.translator.trans('datlechin-link-clicks.forum.popular_links_title')}</h4>
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
