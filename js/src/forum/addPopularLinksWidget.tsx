import { extend } from 'flarum/common/extend';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import PopularLinksWidget from './components/PopularLinksWidget';

export default function addPopularLinksWidget() {
  extend(DiscussionPage.prototype, 'sidebarItems', function (items) {
    if (!this.discussion) {
      return;
    }

    items.add('popularLinks', <PopularLinksWidget discussionId={this.discussion.id()} />, 50);
  });
}
