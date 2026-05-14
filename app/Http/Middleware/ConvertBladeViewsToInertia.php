<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ConvertBladeViewsToInertia
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldConvert($request, $response)) {
            return $response;
        }

        $html = $response->getContent() ?: '';

        if (! str_contains($html, '<!--UHMS_LEGACY_LAYOUT-->')) {
            return $response;
        }

        return Inertia::render('Legacy/BladePage', [
            'html' => $this->extractBetween($html, '<!--UHMS_LEGACY_BODY_START-->', '<!--UHMS_LEGACY_BODY_END-->') ?: '',
            'styles' => $this->extractBetween($html, '<!--UHMS_LEGACY_STYLES_START-->', '<!--UHMS_LEGACY_STYLES_END-->') ?: '',
            'scripts' => $this->extractBetween($html, '<!--UHMS_LEGACY_SCRIPTS_START-->', '<!--UHMS_LEGACY_SCRIPTS_END-->') ?: '',
            'title' => $this->extractTitle($html),
            'url' => $request->fullUrl(),
        ])->toResponse($request);
    }

    private function shouldConvert(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->expectsJson() && ! $request->headers->has('X-Inertia')) {
            return false;
        }

        if (! $response->isOk()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return $contentType === '' || str_contains($contentType, 'text/html');
    }

    private function extractBetween(string $html, string $startMarker, string $endMarker): ?string
    {
        $start = strpos($html, $startMarker);

        if ($start === false) {
            return null;
        }

        $start += strlen($startMarker);
        $end = strpos($html, $endMarker, $start);

        if ($end === false) {
            return null;
        }

        return trim(substr($html, $start, $end - $start));
    }

    private function extractTitle(string $html): string
    {
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches) !== 1) {
            return config('app.name', 'UHMS');
        }

        return trim(html_entity_decode(strip_tags($matches[1])));
    }
}