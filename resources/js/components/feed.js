import { errorMessage, headers, request } from '../http';

const MAX_FILES = 10;

let commentKey = 0;
const withKey = (comment) => ({ ...comment, key: ++commentKey });

/**
 * Nouvelle publication : texte, photos et vidéos, avec barre de progression (les vidéos peuvent être lourdes).
 */
export function postComposer({ url }) {
    return {
        body: '',
        files: [],
        visibility: 'members',
        expanded: false,
        uploading: false,
        progress: 0,
        errors: [],

        add(event) {
            this.errors = [];

            for (const file of event.target.files) {
                if (this.files.length >= MAX_FILES) {
                    this.errors = [`${MAX_FILES} fichiers maximum par publication.`];
                    break;
                }
                this.files.push({
                    key: `${file.name}-${file.size}-${Date.now()}-${this.files.length}`,
                    file,
                    isVideo: file.type.startsWith('video/'),
                    preview: URL.createObjectURL(file),
                });
            }

            event.target.value = '';
        },

        remove(index) {
            URL.revokeObjectURL(this.files[index].preview);
            this.files.splice(index, 1);
        },

        submit() {
            if (this.uploading || (!this.body.trim() && !this.files.length)) {
                return;
            }

            const data = new FormData();
            data.append('body', this.body.trim());
            data.append('visibility', this.visibility);
            this.files.forEach((item) => data.append('media[]', item.file));

            this.uploading = true;
            this.progress = 0;
            this.errors = [];

            const xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            Object.entries(headers()).forEach(([name, value]) => xhr.setRequestHeader(name, value));

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    this.progress = Math.round((event.loaded / event.total) * 100);
                }
            };

            xhr.onload = () => {
                let response = {};
                try {
                    response = JSON.parse(xhr.responseText);
                } catch {
                    // Réponse non JSON (erreur serveur) : message générique ci-dessous.
                }

                if (xhr.status >= 200 && xhr.status < 300) {
                    window.location.href = response.redirect ?? window.location.href;
                    return;
                }

                this.uploading = false;
                this.errors = response.errors
                    ? [...new Set(Object.values(response.errors).flat())]
                    : [errorMessage(response, xhr.status)];
            };

            xhr.onerror = () => {
                this.uploading = false;
                this.errors = ["La connexion a été interrompue pendant l'envoi."];
            };

            xhr.send(data);
        },
    };
}

/**
 * Publication du fil : « j'aime », commentaires et visionneuse de photos.
 */
export function postCard(state) {
    return {
        ...state,
        comments: state.comments.map(withKey),
        busy: false,
        sending: false,
        draft: '',
        error: '',
        showAll: state.showAll ?? false,
        viewer: null,

        get visibleComments() {
            return this.showAll ? this.comments : this.comments.slice(-3);
        },

        async toggleLike() {
            const wasLiked = this.liked;
            this.busy = true;
            this.liked = !wasLiked;
            this.likes += wasLiked ? -1 : 1;

            try {
                const response = await request(this.likeUrl, { method: 'POST' });
                this.liked = response.liked;
                this.likes = response.likes;
            } catch (error) {
                this.liked = wasLiked;
                this.likes += wasLiked ? 1 : -1;
                this.error = error.message;
            } finally {
                this.busy = false;
            }
        },

        async addComment() {
            const body = this.draft.trim();
            if (!body || this.sending) {
                return;
            }

            this.sending = true;
            this.error = '';

            try {
                const response = await request(this.commentUrl, { method: 'POST', body: { body } });
                this.comments.push(withKey(response.comment));
                this.showAll = true;
                this.draft = '';
                this.$refs.comment.style.height = 'auto';
            } catch (error) {
                this.error = error.message;
            } finally {
                this.sending = false;
            }
        },

        async removeComment(comment) {
            if (!confirm('Supprimer ce commentaire ?')) {
                return;
            }

            try {
                await request(comment.deleteUrl, { method: 'DELETE' });
                this.comments = this.comments.filter((item) => item.key !== comment.key);
            } catch (error) {
                this.error = error.message;
            }
        },

        open(index) {
            if (this.images.length) {
                this.viewer = index;
            }
        },

        next() {
            if (this.viewer !== null) {
                this.viewer = (this.viewer + 1) % this.images.length;
            }
        },

        prev() {
            if (this.viewer !== null) {
                this.viewer = (this.viewer - 1 + this.images.length) % this.images.length;
            }
        },
    };
}
