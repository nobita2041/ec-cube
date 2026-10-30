<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\EventSubscriber;

use Customize\Service\QuoteSettings;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BusinessQuoteNavigation implements EventSubscriberInterface
{
    public function __construct(private readonly QuoteSettings $settings)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return ['default_frame.twig' => 'front', '@admin/default_frame.twig' => 'admin'];
    }

    public function front(TemplateEvent $event): void
    {
        if ($this->settings->enabled) {
            $event->addSnippet('BusinessQuote/navigation.twig');
        }
    }

    public function admin(TemplateEvent $event): void
    {
        if ($this->settings->enabled) {
            $event->addSnippet('@admin/BusinessQuote/navigation.twig');
        }
    }
}
