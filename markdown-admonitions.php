<?php

declare(strict_types=1);

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Markdown\Extension\MarkdownExtensionRegistry;
use Grav\Common\Plugin;
use Grav\Plugin\Admonitions\AdmonitionsExtension;
use RocketTheme\Toolbox\Event\Event;

/**
 * Adds Markdown admonition blocks to Grav content.
 */
final class MarkdownAdmonitionsPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
            'onAssetsInitialized' => ['onAssetsInitialized', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        /** @var ClassLoader $loader */
        $loader = $this->grav['loader'];
        $loader->addPsr4('Grav\\Plugin\\Admonitions\\', __DIR__ . '/classes', true);
    }

    public function onMarkdownInitialized(Event $event): void
    {
        if (!$this->config->get('plugins.markdown-admonitions.enabled', true)) {
            return;
        }

        $registry = new MarkdownExtensionRegistry($event['markdown'], $event['page']);
        $registry->add(new AdmonitionsExtension((array) $this->config->get('plugins.markdown-admonitions', [])));
    }

    public function onAssetsInitialized(): void
    {
        if (!$this->config->get('plugins.markdown-admonitions.enabled', true)
            || !$this->config->get('plugins.markdown-admonitions.built_in_css', true)) {
            return;
        }

        $this->grav['assets']->addCss('plugin://markdown-admonitions/assets/admonitions.css');
    }
}
