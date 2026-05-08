import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import PostControls from 'flarum/forum/utils/PostControls';
import type Post from 'flarum/common/models/Post';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

const saving = new Set<number>();

export default function extendPostControls(): void {
  extend(PostControls, 'moderationControls', function (items: ItemList<Mithril.Children>, post: Post) {
    if (post.contentType() !== 'comment' || !post.canEdit()) return;

    const disabled = !!post.attribute<boolean>('linkClicksDisabled');
    const postId = Number(post.id());

    items.add(
      'linkClicksToggle',
      <Button
        icon={disabled ? 'fas fa-link' : 'fas fa-link-slash'}
        loading={saving.has(postId)}
        onclick={() => {
          saving.add(postId);
          m.redraw();
          post.save({ linkClicksDisabled: !disabled }).then(
            () => {
              saving.delete(postId);
              m.redraw();
            },
            (err) => {
              saving.delete(postId);
              app.alerts.show({ type: 'error' }, app.translator.trans('datlechin-link-clicks.forum.post_controls.toggle_failed'));
              m.redraw();
              throw err;
            }
          );
        }}
      >
        {app.translator.trans(
          disabled ? 'datlechin-link-clicks.forum.post_controls.enable_tracking' : 'datlechin-link-clicks.forum.post_controls.disable_tracking'
        )}
      </Button>,
      -10
    );
  });
}
