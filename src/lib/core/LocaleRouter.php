<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class LocaleRouter
{
    private $routes = [];
    private $detector;
    private $availableLocales = [];

    public function __construct(array $detectorSettings = [])
    {
        $this->detector = new LocaleDetector($detectorSettings);
        $this->availableLocales = $this->detector->getAvailableLocales();
        $this->registerDefaultRoutes();
    }

    private function registerDefaultRoutes(): void
    {
        $this->routes = [
            'home' => '/',
            'blog' => '/blog',
            'category' => '/category/(?<category>[\w\-]+)',
            'archive' => '/archive/[0-9]{2}/[0-9]{4}',
            'page' => '/page/(?<page>[^/]+)',
            'single' => '/post/(?<id>\d+)/(?<post>[\w\-]+)',
            'search' => '/search',
            'tag' => '/tag/(?<tag>[\w\-]+)',
        ];
    }

    public function addRoute(string $name, string $pattern): void
    {
        $this->routes[$name] = $pattern;
    }

    public function match(string $path): ?array
    {
        $locale = $this->extractLocale($path);
        $cleanPath = $this->stripLocalePrefix($path);

        foreach ($this->routes as $name => $pattern) {
            $fullPattern = '^' . $pattern . '$';

            if (preg_match('~' . $fullPattern . '~i', $cleanPath, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return [
                    'route' => $name,
                    'params' => $params,
                    'locale' => $locale,
                    'path' => $cleanPath
                ];
            }
        }

        return null;
    }

    public function extractLocale(string $path): ?string
    {
        $path = trim($path, '/');
        $segments = explode('/', $path);
        $firstSegment = $segments[0] ?? '';

        if ($this->detector->isValidLocale($firstSegment)) {
            return $firstSegment;
        }

        return null;
    }

    public function stripLocalePrefix(string $path): string
    {
        if (empty($this->availableLocales)) {
            return $path;
        }

        $localePattern = '^/(?:' . implode('|', $this->availableLocales) . ')(?:/|$)';
        return preg_replace('#' . $localePattern . '#', '/', $path, 1);
    }

    public function buildUrl(string $path, ?string $locale = null): string
    {
        $locale = $locale ?? $this->detector->detect();
        $path = '/' . ltrim($path, '/');

        return "/{$locale}{$path}";
    }

    public function buildCurrentUrl(string $path): string
    {
        return $this->buildUrl($path, $this->detector->detect());
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function hasLocalePrefix(string $path): bool
    {
        return $this->extractLocale($path) !== null;
    }

    public function getDetector(): LocaleDetector
    {
        return $this->detector;
    }
}
