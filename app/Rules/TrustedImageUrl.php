<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TrustedImageUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || strlen($value) > 800) {
            $fail('The :attribute must be a valid image URL.');

            return;
        }

        if (str_starts_with($value, '/assets/') || str_starts_with($value, '/storage/')) {
            return;
        }

        $parts = parse_url($value);

        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            $fail('The :attribute must use http, https, /assets, or /storage.');

            return;
        }

        $host = strtolower($parts['host']);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $trustedHosts = array_map('strtolower', config('farmbridge_security.trusted_image_hosts', []));

        if ($appHost) {
            $trustedHosts[] = strtolower($appHost);
        }

        if (! in_array($host, array_unique($trustedHosts), true)) {
            $fail('The :attribute must be hosted by FarmBridge or a trusted image host.');
        }
    }
}
