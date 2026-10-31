import { request } from './http';

/**
 * Alertes : son généré (aucun fichier à charger), notification système et abonnement push.
 */
let audioContext = null;

function context() {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!audioContext && AudioContext) {
        audioContext = new AudioContext();
    }

    return audioContext;
}

/**
 * Les navigateurs n'autorisent le son qu'après une interaction : on prépare le contexte au premier clic.
 */
export function unlockSound() {
    const audio = context();
    if (audio?.state === 'suspended') {
        audio.resume().catch(() => {});
    }
}

export function playNotificationSound() {
    const audio = context();
    if (!audio) {
        return;
    }
    if (audio.state === 'suspended') {
        audio.resume().catch(() => {});
    }

    // Deux notes brèves et montantes, façon « ding-dong ».
    const start = audio.currentTime + 0.01;
    [[880, 0], [1318.5, 0.13]].forEach(([frequency, delay]) => {
        const oscillator = audio.createOscillator();
        const gain = audio.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0.0001, start + delay);
        gain.gain.exponentialRampToValueAtTime(0.3, start + delay + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + delay + 0.45);
        oscillator.connect(gain).connect(audio.destination);
        oscillator.start(start + delay);
        oscillator.stop(start + delay + 0.5);
    });
}

/**
 * Notification du système (onglet en arrière-plan) ; passe par le service worker quand il existe,
 * seule méthode acceptée par Chrome sur Android.
 */
export async function showSystemNotification({ title, body, url, icon, tag }) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    const options = { body, icon: icon || '/icons/icon-192.png', badge: '/icons/icon-192.png', tag, renotify: true, data: { url } };
    const registration = await navigator.serviceWorker?.getRegistration().catch(() => null);

    if (registration) {
        registration.showNotification(title, options);
        return;
    }

    const notification = new Notification(title, options);
    notification.onclick = () => {
        window.focus();
        window.location.href = url;
    };
}

function base64UrlToBytes(value) {
    const base64 = (value + '='.repeat((4 - (value.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');

    return Uint8Array.from(window.atob(base64), (char) => char.charCodeAt(0));
}

function sameKey(buffer, expected) {
    if (!buffer) {
        return true;
    }
    const actual = new Uint8Array(buffer);

    return actual.length === expected.length && actual.every((byte, index) => byte === expected[index]);
}

/**
 * Abonne l'appareil aux notifications push (reçues même application fermée) et l'enregistre sur le serveur.
 */
export async function subscribeToPush(url) {
    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!url || !vapidKey || !('serviceWorker' in navigator) || !('PushManager' in window) || Notification.permission !== 'granted') {
        return false;
    }

    const registration = await navigator.serviceWorker.ready;
    const applicationServerKey = base64UrlToBytes(vapidKey);
    let subscription = await registration.pushManager.getSubscription();

    // Clé du serveur changée : l'ancien abonnement n'est plus valable.
    if (subscription && !sameKey(subscription.options?.applicationServerKey, applicationServerKey)) {
        await subscription.unsubscribe();
        subscription = null;
    }

    subscription ??= await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey });

    const { endpoint, keys } = subscription.toJSON();
    const contentEncoding = (window.PushManager.supportedContentEncodings ?? []).includes('aes128gcm') ? 'aes128gcm' : 'aesgcm';
    await request(url, { method: 'POST', body: { endpoint, keys, contentEncoding } });

    return true;
}
