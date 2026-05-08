import { extend } from 'flarum/common/extend';
import UserPage from 'flarum/forum/components/UserPage';
import UserPopularLinksWidget from './components/UserPopularLinksWidget';

export default function addUserPopularLinksWidget(): void {
  extend(UserPage.prototype, 'sidebarItems', function (this: any, items: any) {
    if (!this.user) return;
    items.add('linkClicksPopular', <UserPopularLinksWidget userId={String(this.user.id())} />, -10);
  });
}
