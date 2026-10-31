import { playNotificationSound, showSystemNotification, subscribeToPush, unlockSound } from '../notifier';

const TOAST_DURATION = 7000;
const MAX_TOASTS = 4;
const CONTEXTS = { like: 'Publication', comment: 'Publication', meeting: 'Réunion' };

const storage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Stockage indisponible (navigation privée) : préférence gardée pour la page en cours.
        }
    },
};

/**
 * Coquille de l'espace membres : badges, notifications (bandeau, son, système, push),
 * installation de l'application (PWA).
 */
export function registerShell(Alpine) {
    Alpine.store('chat', { active: null });

    const supportsNotifications = 'Notification' in window;

    Alpine.store('notifications', {
        sound: storage.get('ag35.sound') !== 'off',
        permission: supportsNotifications ? Notification.permission : 'unsupported',
        pushEnabled: false,
        bannerDismissed: storage.get('ag35.notifications-banner') === 'dismissed',

        get canAsk() {
            return this.permission === 'default';
        },

        get showBanner() {
            return this.canAsk && !this.bannerDismissed;
        },

        async requestPermission() {
            unlockSound();
            if (!supportsNotifications) {
                return;
            }
            this.permission = await Notification.requestPermission();
            if (this.permission === 'granted') {
                await this.enablePush();
            }
        },

        /**
         * Push : alertes reçues même quand l'application est fermée.
         */
        async enablePush() {
            try {
                this.pushEnabled = await subscribeToPush(document.body.dataset.pushUrl);
            } catch {
                this.pushEnabled = false;
            }
        },

        dismissBanner() {
            this.bannerDismissed = true;
            storage.set('ag35.notifications-banner', 'dismissed');
        },

        toggleSound() {
            this.sound = !this.sound;
            storage.set('ag35.sound', this.sound ? 'on' : 'off');
            if (this.sound) {
                unlockSound();
                playNotificationSound();
            }
        },
    });

    ['pointerdown', 'keydown', 'touchstart'].forEach((type) => window.addEventListener(type, unlockSound, { once: true, passive: true }));

    // L'événement d'installation reste hors du store : un proxy réactif casserait son appel natif.
    let installPrompt = null;

    Alpine.store('pwa', {
        canInstall: false,
        // Safari sur iPhone/iPad n'a pas de bouton d'installation : on explique « Partager → Sur l'écran d'accueil ».
        ios: /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1),
        standalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
        dismissed: storage.get('ag35.install-banner') === 'dismissed',

        get showBanner() {
            return !this.standalone && !this.dismissed && (this.canInstall || this.ios);
        },

        dismiss() {
            this.dismissed = true;
            storage.set('ag35.install-banner', 'dismissed');
        },

        showHelp() {
            this.dismissed = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async install() {
            if (!installPrompt) {
                return;
            }
            installPrompt.prompt();
            await installPrompt.userChoice.catch(() => null);
            installPrompt = null;
            this.canInstall = false;
        },
    });

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        Alpine.store('pwa').canInstall = true;
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        Alpine.store('pwa').canInstall = false;
        Alpine.store('pwa').standalone = true;
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            await navigator.serviceWorker.register('/sw.js').catch(() => {});
            if (Alpine.store('notifications').permission === 'granted') {
                Alpine.store('notifications').enablePush();
            }
        });
    }

    Alpine.data('appShell', ({ unread, unreadNotifications }) => {
        // Messages comptés dans le badge depuis l'ouverture de la page, par conversation.
        const pendingByConversation = {};
        const baseTitle = document.title;
        let toastSequence = 0;

        return {
            unread,
            unreadNotifications,
            toasts: [],

            init() {
                const channel = document.body.dataset.userChannel;

                if (channel && window.Echo) {
                    window.Echo.private(channel)
                        .listen('.message.sent', (payload) => this.receive(payload))
                        .listen('.notification.created', (payload) => this.receiveNotification(payload));
                }

                window.addEventListener('chat:read', (event) => this.markConversationRead(event.detail));

                this.updateTitle();
                this.$watch('unread', () => this.updateTitle());
                this.$watch('unreadNotifications', () => this.updateTitle());
            },

            receive(payload) {
                window.dispatchEvent(new CustomEvent('chat:message', { detail: payload }));

                const message = payload.message;
                if (message.author.uuid === document.body.dataset.userUuid) {
                    return;
                }

                const isViewing = Alpine.store('chat').active === message.conversation && document.visibilityState === 'visible';
                if (!isViewing) {
                    this.unread++;
                    pendingByConversation[message.conversation] = (pendingByConversation[message.conversation] ?? 0) + 1;
                }

                const context = payload.type === 'direct' ? 'Message privé' : (payload.conversationName ?? 'Groupe');
                const body = message.body || '📎 Pièce jointe';

                this.toast({ kind: 'message', title: message.author.name, body, context, url: payload.url, avatar: message.author.avatar, initials: message.author.initials });
                this.alert({
                    title: payload.type === 'direct' ? message.author.name : `${message.author.name} · ${context}`,
                    body,
                    url: payload.url,
                    icon: message.author.avatar,
                    tag: `conversation-${message.conversation}`,
                });
            },

            /**
             * « J'aime », commentaire, réunion : la cloche, le bandeau et le son.
             */
            receiveNotification({ notification, unread: unreadCount }) {
                this.unreadNotifications = unreadCount;
                window.dispatchEvent(new CustomEvent('notification:created', { detail: notification }));

                const { actor, kind, text, excerpt, url } = notification;

                this.toast({
                    kind,
                    title: actor.name,
                    body: excerpt ? `${text} : « ${excerpt} »` : text,
                    context: CONTEXTS[kind] ?? 'Notification',
                    url,
                    avatar: actor.avatar,
                    initials: actor.initials,
                });
                this.alert({ title: `${actor.name} ${text}`, body: excerpt ?? '', url, icon: actor.avatar, tag: `notification-${notification.id}` });
            },

            /**
             * Son, puis notification système si l'onglet est caché et que le push ne s'en charge pas déjà.
             */
            alert(systemNotification) {
                const settings = Alpine.store('notifications');

                if (settings.sound) {
                    playNotificationSound();
                }

                if (document.visibilityState !== 'visible' && !settings.pushEnabled) {
                    showSystemNotification(systemNotification);
                }
            },

            markConversationRead(uuid) {
                this.unread = Math.max(0, this.unread - (pendingByConversation[uuid] ?? 0));
                pendingByConversation[uuid] = 0;
            },

            updateTitle() {
                const total = this.unread + this.unreadNotifications;
                document.title = total > 0 ? `(${total > 99 ? '99+' : total}) ${baseTitle}` : baseTitle;
            },

            toast(notification) {
                const toast = { id: ++toastSequence, visible: false, timer: null, ...notification };

                this.toasts.unshift(toast);
                this.toasts.slice(MAX_TOASTS).forEach((old) => this.dismiss(old));

                this.$nextTick(() => {
                    this.findToast(toast.id).visible = true;
                    this.schedule(toast.id, TOAST_DURATION);
                });
            },

            findToast(id) {
                return this.toasts.find((toast) => toast.id === id) ?? {};
            },

            schedule(id, delay) {
                const toast = this.findToast(id);
                clearTimeout(toast.timer);
                toast.timer = setTimeout(() => this.dismiss(toast), delay);
            },

            hold(toast) {
                clearTimeout(toast.timer);
            },

            release(toast) {
                this.schedule(toast.id, 2500);
            },

            dismiss(toast) {
                clearTimeout(toast.timer);
                toast.visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter((item) => item.id !== toast.id);
                }, 300);
            },
        };
    });
}
