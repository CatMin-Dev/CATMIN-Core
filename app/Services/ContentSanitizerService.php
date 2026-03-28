<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Whitelist-based HTML sanitizer.
 *
 * Strips all tags and attributes not explicitly allowed, removes event
 * handlers (on*), rejects javascript: / data: URIs and forces
 * rel="noopener noreferrer" on external links.
 *
 * No third-party dependency — uses PHP's built-in DOM extension.
 */
class ContentSanitizerService
{
    /**
     * Tags whose content is kept but the tag itself is unwrapped when not in the whitelist.
     * Tags like <script>, <style>, <head> are removed along with their content.
     */
    private const REMOVE_WITH_CONTENT = ['script', 'style', 'head', 'meta', 'link', 'base', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'noscript'];

    private const ALLOWED_TAGS = [
        'p', 'br', 'hr',
        'strong', 'b', 'em', 'i', 'u', 's', 'del', 'mark', 'sub', 'sup',
        'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'a',
        'img',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'figure', 'figcaption',
        'span', 'div',
    ];

    /**
     * Allowed attributes per tag.  '*' applies to all tags.
     *
     * @var array<string, string[]>
     */
    private const ALLOWED_ATTRS = [
        'a'   => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td'  => ['colspan', 'rowspan'],
        'th'  => ['colspan', 'rowspan', 'scope'],
        'ol'  => ['type', 'start'],
        'li'  => ['value'],
        'pre' => ['class'],   // allow language hint for syntax highlighting
        'code'=> ['class'],
        'span'=> ['class'],
        'div' => ['class'],
        'p'   => ['class'],
    ];

    private const URL_ATTRS = ['href', 'src'];

    // -----------------------------------------------------------------------

    public function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        $doc = new DOMDocument('1.0', 'UTF-8');
        // Wrap content so libxml keeps all top-level nodes.
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><html><body><div id="__sanitizer_root__">'
            . $html
            . '</div></body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__sanitizer_root__');
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

    // -----------------------------------------------------------------------

    private function cleanNode(DOMDocument $doc, DOMNode $node): void
    {
        // Snapshot children because we may mutate the list
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMText) {
                // Plain text is always safe
                continue;
            }

            if (!$child instanceof DOMElement) {
                // Remove comments, processing instructions, CDATA, etc.
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);

            // Remove entire subtree for dangerous tags
            if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unwrap: keep children, drop the tag
                $grandchildren = [];
                foreach ($child->childNodes as $gc) {
                    $grandchildren[] = $gc->cloneNode(true);
                }
                foreach ($grandchildren as $gc) {
                    $node->insertBefore($gc, $child);
                }
                $node->removeChild($child);
                continue;
            }

            // --- Clean attributes ---
            $allowedAttrs = self::ALLOWED_ATTRS[$tag] ?? [];
            $toRemove = [];

            foreach ($child->attributes as $attr) {
                // Strip all event handlers
                if (str_starts_with(strtolower($attr->name), 'on')) {
                    $toRemove[] = $attr->name;
                    continue;
                }
                // Strip all data-* attributes
                if (str_starts_with(strtolower($attr->name), 'data-')) {
                    $toRemove[] = $attr->name;
                    continue;
                }
                if (!in_array($attr->name, $allowedAttrs, true)) {
                    $toRemove[] = $attr->name;
                    continue;
                }
                // Validate URL values
                if (in_array($attr->name, self::URL_ATTRS, true) && !$this->isSafeUrl($attr->value)) {
                    $toRemove[] = $attr->name;
                }
            }

            foreach ($toRemove as $attrName) {
                $child->removeAttribute($attrName);
            }

            // Force rel="noopener noreferrer" when target="_blank" to prevent tabnapping
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }

            // Recurse into children
            $this->cleanNode($doc, $child);
        }
    }

    private function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return true;
        }

        // Normalise for prefix checks (strip whitespace and decode entities)
        $lower = strtolower(preg_replace('/[\x00-\x1f\s]+/', '', html_entity_decode($url, ENT_QUOTES, 'UTF-8')) ?? '');

        // Reject javascript: and data: regardless of obfuscation
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) {
            return false;
        }

        // Relative paths and fragment references are fine
        if (str_starts_with($url, '/') || str_starts_with($url, '#') || str_starts_with($url, '?') || str_starts_with($url, './') || str_starts_with($url, '../')) {
            return true;
        }

        // Protocol-relative
        if (str_starts_with($url, '//')) {
            return true;
        }

        // Absolute URLs — only http/https/mailto
        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');

        // Bare relative path (no scheme, no host) — e.g. "image.jpg", "assets/img/foo.png"
        if ($scheme === '' && !isset($parsed['host'])) {
            return true;
        }

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }
}
