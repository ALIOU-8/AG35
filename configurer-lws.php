<?php

/*
 * Configuration d'AG35 sur l'hébergement mutualisé LWS, à lancer dans le terminal LWS :
 *
 *     php configurer-lws.php
 *
 * Demande l'accès MySQL (testé avant d'être enregistré) et le secret Pusher, écrit le .env,
 * génère les clés de l'application, crée les tables et optimise le site.
 * Relançable sans risque : les réponses déjà enregistrées sont proposées par défaut.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const ENV_TEMPLATE = <<<'ENV'
APP_NAME=AG35
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://ag35.vitalitygn.com

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=fr_FR
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
FILESYSTEM_DISK=local
# Sans tâche cron, les notifications push partent pendant la requête.
QUEUE_CONNECTION=sync

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=2208445
PUSHER_APP_KEY=269fc9ba99e4b12b23a7
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=eu

VAPID_SUBJECT="mailto:contact@ag35.fr"
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=

VISIO_DRIVER=webrtc

MAIL_MAILER=log
MAIL_FROM_ADDRESS="contact@ag35.fr"
MAIL_FROM_NAME="AG35"

ENV;

$root = __DIR__;
$envFile = $root.'/.env';

function say(string $text = ''): void
{
    fwrite(STDOUT, $text.PHP_EOL);
}

function fail(string $text): never
{
    fwrite(STDERR, PHP_EOL.'✗ '.$text.PHP_EOL);
    exit(1);
}

/**
 * Question dans le terminal ; Entrée garde la valeur par défaut. Les secrets ne s'affichent pas quand c'est possible.
 */
function ask(string $label, string $default = '', bool $secret = false): string
{
    $hint = $secret ? ($default !== '' ? ' [Entrée = garder l\'actuel]' : '') : ($default !== '' ? " [{$default}]" : '');
    fwrite(STDOUT, "  {$label}{$hint} : ");

    if ($secret) {
        @shell_exec('stty -echo 2>/dev/null');
    }
    $answer = trim((string) fgets(STDIN));
    if ($secret) {
        @shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, PHP_EOL);
    }

    return $answer === '' ? $default : $answer;
}

/**
 * Valeur entre guillemets pour le .env : \, " et $ échappés, relue à l'identique par Laravel.
 */
function envValue(string $value): string
{
    return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
}

function writeEnv(string $file, array $values): void
{
    $content = (string) file_get_contents($file);

    foreach ($values as $key => $value) {
        $line = $key.'='.envValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
        $content = preg_match($pattern, $content)
            ? preg_replace_callback($pattern, fn () => $line, $content)
            : rtrim($content).PHP_EOL.$line.PHP_EOL;
    }

    file_put_contents($file, $content);
}

function readEnv(string $file, string $key): string
{
    if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', (string) file_get_contents($file), $match)) {
        return '';
    }

    $value = trim($match[1]);
    if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
        $value = str_replace(['\\$', '\\"', '\\\\'], ['$', '"', '\\'], substr($value, 1, -1));
    }

    return $value;
}

function artisan(string $command): void
{
    passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/artisan').' '.$command, $status);
    if ($status !== 0) {
        fail("La commande « php artisan {$command} » a échoué (voir le message ci-dessus).");
    }
}

say('Configuration d\'AG35 pour https://ag35.vitalitygn.com');
say('PHP '.PHP_VERSION);

if (version_compare(PHP_VERSION, '8.4.1', '<')) {
    fail('PHP 8.4.1 ou plus est nécessaire.');
}
if (! is_file($root.'/vendor/autoload.php')) {
    fail('Dépendances absentes : lancez d\'abord « composer install --no-dev --optimize-autoloader ».');
}
if (! is_file($envFile)) {
    file_put_contents($envFile, ENV_TEMPLATE);
    say('Fichier .env créé.');
}

say();
say('1/4 Base MySQL (panel LWS → Bases de données MySQL)');
for ($attempt = 1; ; $attempt++) {
    $database = [
        'DB_HOST' => ask('Hôte', readEnv($envFile, 'DB_HOST') ?: 'localhost'),
        'DB_PORT' => ask('Port', readEnv($envFile, 'DB_PORT') ?: '3306'),
        'DB_DATABASE' => ask('Nom de la base', readEnv($envFile, 'DB_DATABASE')),
        'DB_USERNAME' => ask('Utilisateur', readEnv($envFile, 'DB_USERNAME')),
        'DB_PASSWORD' => ask('Mot de passe', readEnv($envFile, 'DB_PASSWORD'), secret: true),
    ];

    try {
        $pdo = new PDO(
            "mysql:host={$database['DB_HOST']};port={$database['DB_PORT']};dbname={$database['DB_DATABASE']};charset=utf8mb4",
            $database['DB_USERNAME'],
            $database['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8],
        );
        say('  ✓ Connexion réussie (MySQL '.$pdo->query('select version()')->fetchColumn().')');
        writeEnv($envFile, $database);
        break;
    } catch (PDOException $e) {
        say('  ✗ Connexion impossible : '.$e->getMessage());
        if ($attempt >= 3) {
            fail('Vérifiez les informations dans le panel LWS, puis relancez « php configurer-lws.php ».');
        }
        say('  Réessayons.');
    }
}

say();
say('2/4 Temps réel (pusher.com → votre app → App Keys)');
$secret = ask('Secret Pusher', readEnv($envFile, 'PUSHER_APP_SECRET'), secret: true);
if ($secret === '') {
    fail('Le secret Pusher est nécessaire pour la messagerie et les notifications en direct.');
}
writeEnv($envFile, ['PUSHER_APP_SECRET' => $secret]);
say('  ✓ Enregistré');

say();
say('3/4 Clés de l\'application');
if (readEnv($envFile, 'APP_KEY') === '') {
    artisan('key:generate --force');
}
if (readEnv($envFile, 'VAPID_PUBLIC_KEY') === '') {
    artisan('webpush:vapid --force');
}
say('  ✓ Clés en place');

say();
say('4/4 Tables et optimisation');
// Le cache est en base : pas de cache:clear avant que sa table existe.
artisan('config:clear');
artisan('migrate --force');
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/artisan').' storage:link', $linkStatus);
if ($linkStatus !== 0) {
    say('  ! Lien des fichiers envoyés impossible : les photos ne s\'afficheront pas, signalez-le.');
}
artisan('optimize');

say();
say('✓ Terminé. Ouvrez https://ag35.vitalitygn.com et inscrivez-vous : le premier compte devient administrateur.');
