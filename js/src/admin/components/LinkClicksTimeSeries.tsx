import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import dayjs from 'dayjs';
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

const ALLOWED_DAYS = [30, 60, 90] as const;

export default class LinkClicksTimeSeries extends Component {
  protected loading = true;
  protected days = 30;
  protected data: SeriesResponse | null = null;

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
    this.load();
  }

  protected async load(): Promise<void> {
    this.loading = true;
    m.redraw();
    try {
      this.data = await app.request<SeriesResponse>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/link-click-stats/timeseries`,
        params: { days: this.days },
      });
    } catch {
      this.data = null;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  view(): Mithril.Children {
    return (
      <div className="LinkClicksTimeSeries">
        <div className="LinkClicksTimeSeries-header">
          <h4 className="LinkClicksTimeSeries-title">{app.translator.trans('datlechin-link-clicks.admin.timeseries.title')}</h4>
          <div className="LinkClicksTimeSeries-rangeButtons">
            {ALLOWED_DAYS.map((d) => (
              <button
                type="button"
                className={`Button Button--text${this.days === d ? ' active' : ''}`}
                onclick={() => {
                  this.days = d;
                  this.load();
                }}
              >
                {d}d
              </button>
            ))}
          </div>
        </div>

        {this.loading ? <LoadingIndicator /> : this.data && this.data.max > 0 ? this.renderChart(this.data) : null}
      </div>
    );
  }

  protected renderChart(data: SeriesResponse): Mithril.Children {
    const w = 800;
    const h = 160;
    const pad = 24;
    const stepX = (w - pad * 2) / Math.max(1, data.points.length - 1);
    const scaleY = (h - pad * 2) / Math.max(1, data.max);

    const linePoints = data.points.map((p, i) => `${pad + i * stepX},${h - pad - p.count * scaleY}`).join(' ');

    const areaPoints = `${pad},${h - pad} ${linePoints} ${pad + (data.points.length - 1) * stepX},${h - pad}`;

    const total = data.points.reduce((sum, p) => sum + p.count, 0);
    const avg = Math.round(total / data.points.length);

    return (
      <div>
        <div className="LinkClicksTimeSeries-summary">
          <span>
            <strong>{total.toLocaleString()}</strong> {app.translator.trans('datlechin-link-clicks.admin.timeseries.total_clicks')}
          </span>
          <span>
            <strong>{avg.toLocaleString()}</strong> {app.translator.trans('datlechin-link-clicks.admin.timeseries.avg_per_day')}
          </span>
          <span>
            <strong>{data.max.toLocaleString()}</strong> {app.translator.trans('datlechin-link-clicks.admin.timeseries.peak_day')}
          </span>
        </div>

        <svg className="LinkClicksTimeSeries-svg" viewBox={`0 0 ${w} ${h}`} preserveAspectRatio="none">
          <polygon points={areaPoints} className="LinkClicksTimeSeries-area" />
          <polyline points={linePoints} className="LinkClicksTimeSeries-line" />
          {data.points.map((p, i) => (
            <circle cx={pad + i * stepX} cy={h - pad - p.count * scaleY} r={2.5} className="LinkClicksTimeSeries-dot">
              <title>
                {dayjs(p.date).format('ddd MMM D')}: {p.count} {p.count === 1 ? 'click' : 'clicks'}
              </title>
            </circle>
          ))}
        </svg>

        <div className="LinkClicksTimeSeries-axis">
          <span>{dayjs(data.points[0].date).format('MMM D')}</span>
          <span>{dayjs(data.points[data.points.length - 1].date).format('MMM D')}</span>
        </div>
      </div>
    );
  }
}
