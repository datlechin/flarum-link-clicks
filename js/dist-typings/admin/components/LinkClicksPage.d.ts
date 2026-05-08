import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
type Tab = 'settings' | 'analytics' | 'webhook';
export default class LinkClicksPage extends ExtensionPage {
    protected activeTab: Tab;
    protected setTab(tab: Tab): void;
    content(): Mithril.Vnode<any, any>;
    protected renderActiveTab(): Mithril.Vnode<any, any>;
    protected renderTabButton(tab: Tab, icon: string): Mithril.Vnode<any, any>;
    protected renderSettings(): Mithril.Vnode<any, any>;
    protected renderWebhook(): Mithril.Vnode<any, any>;
    protected renderAnalytics(): Mithril.Vnode<any, any>;
}
export {};
