import Component from 'flarum/common/Component';
import type Mithril from 'mithril';
interface PopularLink {
    id: number;
    track_url: string;
    display: string;
    title: string;
    count: number;
}
interface PopularLinksWidgetAttrs {
    discussionId: string;
}
export default class PopularLinksWidget extends Component<PopularLinksWidgetAttrs> {
    protected loading: boolean;
    protected links: PopularLink[];
    oninit(vnode: Mithril.Vnode<PopularLinksWidgetAttrs, this>): void;
    protected load(): Promise<void>;
    view(): Mithril.Children;
}
export {};
