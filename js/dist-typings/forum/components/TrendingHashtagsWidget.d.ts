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
    protected loading: boolean;
    protected hashtags: TrendingHashtag[];
    oninit(vnode: Mithril.Vnode<{}, this>): void;
    protected load(): Promise<void>;
    view(): Mithril.Children;
}
export {};
