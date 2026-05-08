import Extend from 'flarum/common/extenders';
import LinkClicksPage from './components/LinkClicksPage';

export default [
  new Extend.Admin().page(LinkClicksPage),
];
