<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#009460">
        <title>Hors ligne · AG35</title>
        <link rel="icon" type="image/png" sizes="64x64" href="/icons/favicon-64.png">
        {{-- Page servie par le service worker sans réseau : styles intégrés, aucune ressource externe. --}}
        <style>
            body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box;
                   font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #e6f6ef, #fff, #fefce8); color: #111827; }
            .card { max-width: 420px; width: 100%; background: #fff; border-radius: 20px; overflow: hidden; text-align: center;
                    box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 0 0 1px rgba(17,24,39,.05); }
            .stripe { height: 6px; background: linear-gradient(90deg, #CE1126 0 33.33%, #FCD116 33.33% 66.66%, #009460 66.66% 100%); }
            .body { padding: 32px 28px; }
            .logo { width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px; display: block; }
            h1 { font-size: 20px; margin: 0 0 8px; }
            p { margin: 0; color: #4b5563; font-size: 15px; line-height: 1.5; }
            button { margin-top: 24px; border: 0; border-radius: 12px; padding: 10px 18px; background: #009460; color: #fff; font-weight: 600; font-size: 14px; cursor: pointer; }
            button:hover { background: #007f53; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="stripe"></div>
            <div class="body">
                <img src="/icons/icon-192.png" alt="AG35" class="logo">
                <h1>Vous êtes hors ligne</h1>
                <p>Vérifiez votre connexion Internet. L'espace membres de l'AG35 se rechargera dès que le réseau sera de retour.</p>
                <button type="button" onclick="location.reload()">Réessayer</button>
            </div>
        </div>
        <script>window.addEventListener('online', () => location.reload());</script>
    </body>
</html>
