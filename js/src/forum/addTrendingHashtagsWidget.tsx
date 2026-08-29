import { extend } from 'flarum/common/extend';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import TrendingHashtagsWidget from './components/TrendingHashtagsWidget';

/**
 * The index sidebar, below the nav, the widget is a way in to what people are
 * reading right now, so it belongs next to the other ways of getting around,
 * not above the "New discussion" button.
 */
export default function addTrendingHashtagsWidget(): void {
  extend(IndexSidebar.prototype, 'items', function (items: ItemList<Mithril.Children>) {
    items.add('linkClicksTrending', <TrendingHashtagsWidget />, -10);
  });
}
