import app from 'flarum/forum/app';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';
import applyCountedClick, { type CountedClick } from './applyCountedClick';

export default function extendRealtime(): void {
  new RealtimeExtend()
    .onBothChannelsEvent('linkClickCounted', (raw: unknown) => {
      applyCountedClick(raw as CountedClick, app.forum.attribute<number>('linkClicksMinDisplay'));
    })
    .extend(app, { name: 'datlechin-link-clicks', exports: {} });
}
