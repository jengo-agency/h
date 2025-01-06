<?php

namespace Jengo;
/*
* A tag must be lowercase --> to transform
* Nesting rules.
* a tag can contain attributes. their values are quoted, unless it's : 'required', 'checked', 'selected', 'multiple', 'readonly', 'disabled'
*/
// Security : escape H::div('<script>alert("xss")</script>'); ? No, ouputting some html is ok; including script. it's the same with default php output, right ?


//String builder : nice idea, let's keep it for after.
/* Edge cases :
- H::div(new stdClass());  // Object conversion
- H::div('') ; H::div(null);  H::div(false);  : ok.
- H::div(0); H::div(0.0); H::div('0'); : ok.
- H::div(true); H::div(false); : ok.
- H::div(1); H::div(0.0); H::div('1'); : ok.
*/
/*
    //https://html.spec.whatwg.org/multipage/dom.html#content-models
	1.	Tag Rules:
	•	1.1 Tags must be properly nested (no overlapping) : Always enforced
	•	1.2 Void elements must not have closing tags : Always enforced
	•	1.3 Non-void elements must have closing tags : Always enforced
	•	1.4 Tag names must start ywith letter, then any ascii . then transformed to lowercase:  Exception in strict mode, then transformed to lowercase in smart and strict mode.
    •	1.5 No duplicate attributes : Always enforced if single function (by syntax). Additional attributes (from where ??) will be let at they are in loose mode, merged in smart mode, throw exception in strict mode.
    •	1.6 Boolean attributes must use correct syntax : boolean value will be dropped (true : only attribute, false : attribute dropped) always. Loose mode : no check, Smart mode : dropped if not "false". Strict mode : throw exception if not "true"/"false" or empty or attribute name. 
    •	1.7 No duplicate Id attribute values : IGNORED, not enforced.
    2.	Content Model Rules:
	•	2.1 Phrasing content elements must only contain phrasing content: Let as it is in Loose mode and smart mode , throw exception in strict mode.
	•	2.2 <p> must only contain phrasing content : let as it is , autoclosed / autoopened in smart mode, throw exception in strict mode.
	•	2.3 Elements with specific content models must respect them (like table structure): always IGNORED,  not enforced (we don't know the context).
    •	2.4 "Transparent" content elements (like <a>) inherit their content model from their parent: always IGNORED,  not enforced (we don't know the context).
	3.	Interactive Content Rules:
	•	3.1 <a> must not contain interactive elements : ignored in LOOSE &  smart mode, throw exception in strict mode.
	•	3.2 <a> must not be nested within <a> : IGNORED in LOOSE mode, autoclose the first one when encoutnering a second one in smart mode, throw exception in strict mode.
*/

/**
 * LOOSE Mode : will do it's best to provide the output the way it's input, even if not valid html. Nothing happens if rule violation is detected
 *
 * STRICT Mode : will do its best to throw an exception instead of fixinig it, if Rule (under) violation is detected
 *
 * SMART Mode: will do its best to try to fix it, teh way chrome if fixing it. No more, no less.
 *
 * What WILL NOT be checked */
/*
 * duplicate id values
 * <tr> must be inside <table>, <thead>, <tbody>, or <tfoot>, <td> and <th> must be inside <tr>: we don't know if the html you generate is part of a bigger one.
 */



