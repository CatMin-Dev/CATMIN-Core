<?php

namespace App\Services\Editor;

use DOMDocument;
use DOMElement;
use DOMNode;

class WysiwygSanitizer
{
    private const REMOVE_WITH_CONTENT = ['script', 'style', 'head', 'meta', 'link', 'base', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'noscript'];

    private const ALLOWED_TAGS = [
        'p', 'br', 'hr',
        'strong', 'b', 'em', 'i', 'u', 's', 'del', 'mark', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4',
        'ul', 'ol', 'li',
        'blockquote', 'pre', 'code',
        'a', 'img',
        'div', 'span',
    ];

    /**
     * @var array<string, string[]>
     */
    private const ALLOWED_ATTRS = [
        '*' => ['class', 'style'],
        'a' => ['href', 'title', 'target', 'rel', 'class', 'style'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'class'],
        'ol' => ['type', 'start', 'class'],
        'li' => ['value', 'class'],
        'pre' => ['class'],
        'code' => ['class'],
    ];

    private const URL_ATTRS = ['href', 'src'];

    private const ALLOWED_STYLE_PROPS = [
        'text-align',
        'color',
        'background-color',
    ];

    public function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><html><body><div id="__editor_root__">' . $html . '</div></body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__editor_root__');
        if ($root === null) {
            return '';
        }

        $this->cleanNode($doc, $root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        return trim($output);
    }

    private function cleanNode(DOMDocument $doc, DOMNode $node): void
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->unwrapNode($node, $child);
                continue;
            }

            $allowed = array_unique(array_merge(self::ALLOWED_ATTRS['*'], self::ALLOWED_ATTRS[$tag] ?? []));
            $toRemove = [];

            foreach ($child->attributes as $attr) {
                $name = strtolower($attr->name);

                if (str_starts_with($name, 'on') || str_starts_with($name, 'data-')) {
                    $toRemove[] = $attr->name;
                    continue;
                }

                if (!in_array($name, $allowed, true)) {
                    $toRemove[] = $attr->name;
                    continue;
                }

                if (in_array($name, self::URL_ATTRS, true) && !$this->isSafeUrl($attr->value)) {
                    $toRemove[] = $attr->name;
                    continue;
                }

                if ($name === 'class') {
                    $cleanClass = $this->sanitizeClassList($attr->value);
                    if ($cleanClass === '') {
                        $toRemove[] = $attr->name;
                    } else {
                        $child->setAttribute('class', $cleanClass);
                    }
                }

                if ($name === 'style') {
                    $cleanStyle = $this->sanitizeStyle($attr->value);
                    if ($cleanStyle === '') {
                        $toRemove[] = $attr->name;
                    } else {
                        $child->setAttribute('style', $cleanStyle);
                    }
                }
            }

            foreach ($toRemove as $attrName) {
                $child->removeAttribute($attrName);
            }

            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }

            $this->cleanNode($doc, $child);
        }
    }

    private function unwrapNode(DOMNode $parent, DOMElement $child): void
    {
        $grandChildren = [];
        foreach ($child->childNodes as $grandChild) {
            $grandChildren[] = $grandChild->cloneNode(true);
        }

        foreach ($grandChildren as $grandChild) {
            $parent->insertBefore($grandChild, $child);
        }

        $parent->removeChild($child);
    }

    private function sanitizeClassList(string $classList): string
    {
        $allowed = (array) config('catmin_editor.allowed_css_classes', []);
        $candidate = preg_split('/\s+/', trim($classList)) ?: [];
        $clean = [];

        foreach ($candidate as $className) {
            $className = trim((string) $className);
            if ($className === '') {
                continue;
            }

            if (in_array($className, $allowed, true)) {
                $clean[] = $className;
            }
        }

        return implode(' ', array_unique($clean));
    }

    private function sanitizeStyle(string $style): string
    {
        $rules = explode(';', $style);
        $clean = [];

        foreach ($rules as $rule) {
            [$prop, $value] = array_pad(explode(':', $rule, 2), 2, '');
            $prop = strtolower(trim($prop));
            $value = trim($value);

            if ($prop === '' || $value === '') {
                continue;
            }

            if (!in_array($prop, self::ALLOWED_STYLE_PROPS, true)) {
                continue;
            }

            if (preg_match('/expression|javascript:|url\s*\(/i', $value)) {
                continue;
            }

            $clean[] = $prop . ': ' . $value;
        }

        return implode('; ', $clean);
    }

    private function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return true;
        }

        $lower = strtolower(preg_replace('/[\x00-\x1f\s]+/', '', html_entity_decode($url, ENT_QUOTES, 'UTF-8')) ?? '');
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) {
            return false;
        }

        if (str_starts_with($url, '/') || str_starts_with($url, '#') || str_starts_with($url, '?') || str_starts_with($url, './') || str_starts_with($url, '../')) {
            return true;
        }

        if (str_starts_with($url, '//')) {
            return true;
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        if ($scheme === '' && !isset($parsed['host'])) {
            return true;
        }

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }
}
