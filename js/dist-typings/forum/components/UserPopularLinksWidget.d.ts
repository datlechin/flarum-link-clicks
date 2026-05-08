import Component from 'flarum/common/Component';
import type Mithril from 'mithril';
interface UserPopularLink {
    id: number;
    track_url: string;
    display: string;
    title: string;
    count: number;
}
interface UserPopularLinksWidgetAttrs {
    userId: string;
}
export default class UserPopularLinksWidget extends Component<UserPopularLinksWidgetAttrs> {
    protected loading: boolean;
    protected links: UserPopularLink[];
    oninit(vnode: Mithril.Vnode<UserPopularLinksWidgetAttrs, this>): void;
    protected load(): Promise<void>;
    view(): Mithril.Children;
}
export {};
