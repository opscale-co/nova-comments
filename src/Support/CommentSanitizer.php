<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans comment HTML against a configurable allow-list, keeping the rich-text
 * markup and file attachments produced by Trix while removing XSS vectors.
 */
class CommentSanitizer
{
    public function sanitize(string $html): string
    {
        if (! (bool) config('nova-comments.sanitizer.enabled', true)) {
            return $html;
        }

        return $this->sanitizer()->sanitize($html);
    }

    private function sanitizer(): HtmlSanitizer
    {
        $config = new HtmlSanitizerConfig;

        /** @var array<string, list<string>> $allowed */
        $allowed = (array) config('nova-comments.sanitizer.allowed', []);

        foreach ($allowed as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        /** @var list<string> $schemes */
        $schemes = (array) config('nova-comments.sanitizer.allowed_schemes', ['http', 'https', 'mailto', 'tel']);

        return new HtmlSanitizer(
            $config
                ->allowLinkSchemes($schemes)
                ->allowMediaSchemes($schemes)
                ->allowRelativeLinks()
                ->allowRelativeMedias()
        );
    }
}
