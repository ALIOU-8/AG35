import { request } from '../http';

const JOIN_TIMEOUT = 12000;

/**
 * Visio intégrée : maillage WebRTC pair-à-pair, signalisation par « whispers » sur le canal de présence Reverb.
 * Chaque paire applique la « négociation parfaite » (MDN) : les collisions d'offres se résolvent seules,
 * ce qui permet aussi de renégocier en cours de réunion (partage d'écran, caméra activée plus tard).
 */
export function meetingRoom(config) {
    const me = config.me.uuid;
    // Objets WebRTC hors de l'état réactif d'Alpine.
    const peers = new Map();
    const roster = new Map();
    let localStream = new MediaStream();
    let screenTrack = null;
    let channel = null;
    let joinTimer = null;

    return {
        phase: 'lobby',
        joining: false,
        realtime: Boolean(window.Echo),
        error: window.Echo ? '' : 'Le serveur temps réel est indisponible : la visio ne peut pas démarrer pour le moment.',
        mediaError: '',
        hasMic: false,
        hasCamera: false,
        micOn: false,
        camOn: false,
        sharing: false,
        handUp: false,
        needsAudioUnlock: false,
        canShareScreen: Boolean(navigator.mediaDevices?.getDisplayMedia),
        canEnd: config.canEnd,
        tiles: [],

        async init() {
            window.addEventListener('pagehide', () => this.cleanup());
            await this.openMedia();
        },

        async openMedia() {
            if (!navigator.mediaDevices?.getUserMedia) {
                this.mediaError = 'Ce navigateur ne donne pas accès à la caméra (une connexion HTTPS est nécessaire).';
                return;
            }

            try {
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: { echoCancellation: true, noiseSuppression: true },
                    video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                });
            } catch {
                try {
                    localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.mediaError = 'Caméra indisponible : vous participerez en audio.';
                } catch {
                    this.mediaError = 'Micro et caméra inaccessibles : autorisez-les dans le navigateur, ou participez en écoute seule.';
                }
            }

            this.hasMic = localStream.getAudioTracks().length > 0;
            this.hasCamera = localStream.getVideoTracks().length > 0;
            this.micOn = this.hasMic;
            this.camOn = this.hasCamera;
            this.$refs.preview.srcObject = localStream;
        },

        join() {
            if (!window.Echo || this.joining) {
                return;
            }

            this.joining = true;
            this.error = '';
            joinTimer = setTimeout(() => {
                this.joining = false;
                this.error = 'Connexion au serveur de visio impossible. Vérifiez votre réseau et réessayez.';
            }, JOIN_TIMEOUT);

            channel = window.Echo.join(`meeting.${config.channel}`)
                .here((members) => {
                    clearTimeout(joinTimer);
                    this.joining = false;
                    this.phase = 'joined';
                    this.tiles = [this.makeTile(config.me, true)];
                    this.$nextTick(() => this.bind(me));

                    // Le nouvel arrivant appelle chacun ; les autres répondent à son offre.
                    members.filter((member) => member.uuid !== me).forEach((member) => {
                        this.addMember(member);
                        this.createPeer(member.uuid);
                    });
                    this.broadcastState();
                })
                .joining((member) => {
                    this.addMember(member);
                    setTimeout(() => this.broadcastState(), 800);
                })
                .leaving((member) => this.removePeer(member.uuid))
                .listenForWhisper('signal', (data) => this.onSignal(data))
                .listenForWhisper('state', (data) => this.onState(data))
                .listenForWhisper('ended', () => this.onEnded())
                .error(() => {
                    clearTimeout(joinTimer);
                    this.joining = false;
                    this.error = "Impossible de rejoindre cette réunion : elle est peut-être terminée.";
                });
        },

        makeTile(member, self = false) {
            return {
                uuid: member.uuid,
                name: member.name,
                initials: member.initials,
                avatar: member.avatar,
                self,
                mic: self ? this.micOn : true,
                cam: self ? this.camOn : false,
                hand: false,
                screen: false,
                state: self ? 'connected' : 'connecting',
            };
        },

        addMember(member) {
            roster.set(member.uuid, member);
            if (!this.tile(member.uuid)) {
                this.tiles.push(this.makeTile(member));
            }
        },

        tile(uuid) {
            return this.tiles.find((tile) => tile.uuid === uuid);
        },

        createPeer(uuid) {
            const pc = new RTCPeerConnection({ iceServers: config.iceServers });
            const peer = { pc, stream: new MediaStream(), polite: me > uuid, makingOffer: false, ignoreOffer: false };
            peers.set(uuid, peer);

            const videoTrack = screenTrack ?? localStream.getVideoTracks()[0];
            localStream.getAudioTracks().forEach((track) => pc.addTrack(track, localStream));
            if (videoTrack) {
                pc.addTrack(videoTrack, localStream);
            }

            pc.ontrack = ({ track }) => {
                peer.stream.addTrack(track);
                track.onunmute = () => this.bind(uuid);
                this.bind(uuid);
            };

            pc.onnegotiationneeded = async () => {
                try {
                    peer.makingOffer = true;
                    await pc.setLocalDescription();
                    this.signal(uuid, { description: pc.localDescription });
                } catch (error) {
                    console.error(error);
                } finally {
                    peer.makingOffer = false;
                }
            };

            pc.onicecandidate = ({ candidate }) => {
                if (candidate) {
                    this.signal(uuid, { candidate });
                }
            };

            pc.onconnectionstatechange = () => {
                const tile = this.tile(uuid);
                if (!tile) {
                    return;
                }
                if (pc.connectionState === 'connected') {
                    tile.state = 'connected';
                } else if (pc.connectionState === 'failed') {
                    tile.state = 'failed';
                    pc.restartIce();
                }
            };

            return peer;
        },

        async onSignal({ from, to, description, candidate }) {
            if (to !== me) {
                return;
            }

            if (!this.tile(from)) {
                this.addMember(roster.get(from) ?? { uuid: from, name: 'Participant', initials: '?', avatar: null });
            }

            const peer = peers.get(from) ?? this.createPeer(from);
            const pc = peer.pc;

            try {
                if (description) {
                    const offerCollision = description.type === 'offer' && (peer.makingOffer || pc.signalingState !== 'stable');
                    peer.ignoreOffer = !peer.polite && offerCollision;
                    if (peer.ignoreOffer) {
                        return;
                    }

                    await pc.setRemoteDescription(description);
                    if (description.type === 'offer') {
                        await pc.setLocalDescription();
                        this.signal(from, { description: pc.localDescription });
                    }
                } else if (candidate) {
                    try {
                        await pc.addIceCandidate(candidate);
                    } catch (error) {
                        if (!peer.ignoreOffer) {
                            throw error;
                        }
                    }
                }
            } catch (error) {
                console.error(error);
            }
        },

        signal(to, payload) {
            channel?.whisper('signal', { from: me, to, ...JSON.parse(JSON.stringify(payload)) });
        },

        broadcastState() {
            channel?.whisper('state', { from: me, mic: this.micOn, cam: this.camOn || this.sharing, hand: this.handUp, screen: this.sharing });
        },

        onState({ from, mic, cam, hand, screen }) {
            const tile = this.tile(from);
            if (tile) {
                Object.assign(tile, { mic, cam, hand, screen });
                this.$nextTick(() => this.bind(from));
            }
        },

        removePeer(uuid) {
            peers.get(uuid)?.pc.close();
            peers.delete(uuid);
            roster.delete(uuid);
            this.tiles = this.tiles.filter((tile) => tile.uuid !== uuid);
        },

        /**
         * Les flux ne passent pas par l'état réactif : on les branche directement sur l'élément vidéo.
         */
        attach(element, uuid) {
            const stream = uuid === me ? (screenTrack ? new MediaStream([screenTrack]) : localStream) : peers.get(uuid)?.stream;
            if (!stream || element.srcObject === stream) {
                return;
            }

            // Jamais son propre micro dans ses haut-parleurs (écho).
            element.muted = uuid === me;
            element.srcObject = stream;
            element.play().catch(() => {
                if (uuid !== me) {
                    this.needsAudioUnlock = true;
                }
            });
        },

        bind(uuid) {
            const element = this.$root.querySelector(`[data-tile="${uuid}"] video`);
            if (element) {
                this.attach(element, uuid);
            }
        },

        unlockAudio() {
            this.$root.querySelectorAll('[data-tile] video').forEach((element) => element.play().catch(() => {}));
            this.needsAudioUnlock = false;
        },

        toggleMic() {
            const track = localStream.getAudioTracks()[0];
            if (!track) {
                return;
            }
            track.enabled = !track.enabled;
            this.micOn = track.enabled;
            this.updateSelf();
        },

        toggleCam() {
            const track = localStream.getVideoTracks()[0];
            if (!track) {
                return;
            }
            track.enabled = !track.enabled;
            this.camOn = track.enabled;
            this.updateSelf();
        },

        toggleHand() {
            this.handUp = !this.handUp;
            this.updateSelf();
        },

        async toggleScreen() {
            if (this.sharing) {
                this.stopScreen();
                return;
            }

            try {
                const display = await navigator.mediaDevices.getDisplayMedia({ video: true });
                screenTrack = display.getVideoTracks()[0];
            } catch {
                return;
            }

            screenTrack.onended = () => this.stopScreen();
            this.sharing = true;
            this.replaceVideo(screenTrack);
            this.updateSelf();
        },

        stopScreen() {
            if (!screenTrack) {
                return;
            }
            screenTrack.onended = null;
            screenTrack.stop();
            screenTrack = null;
            this.sharing = false;
            this.replaceVideo(localStream.getVideoTracks()[0] ?? null);
            this.updateSelf();
        },

        replaceVideo(track) {
            peers.forEach(({ pc }) => {
                const transceiver = pc.getTransceivers().find((item) => item.receiver.track.kind === 'video');

                if (!transceiver) {
                    if (track) {
                        pc.addTrack(track, localStream);
                    }
                    return;
                }

                transceiver.sender.replaceTrack(track);
                // Un participant arrivé sans caméra n'envoyait pas de vidéo : on renégocie le sens.
                if (track && !['sendrecv', 'sendonly'].includes(transceiver.direction)) {
                    transceiver.direction = 'sendrecv';
                }
            });
        },

        updateSelf() {
            const tile = this.tile(me);
            if (tile) {
                Object.assign(tile, { mic: this.micOn, cam: this.camOn || this.sharing, hand: this.handUp, screen: this.sharing });
                this.$nextTick(() => {
                    const element = this.$root.querySelector(`[data-tile="${me}"] video`);
                    if (element) {
                        element.srcObject = null;
                        this.attach(element, me);
                    }
                });
            }
            this.broadcastState();
        },

        async endForAll() {
            if (!confirm('Terminer la réunion pour tous les participants ?')) {
                return;
            }

            try {
                await request(config.endUrl, { method: 'POST' });
            } catch (error) {
                this.error = error.message;
                return;
            }

            channel?.whisper('ended', {});
            this.leave();
        },

        onEnded() {
            this.cleanup();
            alert("L'organisateur a terminé la réunion.");
            window.location.href = config.leaveUrl;
        },

        leave() {
            this.cleanup();
            window.location.href = config.leaveUrl;
        },

        cleanup() {
            clearTimeout(joinTimer);
            peers.forEach(({ pc }) => pc.close());
            peers.clear();
            localStream.getTracks().forEach((track) => track.stop());
            screenTrack?.stop();
            if (channel) {
                window.Echo.leave(`meeting.${config.channel}`);
                channel = null;
            }
        },
    };
}

/**
 * Grandes réunions : salle Jitsi Meet intégrée.
 */
export function jitsiRoom(config) {
    return {
        error: '',

        init() {
            const script = document.createElement('script');
            script.src = `https://${config.jitsiDomain}/external_api.js`;
            script.async = true;
            script.onload = () => this.start();
            script.onerror = () => {
                this.error = "Le service de visio n'a pas pu être chargé. Vérifiez votre connexion.";
            };
            document.head.appendChild(script);
        },

        start() {
            const api = new window.JitsiMeetExternalAPI(config.jitsiDomain, {
                roomName: `AG35-${config.channel}`,
                parentNode: this.$refs.frame,
                width: '100%',
                height: '100%',
                lang: 'fr',
                userInfo: { displayName: config.me.name },
                configOverwrite: { subject: config.title, prejoinPageEnabled: true, disableDeepLinking: true },
                interfaceConfigOverwrite: { MOBILE_APP_PROMO: false },
            });

            api.addListener('readyToClose', () => {
                window.location.href = config.leaveUrl;
            });
        },
    };
}
