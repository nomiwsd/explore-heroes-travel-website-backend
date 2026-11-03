<?php

namespace Modules\Location\Support;

/**
 * Strips pasted presentational markup from rich-text destination content
 * while keeping semantic HTML (p, h2-h4, ul/ol/li, a, strong, em, img, br ...).
 *
 *  - removes `style` attributes (and legacy font attrs: face/color/size) everywhere
 *  - unwraps <font> and Office <o:p> tags (children kept)
 *  - unwraps <span> elements left without attributes, drops empty spans
 *  - drops <style>, <script> and <meta> elements
 *
 * Input without any of those patterns is returned unchanged (no reformatting).
 */
class ContentCleaner
{
    public static function needsCleaning($html): bool
    {
        if (!is_string($html) || $html === '' || strpos($html, '<') === false) {
            return false;
        }
        return (bool) preg_match('/\sstyle\s*=|<\s*\/?\s*(font|span|style|script|meta|o:p)\b/i', $html);
    }

    public static function clean($html)
    {
        if (!static::needsCleaning($html)) {
            return $html;
        }
        if (!class_exists(\DOMDocument::class)) {
            return static::regexClean($html);
        }

        // libxml's HTML parser turns <o:p> into <p>; drop Office namespace tags up front
        $html = preg_replace('#</?[a-z]+:[a-z0-9]+\b[^>]*>#i', '', $html);

        $prev = libxml_use_internal_errors(true);
        try {
            $doc = new \DOMDocument('1.0', 'UTF-8');
            $wrapped = '<?xml encoding="UTF-8"?><div id="__eh_root__">' . $html . '</div>';
            $ok = $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            if (!$ok) {
                return static::regexClean($html);
            }
            $doc->encoding = 'UTF-8';

            $root = null;
            foreach ($doc->getElementsByTagName('div') as $div) {
                if ($div->getAttribute('id') === '__eh_root__') {
                    $root = $div;
                    break;
                }
            }
            if (!$root) {
                return static::regexClean($html);
            }

            $xpath = new \DOMXPath($doc);

            // 1. drop non-content elements
            foreach (iterator_to_array($xpath->query('.//style|.//script|.//meta', $root)) as $node) {
                $node->parentNode->removeChild($node);
            }

            // 2. strip presentational attributes
            foreach (iterator_to_array($xpath->query('.//*[@style or @face or @color or @size]', $root)) as $el) {
                foreach (['style', 'face', 'color', 'size'] as $attr) {
                    if ($el->hasAttribute($attr) && !($attr === 'size' && strtolower($el->nodeName) === 'input')) {
                        $el->removeAttribute($attr);
                    }
                }
            }

            // 3. unwrap font / o:p / attribute-less spans (deepest first)
            $nodes = [];
            foreach ($xpath->query('.//*', $root) as $el) {
                $name = strtolower($el->nodeName);
                if ($name === 'font' || $name === 'span' || strpos($name, ':') !== false) {
                    $nodes[] = $el;
                }
            }
            foreach (array_reverse($nodes) as $el) {
                $name = strtolower($el->nodeName);
                if ($name === 'span' && $el->attributes && $el->attributes->length > 0) {
                    if (trim($el->textContent) === '' && !$el->getElementsByTagName('*')->length) {
                        $el->parentNode->removeChild($el);
                    }
                    continue;
                }
                static::unwrap($el);
            }

            $out = '';
            foreach ($root->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }
            return $out;
        } catch (\Throwable $e) {
            return static::regexClean($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    protected static function unwrap(\DOMNode $el): void
    {
        $parent = $el->parentNode;
        if (!$parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }

    /** Fallback when DOM is unavailable / parsing fails. */
    protected static function regexClean(string $html): string
    {
        $html = preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('/\s(style|face|color)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('#</?(font|o:p)\b[^>]*>#i', '', $html);
        $html = preg_replace('#<span\s*>(.*?)</span>#is', '$1', $html);
        return $html;
    }

    /**
     * Clean the rich-text fields of a request payload / attribute array.
     */
    public static function cleanFields(array $data, array $fields = ['content', 'short_description']): array
    {
        foreach ($fields as $f) {
            if (array_key_exists($f, $data) && is_string($data[$f])) {
                $data[$f] = static::clean($data[$f]);
            }
        }
        return $data;
    }
}
