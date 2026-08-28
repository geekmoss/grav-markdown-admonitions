<?php

declare(strict_types=1);

namespace Grav\Plugin\Admonitions;

use Grav\Common\Markdown\BlockResult;
use Grav\Common\Markdown\Element;
use Grav\Common\Markdown\Extension\AbstractMarkdownExtension;
use Grav\Common\Markdown\Extension\BlockCompletableInterface;
use Grav\Common\Markdown\Extension\BlockContinuableInterface;
use Grav\Common\Markdown\Extension\BlockHandlerInterface;
use Grav\Common\Markdown\Extension\MarkdownExtensionRegistry;

/** Markdown extension implementing !!!, ??? and ???+ admonition blocks. */
final class AdmonitionsExtension extends AbstractMarkdownExtension implements
    BlockHandlerInterface,
    BlockContinuableInterface,
    BlockCompletableInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $types;

    private string $rootClass;

    private string $classPrefix;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->rootClass = $this->safeClass((string) ($config['root_class'] ?? 'admonition'), 'admonition');
        $this->classPrefix = $this->safePrefix((string) ($config['class_prefix'] ?? 'admonition--'));
        $this->types = self::mergeTypes($config['types'] ?? []);
    }

    public function getName(): string
    {
        return 'markdown-admonitions';
    }

    public function register(MarkdownExtensionRegistry $registry): void
    {
        $registry->registerBlock('Admonition', '!', $this, [
            'continuable' => true,
            'completable' => true,
            'index' => 0,
        ]);
        $registry->registerBlock('AdmonitionCollapsed', '?', $this, [
            'continuable' => true,
            'completable' => true,
            'index' => 0,
        ]);
    }

    public function block(array $line, ?array $block = null): ?array
    {
        $text = (string) ($line['text'] ?? '');
        if (!preg_match('/^(?<marker>!!!|\?\?\?\+?)\s*(?<type>[A-Za-z][A-Za-z0-9_-]*)(?:\s+(?<title>"(?:\\\\.|[^"\\\\])*"))?\s*$/', $text, $m)) {
            return null;
        }

        $marker = $m['marker'];
        $typeName = strtolower($m['type']);
        $knownType = is_array($this->types[$typeName] ?? null);
        $type = $knownType ? $this->types[$typeName] : [];
        $cssType = $this->safeClass((string) ($type['class'] ?? $typeName), 'unknown');
        $defaultTitle = (string) ($type['label'] ?? ucfirst($typeName));
        $hasTitle = array_key_exists('title', $m) && $m['title'] !== '';
        $title = $hasTitle ? $this->unquote($m['title']) : $defaultTitle;
        $explicitEmpty = $hasTitle && $title === '';
        $collapsible = $marker !== '!!!';
        // A details summary must not be empty; it uses the type label instead.
        if ($collapsible && $explicitEmpty) {
            $title = $defaultTitle;
        }

        $classes = [$this->rootClass, $this->classPrefix . ($knownType ? $cssType : 'unknown')];
        $style = $this->safeAccent((string) ($type['color'] ?? ''));
        $icon = $this->safeIcon((string) ($type['icon'] ?? ''));
        $iconSvg = $this->iconSvg($icon);
        $titleElement = Element::div()
            ->addClass($this->classPrefix . 'title')
            ->attr('data-admonition-title', 'true');
        if ($iconSvg !== null) {
            $titleElement->setChildren([$this->iconElement($iconSvg)->toArray(), Element::span()->setInlineText($this->escapeMarkup($title))->toArray()]);
        } else {
            $titleElement->setInlineText($this->escapeMarkup($title));
        }

        $content = Element::div()
            ->addClass($this->classPrefix . 'content')
            ->attr('data-admonition-content', 'true')
            ->setRawLines([]);
        $contentIndex = 1;
        if ($collapsible) {
            $summary = Element::create('summary')
                ->addClass($this->classPrefix . 'title')
                ->attr('data-admonition-title', 'true');
            $summaryChildren = [];
            if ($iconSvg !== null) {
                $summaryChildren[] = $this->iconElement($iconSvg)->toArray();
                $summaryChildren[] = Element::span()->setInlineText($this->escapeMarkup($title))->toArray();
            } else {
                $summaryChildren[] = Element::span()->setInlineText($this->escapeMarkup($title))->toArray();
            }
            $summaryChildren[] = Element::span()
                ->addClass('admonition__chevron')
                ->setInlineText('›')
                ->toArray();
            $summary->setChildren($summaryChildren);
            $element = Element::create('details')->addClass($classes[0])->addClass($classes[1])->setChildren([
                $summary->toArray(),
                $content->toArray(),
            ]);
            if ($marker === '???+') {
                $element->attr('open', 'open');
            }
        } else {
            $children = [];
            if (!$explicitEmpty) {
                $children[] = $titleElement->toArray();
            }
            $children[] = $content->toArray();
            $contentIndex = count($children) - 1;
            $element = Element::create('div')->addClass($classes[0])->addClass($classes[1])->setChildren($children);
        }
        $element->attr('data-admonition', 'true');
        if ($style !== null) {
            $element->attr('style', '--admonition-accent: ' . $style);
        }

        return BlockResult::fromElement($element)
            ->set('admonition', true)
            ->set('admonition_marker', $marker)
            ->set('admonition_content_index', $contentIndex)
            ->toArray();
    }

    public function blockContinue(array $line, array $block): ?array
    {
        if (empty($block['admonition'])) {
            return null;
        }

        $indent = (int) ($line['indent'] ?? 0);
        $text = (string) ($line['text'] ?? '');
        // Four spaces are the admonition body boundary. Parsedown reports the
        // indentation separately; use body so nested blocks keep their indent.
        if ($text !== '' && $indent < 4) {
            return null;
        }
        $index = (int) ($block['admonition_content_index'] ?? 0);
        if (!isset($block['element']['text'][$index]['text'])) {
            return $block;
        }

        if (isset($block['interrupted'])) {
            foreach (range(1, (int) $block['interrupted']) as $_) {
                $block['element']['text'][$index]['text'][] = '';
            }
            unset($block['interrupted']);
        }

        $block['element']['text'][$index]['text'][] = substr((string) ($line['body'] ?? $text), 4);
        return $block;
    }

    public function blockComplete(array $block): array
    {
        return $block;
    }

    private function unquote(string $title): string
    {
        $title = substr($title, 1, -1);
        return (string) preg_replace_callback('/\\\\([\\"])/', static fn (array $m): string => $m[1], $title);
    }

    private function safeClass(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/i', '-', $value) ?? '';
        return trim($value, '-_') !== '' ? $value : $fallback;
    }

    private function safePrefix(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/i', '-', $value) ?? '';
        return trim($value, '-_') !== '' ? $value : 'admonition--';
    }

    private function safeIcon(string $icon): string
    {
        return preg_replace('/[^a-z0-9 _:+.-]/i', '', $icon) ?? '';
    }

    private function iconSvg(string $icon): ?string
    {
        $icon = strtolower(trim($icon));
        if ($icon === '' || $icon === 'none') {
            return null;
        }

        $files = [
            'note' => 'info-circle.svg', 'info' => 'info-circle.svg',
            'tip' => 'bulb.svg', 'lightbulb' => 'bulb.svg',
            'success' => 'circle-check.svg', 'check' => 'circle-check.svg',
            'warning' => 'triangle-exclamation.svg', 'danger' => 'triangle-exclamation.svg', 'alert' => 'triangle-exclamation.svg',
            'question' => 'circle-question.svg',
            'failure' => 'circle-x.svg', 'close' => 'circle-x.svg',
            'bug' => 'bug.svg', 'example' => 'code.svg', 'code' => 'code.svg',
            'quote' => 'quote.svg', 'abstract' => 'clipboard.svg', 'clipboard' => 'clipboard.svg',
        ];
        $filename = $files[$icon] ?? (preg_match('/^[a-z0-9][a-z0-9._-]*\.svg$/', $icon) ? $icon : null);
        if ($filename === null) {
            return null;
        }

        $path = __DIR__ . '/../assets/icons/' . $filename;
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $svg = file_get_contents($path);
        return is_string($svg) ? $this->sanitizeSvg($svg) : null;
    }

    private function iconElement(string $svg): Element
    {
        return Element::span()->addClass('admonition__icon')->attr('aria-hidden', 'true')->setRawHtml($svg);
    }

    private function sanitizeSvg(string $svg): string
    {
        $svg = preg_replace('/<\/?(?:script|style|foreignObject|iframe|object|embed)[^>]*>/i', '', $svg) ?? '';
        $svg = preg_replace('/\s(on[a-z]+|href|xlink:href)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? '';
        return trim($svg);
    }

    private function safeAccent(string $color): ?string
    {
        return preg_match('/^#[0-9a-f]{3,8}$/i', $color) === 1 ? $color : null;
    }

    private function escapeMarkup(string $value): string
    {
        return str_replace(['<', '>'], ['&lt;', '&gt;'], $value);
    }

    /** @param mixed $configuredTypes @return array<string, array<string, mixed>> */
    private static function mergeTypes(mixed $configuredTypes): array
    {
        $types = self::defaultTypes();
        if (!is_array($configuredTypes)) {
            return $types;
        }

        foreach ($configuredTypes as $name => $settings) {
            if (!is_string($name) || !is_array($settings)) {
                continue;
            }
            $key = strtolower($name);
            $types[$key] = array_replace($types[$key] ?? [], $settings);
        }

        return $types;
    }

    /** @return array<string, array<string, string>> */
    public static function defaultTypes(): array
    {
        $labels = [
            'note' => 'Note', 'info' => 'Info', 'tip' => 'Tip', 'success' => 'Success',
            'warning' => 'Warning', 'danger' => 'Danger', 'question' => 'Question',
            'failure' => 'Failure', 'bug' => 'Bug', 'example' => 'Example',
            'quote' => 'Quote', 'abstract' => 'Abstract',
        ];
        $result = [];
        foreach ($labels as $name => $label) {
            $result[$name] = ['label' => $label, 'class' => $name, 'icon' => $name];
        }
        return $result;
    }
}
