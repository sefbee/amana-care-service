<?php

namespace App\FrontEnd\Cms\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class CmsHtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'b', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'h2', 'h3', 'h4', 'a'];

    public static function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root) {
            return '';
        }

        self::cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $parent->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);
            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'], true)) {
                    $parent->removeChild($child);
                } else {
                    self::cleanChildren($child);
                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }
                    $parent->removeChild($child);
                }
                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
            $title = $tag === 'a' ? $child->getAttribute('title') : '';
            while ($child->attributes->length) {
                $child->removeAttributeNode($child->attributes->item(0));
            }
            if ($tag === 'a' && self::safeLink($href)) {
                $child->setAttribute('href', $href);
                if ($title !== '') {
                    $child->setAttribute('title', $title);
                }
                if (str_starts_with($href, 'https://')) {
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            } elseif ($tag === 'a') {
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            self::cleanChildren($child);
        }
    }

    private static function safeLink(string $url): bool
    {
        if (preg_match('/[\x00-\x20\\\\]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        if (str_starts_with($url, 'https://')) {
            $parts = parse_url($url);

            return is_array($parts) && ! empty($parts['host']) && ! isset($parts['user']) && ! isset($parts['pass']);
        }

        return str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:');
    }
}