class H {
    private static $totalTimes = [];  // Add this with other static properties
    public const MODE_LOOSE = 'loose';
    public const MODE_STRICT = 'strict';
    public const MODE_SMART = 'smart';
    protected static $mode = self::MODE_STRICT;
    private static $openTags = []; // For nesting validation
    private static $indentCache = [];
    private const PHRASING_CONTENT_TAGS = [
        // Always phrasing content
        'abbr' => [],
        'b' => [],
        'bdi' => [],
        'bdo' => [],
        'br' => [],
        'cite' => [],
        'code' => [],
        'data' => [],
        'dfn' => [],
        'em' => [],
        'i' => [],
        'kbd' => [],
        'mark' => [],
        'q' => [],
        'rb' => [],
        'rp' => [],
        'rt' => [],
        'rtc' => [],
        'ruby' => [],
        's' => [],
        'samp' => [],
        'small' => [],
        'span' => [],
        'strong' => [],
        'sub' => [],
        'sup' => [],
        'time' => [],
        'u' => [],
        'var' => [],
        'wbr' => [],

        // Phrasing content with conditions
        'a' => ['interactive_if' => ['href']],
        'area' => ['interactive_if' => ['href']],
        'audio' => ['interactive_if' => ['controls']],
        'button' => [],  // always interactive
        'canvas' => [],
        'del' => [],
        'embed' => [],
        'iframe' => [],
        'img' => ['interactive_if' => ['usemap']],
        'input' => ['not_interactive_if' => ['type' => ['hidden']]],
        'ins' => [],
        'label' => [],
        'map' => [],
        'meter' => [],
        'noscript' => [],
        'object' => ['interactive_if' => ['usemap']],
        'output' => [],
        'picture' => [],
        'progress' => [],
        'script' => [],
        'select' => [],
        'svg' => [],
        'textarea' => [],
        'video' => ['interactive_if' => ['controls']]
    ];

