import Component from 'flarum/common/Component';
import type Mithril from 'mithril';
interface Point {
    date: string;
    count: number;
}
interface SeriesResponse {
    days: number;
    max: number;
    points: Point[];
}
export default class LinkClicksTimeSeries extends Component {
    protected loading: boolean;
    protected days: number;
    protected data: SeriesResponse | null;
    oninit(vnode: Mithril.Vnode): void;
    protected load(): Promise<void>;
    view(): Mithril.Children;
    protected renderChart(data: SeriesResponse): Mithril.Children;
}
export {};
