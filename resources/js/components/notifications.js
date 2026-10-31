import { request } from '../http';

const LIST_SIZE = 15;

/**
 * Cloche des notifications. Le compteur « unreadNotifications » appartient à appShell (titre de l'onglet).
 */
export default function notificationBell({ listUrl, readAllUrl }) {
    return {
        open: false,
        loaded: false,
        loading: false,
        error: '',
        items: [],

        init() {
            window.addEventListener('notification:created', (event) => {
                if (this.loaded) {
                    this.items = [event.detail, ...this.items].slice(0, LIST_SIZE);
                }
            });
        },

        async toggle() {
            this.open = !this.open;
            if (this.open) {
                await this.load();
            }
        },

        async load() {
            this.loading = true;
            this.error = '';

            try {
                const response = await request(listUrl);
                this.items = response.notifications;
                this.unreadNotifications = response.unread;
                this.loaded = true;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async readAll() {
            try {
                await request(readAllUrl, { method: 'POST' });
                this.items = this.items.map((item) => ({ ...item, read: true }));
                this.unreadNotifications = 0;
            } catch (error) {
                this.error = error.message;
            }
        },
    };
}
