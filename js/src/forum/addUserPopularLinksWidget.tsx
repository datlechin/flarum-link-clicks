import { extend } from 'flarum/common/extend';
import UserPage from 'flarum/forum/components/UserPage';
import UserPopularLinksWidget from './components/UserPopularLinksWidget';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function addUserPopularLinksWidget(): void {
  extend(UserPage.prototype, 'sidebarItems', function (this: UserPage, items: ItemList<Mithril.Children>) {
    if (!this.user) return;
    items.add('linkClicksPopular', <UserPopularLinksWidget userId={String(this.user.id())} />, -10);
  });
}
