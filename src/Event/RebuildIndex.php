<?php

namespace Drupal\purl\Event;

use Drupal\purl\Plugin\ModifierIndex;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RebuildIndex implements EventSubscriberInterface {

  /**
   * @var ModifierIndex
   */
  protected $modifierIndex;

  /**
   * RebuildIndex constructor.
   *
   * @param ModifierIndex $modifierIndex
   */
  public function __construct(ModifierIndex $modifierIndex) {
    $this->modifierIndex = $modifierIndex;
  }

  public static function getSubscribedEvents() {
    return [
      // RequestSubscriber comes in at 50. We need to go before it.
      KernelEvents::REQUEST => ['onRequest', 51],
    ];
  }

  public function onRequest(GetResponseEvent $event, $eventName, EventDispatcherInterface $dispatcher) {
    return;
    $this->modifierIndex->performDueRebuilds();
  }

}