    private const VOID_TAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    ];

    private const  BOOLEAN_ATTRIBUTES = [
        'allowfullscreen',
        'async',
        'autofocus',
        'autoplay',
        'checked',
        'controls',
        'default',
        'defer',
        'disabled',
        'download',
        'draggable',
        'formnovalidate',
        'hidden',
        'ismap',
        'loop',
        'multiple',
        'muted',
        'nomodule',
        'novalidate',
        'open',
        'playsinline',
        'readonly',
        'required',
        'reversed',
        'selected',
        'spellcheck',
        'translate'
    ];

    private const TAB_SPACES = 2;


    public static function setMode(string $mode): void {
        if (!in_array($mode, [self::MODE_LOOSE, self::MODE_STRICT, self::MODE_SMART])) {
            throw new \InvalidArgumentException("Invalid mode: $mode");
        }
        self::$mode = $mode;
    }

    public static function getMode() {
        return self::$mode;
    }

    /**
     * Document creation that calls `__callStatic('html', ...)` internally,
     * so it also supports named arguments for <html> attributes.
     */
    public static function document(...$args): string {
        // We'll treat this call exactly like a magic call to 'html':
        $html = self::__callStatic('html', $args);

        // Prepend the doctype
        return "<!DOCTYPE html>\n" . $html;
    }

    /**
     * Magic method for tag creation.
     * Now it splits numeric vs. string keys to differentiate children vs. attributes.
     */
    public static function __callStatic(string $tag, array $arguments): string {
        try {
            $timers = [];
            $timers['start'] = microtime(true);

            // 1.4 Tag names must start with letter, then any char except <>
            if (self::$mode === self::MODE_STRICT && !self::isValidTagName($tag)) {
                throw new \InvalidArgumentException("Invalid tag name: $tag");
            }
            // Convert to lowercase in smart and strict modes
            $tag = strtolower($tag);
            $timers['tag_validation'] = microtime(true);

            // Special case for header function collision
            if ($tag === 'header_') $tag = 'header';

            $timers['a_nesting'] = microtime(true);

            $attributes = [];
            $children = [];

            // Split attributes and children, handle attributes validation
            foreach ($arguments as $key => $value) {
                if (is_string($key)) {
                    // 1.6 Boolean attributes validation
                    if (in_array($key, self::BOOLEAN_ATTRIBUTES)) {
                        if (self::$mode === self::MODE_STRICT && !is_bool($value) && $value !== 'true' && $value !== 'false') {
                            throw new \InvalidArgumentException("Boolean attribute '$key' must be true/false/'true'/'false'");
                        }
                        if (self::$mode === self::MODE_SMART && !is_bool($value) && $value !== 'false') {
                            $value = true;
                        }
                        if ($value === false || $value === 'false') {
                            continue; // Skip this attribute
                        }
                        $value = true;
                    }

                    // 1.5 Handle duplicate attributes
                    if (isset($attributes[$key])) {
                        if (self::$mode === self::MODE_STRICT) {
                            throw new \InvalidArgumentException("Duplicate attribute: $key");
                        }
                        if (self::$mode === self::MODE_SMART) {
                            if (is_array($value) && is_array($attributes[$key])) {
                                $attributes[$key] = array_merge($attributes[$key], $value);
                            } else {
                                $attributes[$key] = $attributes[$key] . ' ' . $value;
                            }
                        }
                        continue;
                    }
                    $attributes[$key] = $value;
                } else {
                    $children[] = $value;
                }
            }

            // 1.2 Handle void elements
            if (in_array($tag, self::VOID_TAGS)) {
                return self::html_tag($tag, $attributes, null);
            }

            // Content processing
            $content = implode("\n", array_map(
                fn($child) => is_array($child)
                    ? implode("\n", array_map('strval', $child))
                    : self::convertToString($child),
                $children
            ));
            //other Algo
            /*$content = '';
        foreach ($children as $i => $child) {
            if ($i > 0) $content .= "\n";

            if (is_array($child)) {
                // Handle array of simple values, not nested HTML
                $content .= implode("\n", array_map('strval', $child));
            } else {
                // Handle single value (string/object/etc)
                $content .= self::convertToString($child);
            }
        }
            */
            $timers['content_processing'] = microtime(true);

            // 3.2 Handle nested <a> tags
            if ($tag === 'a') {
                if (strpos($content, '<a') !== false) {
                    if (self::$mode === self::MODE_STRICT) {
                        throw new \InvalidArgumentException("Nested <a> tags are not allowed");
                    }
                    if (self::$mode === self::MODE_SMART) {
                        return self::html_tag($tag, $attributes, '') . $content;
                    }
                }
            }
            // Content model validation (2.1, 2.2)
            if (isset(self::PHRASING_CONTENT_TAGS[$tag]) || $tag === 'p') {
                $pattern = '/<(?!(?:' . implode('|', array_keys(self::PHRASING_CONTENT_TAGS)) . ')\b)[a-z]/i';
                if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    if (self::$mode === self::MODE_STRICT) {
                        throw new \InvalidArgumentException("$tag can only contain phrasing content, got <code style='background-color:lightgray'>" . htmlspecialchars($content) . "</code>");
                    }
                    if (self::$mode === self::MODE_SMART && $tag === 'p') {
                        // Split at first non-phrasing element
                        $pos = $matches[0][1];
                        $beforeBlock = substr($content, 0, $pos);
                        $blockAndAfter = substr($content, $pos);

                        return self::html_tag($tag, $attributes, $beforeBlock) .
                            $blockAndAfter .
                            self::html_tag($tag, [], '');
                    }
                }
            }
            $timers['content_validation'] = microtime(true);

            // Generate final HTML
            $newline = array_key_exists($tag, self::PHRASING_CONTENT_TAGS) ? '' : "\n";
            $result = self::html_tag($tag, $attributes, $content, $newline, 0);
            $timers['html_generation'] = microtime(true);

            // Calculate and log timings
            foreach ($timers as $phase => $time) {
                if ($phase === 'start') continue;
                $prevPhase = array_keys($timers)[array_search($phase, array_keys($timers)) - 1];
                $duration = ($time - $timers[$prevPhase]) * 1000;
                self::$totalTimes[$phase] = (self::$totalTimes[$phase] ?? 0) + $duration;
            }

            return $result;
        } catch (\Exception $e) {
            return "<strong style='padding: 1px 6px;border-radius: 6px;border: 1px solid #ccc;font: caption;font-weight: 700;background-color: #fcc;'>" . "Error" . "</strong>" . $e->getMessage();
        }
    }


    /**
     * The unified tag printer
     */
    private static function html_tag(
        string $tag,
        array $attributes = [],
        ?string $content = '',
        string $newline = '',
        int $depth = 0
    ): string {
        $timers = [];
        $timers['start'] = microtime(true);

        // Indentation
        $indentation = str_repeat(' ', self::TAB_SPACES * $depth);
        $timers['indentation'] = microtime(true);

        // Build attribute string
        $attributeString = self::build_attributes($attributes);
        $timers['attributes'] = microtime(true);

        // Opening tag
        $openTag = $indentation . "<$tag" . ($attributeString ? ' ' . $attributeString : '');
        $timers['opening_tag'] = microtime(true);

        // For void elements: <img ... />
        if (in_array($tag, self::VOID_TAGS, true)) {
            return $openTag . ' />';
        }

        // Normal elements: <tag>content</tag>
        $str = $openTag . '>';

        if ($newline && $content !== '') {
            // Indent multiline content
            $contentTimer = microtime(true);
            $indentedContent = self::indent_content($content, $depth + 1);
            $timers['content_indent'] = microtime(true);
            $str .= $newline . $indentedContent . $newline . $indentation;
        } else {
            $str .= $content;
        }
        $str .= "</$tag>" . $newline;
        $timers['final_assembly'] = microtime(true);

        // Calculate and log timings
        foreach ($timers as $phase => $time) {
            if ($phase === 'start') continue;
            $prevPhase = array_keys($timers)[array_search($phase, array_keys($timers)) - 1];
            $duration = ($time - $timers[$prevPhase]) * 1000;
            self::$totalTimes["html_tag_$phase"] = (self::$totalTimes["html_tag_$phase"] ?? 0) + $duration;
        }

        return $str;
    }

    /**
     * Build the attribute string
     */
    private static function build_attributes(array $attributes): string {
        if (!$attributes) {
            return '';
        }
        return implode(
            ' ',
            array_filter(
                array_map(
                    fn($key, $val) => self::attr($key, $val),
                    array_keys($attributes),
                    $attributes
                )
            )
        );
    }

    /**
     * Convert a key=>value pair to a valid HTML attribute
     */
    private static function attr(string $key, mixed $value): string {
        if (is_bool($value)) {
            // <input disabled> or omit it
            return $value ? $key : '';
        }
        if (is_array($value)) {
            // Typically for class => ['btn','btn-primary']
            $value = self::buildClassAttribute($value);
        }
        $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        return "$key=\"$value\"";
    }

    private static function buildClassAttribute(array|string $classes): string {
        if (is_array($classes)) {
            return implode(' ', array_filter($classes));
        }
        return $classes;
    }

    /**
     * Indent multiline content
     */
    private static function indent_content(string $content, int $depth): string {
        if (!$content) return '';


        $start = microtime(true);
        $indentation = self::getIndent($depth);


        if (strpos($content, "\n") === false) {
            return $indentation . $content;
        }

        $start = microtime(true);
        $result = $indentation . str_replace("\n", "\n" . $indentation, $content);


        return $result;
    }

    private static function getIndent(int $depth): string {
        return self::$indentCache[$depth] ??= str_repeat(' ', self::TAB_SPACES * $depth);
    }

    private static function convertToString(mixed $value): string {
        if (is_scalar($value) || is_null($value)) {
            return (string)$value;
        }
        if ($value instanceof \stdClass) {
            return implode("\n", (array)$value);
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }
        throw new \InvalidArgumentException(
            'Content must be scalar, null, stdClass, or object with __toString method'
        );
    }

    //efficient;<2% execution time. cache does not worth it; it adds memory consumtion.
    private static function isValidTagName(string $tag): bool {
        $result = preg_match('/^[a-zA-Z][^<>]*$/', $tag);
        return $result;
    }
    public static function printTimings(): void {
        echo "\nProfiling Results:\n";
        foreach (self::$totalTimes as $phase => $time) {
            printf("%-20s: %.2f ms\n", $phase, $time);
        }
    }
    public static function resetTimings(): void {
        self::$totalTimes = [];
    }
}