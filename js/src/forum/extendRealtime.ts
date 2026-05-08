import app from 'flarum/forum/app';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';

interface LinkClickCountedPayload {
  post_id: number;
  url_id: number;
  clicks_count: number;
  title: string;
}

export default function extendRealtime(): void {
  new RealtimeExtend()
    .onBothChannelsEvent('linkClickCounted', (data: LinkClickCountedPayload) => {
      const minDisplay = app.forum.attribute<number>('linkClicksMinDisplay');
      const selector = `.LinkClicks-link[data-post-id="${data.post_id}"][data-url-id="${data.url_id}"]`;

      document.querySelectorAll<HTMLAnchorElement>(selector).forEach((el) => {
        if (data.clicks_count >= minDisplay) {
          el.setAttribute('data-clicks', String(data.clicks_count));

          if (!el.hasAttribute('data-custom-title')) {
            el.setAttribute('title', data.title);
            el.setAttribute('data-original-title', data.title);
          }
        } else {
          el.removeAttribute('data-clicks');
        }
      });
    })
    .extend(app, { name: 'datlechin-link-clicks', exports: {} });
}
