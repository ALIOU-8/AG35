/**
 * Requêtes JSON vers Laravel : jeton CSRF et identifiant de socket (pour exclure l'expéditeur des diffusions).
 */
export function headers() {
    const result = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    };

    const socketId = window.Echo?.socketId?.();
    if (socketId) {
        result['X-Socket-ID'] = socketId;
    }

    return result;
}

export function errorMessage(data, status) {
    if (data?.errors) {
        return Object.values(data.errors).flat()[0];
    }

    switch (status) {
        case 413:
            return 'Fichier trop volumineux.';
        case 419:
            return 'Votre session a expiré : rechargez la page.';
        case 429:
            return 'Trop de requêtes, patientez quelques secondes.';
        default:
            return data?.message || 'Une erreur est survenue, réessayez.';
    }
}

export class RequestError extends Error {
    constructor(status, data) {
        super(errorMessage(data, status));
        this.status = status;
        this.data = data;
    }
}

export async function request(url, { method = 'GET', body = null } = {}) {
    const options = { method, headers: headers(), credentials: 'same-origin' };

    if (body instanceof FormData) {
        options.body = body;
    } else if (body !== null) {
        options.body = JSON.stringify(body);
        options.headers['Content-Type'] = 'application/json';
    }

    let response;
    try {
        response = await fetch(url, options);
    } catch {
        throw new RequestError(0, { message: 'Connexion impossible : vérifiez votre réseau.' });
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new RequestError(response.status, data);
    }

    return data;
}

export function realtimeConnected() {
    return window.Echo?.connector?.pusher?.connection?.state === 'connected';
}
