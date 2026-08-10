<?php

declare(strict_types=1);

return [
    'commenter' => [
        'nova-resource' => null,
    ],

    'comments-panel' => [
        'name' => null,
    ],

    'limit' => 100,

    'attachments' => [
        // Filesystem disk used to store comment attachments uploaded through Trix.
        'disk' => env('NOVA_COMMENTS_ATTACHMENTS_DISK', 'public'),

        // Directory (relative to the disk root) where attachments are stored.
        'path' => 'nova-comments/attachments',

        // Maximum upload size in kilobytes.
        'max-size' => (int) env('NOVA_COMMENTS_ATTACHMENTS_MAX_SIZE', 12288),

        // Allowed file extensions. Use null to allow any type.
        'mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
    ],

    'sanitizer' => [
        // When enabled, new comments are run through an HTML allow-list before
        // being stored. This strips XSS vectors (<script>, event handlers,
        // javascript: URLs, ...) while keeping the rich-text markup and file
        // attachments that Trix produces. Setting a Comment::whenCreating()
        // callback replaces this default sanitizer.
        'enabled' => true,

        // Elements kept in stored comments and, per element, the attributes
        // preserved on them. Anything not listed here is removed.
        'allowed' => [
            'p' => [],
            'br' => [],
            'div' => ['class'],
            'span' => ['class'],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            's' => [],
            'del' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'blockquote' => [],
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
            'pre' => [],
            'code' => [],
            'a' => ['href', 'target', 'rel'],
            'figure' => ['class', 'data-trix-attachment', 'data-trix-attributes', 'data-trix-content-type'],
            'figcaption' => ['class'],
            'img' => ['src', 'alt', 'width', 'height'],
        ],

        // URL schemes permitted on links and media.
        'allowed_schemes' => ['http', 'https', 'mailto', 'tel'],
    ],
];
