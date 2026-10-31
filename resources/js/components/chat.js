import { realtimeConnected, request } from '../http';

const POLL_INTERVAL = 5000;
const TYPING_TIMEOUT = 3500;

/**
 * Messagerie : liste des conversations et conversation ouverte.
 * Temps réel via Reverb (relayé par appShell), avec polling de secours si le WebSocket est coupé.
 */
export default function chat({ conversations, active, me }) {
    // Objets non réactifs : canal Echo et minuteries.
    let typingChannel = null;
    let typingTimer = null;
    let lastTypingSentAt = 0;
    let readTimer = null;

    return {
        conversations,
        active,
        messages: active?.messages ?? [],
        hasMore: active?.hasMore ?? false,
        search: '',
        draft: '',
        file: null,
        sending: false,
        error: '',
        loadingOlder: false,
        typing: '',
        stickToBottom: true,
        isTouch: window.matchMedia('(pointer: coarse)').matches,

        get isGroup() {
            return this.active !== null && this.active.type !== 'direct';
        },

        get filteredConversations() {
            const query = this.search.trim().toLowerCase();

            return query ? this.conversations.filter((item) => item.name.toLowerCase().includes(query)) : this.conversations;
        },

        get timeline() {
            return this.messages.map((message, index) => {
                const previous = this.messages[index - 1];
                const next = this.messages[index + 1];
                const showDay = !previous || previous.day !== message.day;

                return {
                    message,
                    mine: message.author.uuid === me.uuid,
                    showDay,
                    showAuthor: showDay || previous.author.uuid !== message.author.uuid,
                    showAvatar: !next || next.author.uuid !== message.author.uuid || next.day !== message.day,
                };
            });
        },

        init() {
            window.addEventListener('chat:message', (event) => this.receive(event.detail));

            if (!this.active) {
                return;
            }

            this.$store.chat.active = this.active.uuid;
            this.clearUnread(this.active.uuid);
            this.$nextTick(() => this.scrollToBottom());

            if (window.Echo) {
                typingChannel = window.Echo.private(`conversation.${this.active.uuid}`);
                typingChannel.listenForWhisper('typing', (data) => this.showTyping(data));
            }

            setInterval(() => this.poll(), POLL_INTERVAL);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.markRead();
                }
            });
        },

        receive(payload) {
            this.touchConversation(payload);

            if (this.active && payload.message.conversation === this.active.uuid) {
                this.typing = '';
                this.append([payload.message]);
                if (document.visibilityState === 'visible') {
                    this.markRead();
                }
            }
        },

        /**
         * Remonte la conversation en tête de liste avec l'aperçu du dernier message.
         */
        touchConversation({ message, url, type, conversationName }) {
            const mine = message.author.uuid === me.uuid;
            const isViewing = this.active?.uuid === message.conversation;
            let item = this.conversations.find((conversation) => conversation.uuid === message.conversation);

            if (item) {
                this.conversations = this.conversations.filter((conversation) => conversation.uuid !== item.uuid);
            } else {
                const isDirect = type === 'direct';
                item = {
                    uuid: message.conversation,
                    url,
                    type,
                    name: isDirect ? message.author.name : (conversationName ?? 'Groupe'),
                    avatar: isDirect ? message.author.avatar : null,
                    initials: isDirect ? message.author.initials : (conversationName ?? 'G').slice(0, 2).toUpperCase(),
                    online: isDirect,
                    unread: 0,
                };
            }

            item.preview = (mine ? 'Vous : ' : '') + (message.body || '📎 Pièce jointe');
            item.time = message.time;
            if (!mine && !isViewing) {
                item.unread++;
            }

            this.conversations.unshift(item);
        },

        clearUnread(uuid) {
            const item = this.conversations.find((conversation) => conversation.uuid === uuid);
            if (item) {
                item.unread = 0;
            }
        },

        append(messages) {
            const known = new Set(this.messages.map((message) => message.uuid));
            const fresh = messages.filter((message) => !known.has(message.uuid));

            if (!fresh.length) {
                return 0;
            }

            const shouldScroll = this.stickToBottom || fresh.some((message) => message.author.uuid === me.uuid);
            this.messages.push(...fresh);

            if (shouldScroll) {
                this.$nextTick(() => this.scrollToBottom());
            }

            return fresh.length;
        },

        async send() {
            const body = this.draft.trim();
            if (this.sending || (!body && !this.file)) {
                return;
            }

            const data = new FormData();
            if (body) {
                data.append('body', body);
            }
            if (this.file) {
                data.append('attachment', this.file);
            }

            this.sending = true;
            this.error = '';

            try {
                const response = await request(this.active.urls.send, { method: 'POST', body: data });
                this.draft = '';
                this.clearFile();
                this.autosize(this.$refs.input);
                this.append([response.message]);
                this.touchConversation({ message: response.message, url: window.location.href, type: this.active.type, conversationName: this.active.name });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.sending = false;
                if (!this.isTouch) {
                    this.$refs.input.focus();
                }
            }
        },

        clearFile() {
            this.file = null;
            if (this.$refs.file) {
                this.$refs.file.value = '';
            }
        },

        async loadOlder() {
            if (this.loadingOlder || !this.hasMore || !this.messages.length) {
                return;
            }

            const scroller = this.$refs.scroller;
            const previousHeight = scroller.scrollHeight;
            this.loadingOlder = true;

            try {
                const response = await request(`${this.active.urls.older}?before=${encodeURIComponent(this.messages[0].uuid)}`);
                this.messages.unshift(...response.messages);
                this.hasMore = response.hasMore;
                this.$nextTick(() => {
                    scroller.scrollTop += scroller.scrollHeight - previousHeight;
                });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loadingOlder = false;
            }
        },

        /**
         * Secours quand le WebSocket n'est pas connecté.
         */
        async poll() {
            if (realtimeConnected() || document.visibilityState !== 'visible') {
                return;
            }

            const last = this.messages[this.messages.length - 1];

            try {
                const response = await request(`${this.active.urls.poll}${last ? `?after=${encodeURIComponent(last.uuid)}` : ''}`);
                if (this.append(response.messages) > 0) {
                    this.markRead();
                }
            } catch {
                // Nouvel essai au prochain intervalle.
            }
        },

        markRead() {
            window.dispatchEvent(new CustomEvent('chat:read', { detail: this.active.uuid }));
            clearTimeout(readTimer);
            readTimer = setTimeout(() => request(this.active.urls.read, { method: 'POST' }).catch(() => {}), 800);
        },

        notifyTyping() {
            if (!typingChannel || Date.now() - lastTypingSentAt < 2500) {
                return;
            }

            lastTypingSentAt = Date.now();
            typingChannel.whisper('typing', { uuid: me.uuid, name: me.name });
        },

        showTyping({ uuid, name }) {
            if (uuid === me.uuid) {
                return;
            }

            this.typing = this.isGroup ? `${name.split(' ')[0]} écrit…` : 'écrit…';
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                this.typing = '';
            }, TYPING_TIMEOUT);
        },

        onScroll() {
            const scroller = this.$refs.scroller;
            this.stickToBottom = scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight < 120;

            if (scroller.scrollTop < 40) {
                this.loadOlder();
            }
        },

        keepAtBottom() {
            if (this.stickToBottom) {
                this.scrollToBottom();
            }
        },

        scrollToBottom() {
            const scroller = this.$refs.scroller;
            if (scroller) {
                scroller.scrollTop = scroller.scrollHeight;
            }
        },

        autosize(element) {
            if (!element) {
                return;
            }
            element.style.height = 'auto';
            element.style.height = `${element.scrollHeight}px`;
        },
    };
}
