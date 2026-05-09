import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import type Discussion from 'flarum/common/models/Discussion';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import DiscussionClickStatsModal from './components/DiscussionClickStatsModal';

export default function extendDiscussionControls(): void {
  extend(DiscussionControls, 'moderationControls', function (items: ItemList<Mithril.Children>, discussion: Discussion) {
    if (!app.forum.attribute<boolean>('canViewLinkClickAnalytics')) return;

    items.add(
      'datlechin-link-clicks.stats',
      <Button
        icon="fas fa-chart-line"
        onclick={() =>
          app.modal.show(DiscussionClickStatsModal, {
            discussionId: Number(discussion.id()),
            discussionTitle: discussion.title(),
          })
        }
      >
        {app.translator.trans('datlechin-link-clicks.forum.discussion_controls.click_stats')}
      </Button>,
      -5
    );
  });
}
