import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type Mithril from 'mithril';

interface TrendingHashtag {
  id: number;
  label: string;
  url: string;
  count: number;
  velocity: number;
}

export default class TrendingHashtagsWidget extends Component {
  protected loading = true;
  protected hashtags: TrendingHashtag[] = [];

  oninit(vnode: Mithril.Vnode<{}, this>) {
    super.oninit(vnode);
    this.load();
  }

  protected async load(): Promise<void> {
    try {
      this.hashtags = await app.request<TrendingHashtag[]>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/trending-hashtags`,
      });
    } catch {
      // Nothing rolled up yet, or a transport error: hide silently.
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  view(): Mithril.Children {
    if (this.loading || !this.hashtags.length) {
      return null;
    }

    return (
      <div className="LinkClicks-trending">
        <h4 className="LinkClicks-trending-title">{app.translator.trans('datlechin-link-clicks.forum.trending_title')}</h4>
        <ul className="LinkClicks-trending-list">
          {this.hashtags.map((hashtag) => (
            <li key={hashtag.id}>
              <a href={hashtag.url} className="LinkClicks-trending-item">
                <span className="LinkClicks-trending-label">{hashtag.label}</span>
                <span className="LinkClicks-trending-count">{hashtag.count}</span>
              </a>
            </li>
          ))}
        </ul>
      </div>
    );
  }
}
