import { extend } from 'flarum/common/extend';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import PopularLinksWidget from './components/PopularLinksWidget';

export default function addPopularLinksWidget() {
  // `this.discussion` is protected on DiscussionPage; runtime access
  // through the prototype extension still works, but TS rejects it
  // through the public type. Loosen the `this` type to allow the read.
  extend(DiscussionPage.prototype, 'sidebarItems', function (this: any, items: any) {
    if (!this.discussion) {
      return;
    }

    items.add('popularLinks', <PopularLinksWidget discussionId={this.discussion.id()} />, 50);
  });
}
