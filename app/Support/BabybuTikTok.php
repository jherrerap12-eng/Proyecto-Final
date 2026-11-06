<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Obtiene el ID público de un video de TikTok.
 * Los enlaces vt.tiktok.com son redirecciones, no IDs de video.
 * Se limita el destino de cada solicitud a dominios oficiales de TikTok.
 */
final class BabybuTikTok
{
    public static function videoId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $id = self::extractId($url);
        if ($id !== null) {
            return $id;
        }

        if (! self::allowedShortUrl($url)) {
            return null;
        }

        $key = 'babybu:video:' . sha1($url);
        $saved = Cache::get($key);
        if (is_string($saved) && ctype_digit($saved)) {
            return $saved;
        }

        // Como respaldo, primero se intenta oEmbed con la URL corta.
        try {
            $r = Http::timeout(5)->acceptJson()->get('https://www.tiktok.com/oembed', ['url' => $url]);
            if ($r->successful()) {
                $candidate = $r->json('embed_product_id');
                if (is_string($candidate) && ctype_digit($candidate)) {
                    Cache::put($key, $candidate, now()->addDays(7));
                    return $candidate;
                }
                if (is_int($candidate)) {
                    Cache::put($key, (string) $candidate, now()->addDays(7));
                    return (string) $candidate;
                }
            }
        } catch (Throwable $e) {
            // Si TikTok impide el acceso, la portada sigue funcionando.
        }

        // A continuación se sigue manualmente la redirección, sin enviar
        // solicitudes a dominios ajenos a TikTok.
        $current = $url;
        for ($i = 0; $i < 4; $i++) {
            if (! self::allowedTikTokUrl($current)) {
                break;
            }
            $found = self::extractId($current);
            if ($found !== null) {
                Cache::put($key, $found, now()->addDays(7));
                return $found;
            }
            try {
                $response = Http::timeout(5)->withOptions(['allow_redirects' => false])
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; BabybuToys/1.0)'])
                    ->get($current);
                $location = $response->header('Location');
                if (! is_string($location) || $location === '') {
                    // Algunos servidores entregan el enlace en el HTML.
                    if (preg_match('~(?:/video/|/photo/)([0-9]{12,22})~', $response->body(), $m)) {
                        Cache::put($key, $m[1], now()->addDays(7));
                        return $m[1];
                    }
                    break;
                }
                if (str_starts_with($location, '/')) {
                    $location = 'https://' . parse_url($current, PHP_URL_HOST) . $location;
                }
                $current = html_entity_decode($location, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } catch (Throwable $e) {
                break;
            }
        }
        return null;
    }

    private static function extractId(string $url): ?string
    {
        if (preg_match('~^https://(?:www\.)?tiktok\.com/@[A-Za-z0-9._-]+/(?:video|photo)/([0-9]{12,22})(?:[/?#].*)?$~i', $url, $m)) {
            return $m[1];
        }
        if (preg_match('~^https://www\.tiktok\.com/player/v1/([0-9]{12,22})(?:[/?#].*)?$~i', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    private static function allowedShortUrl(string $url): bool
    {
        return (bool) preg_match('~^https://(?:vt|vm)\.tiktok\.com/[A-Za-z0-9_-]+/?(?:\?.*)?$~i', $url);
    }

    private static function allowedTikTokUrl(string $url): bool
    {
        $p = parse_url($url);
        if (! is_array($p) || ($p['scheme'] ?? '') !== 'https') {
            return false;
        }
        return in_array(strtolower($p['host'] ?? ''), [
            'www.tiktok.com', 'tiktok.com', 'm.tiktok.com', 'vt.tiktok.com', 'vm.tiktok.com',
        ], true);
    }
}
