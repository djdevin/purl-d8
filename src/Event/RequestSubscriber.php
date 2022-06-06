<?php

namespace Drupal\purl\Event;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\purl\Entity\Provider;
use Drupal\purl\MatchedModifiers;
use Drupal\purl\Plugin\ModifierIndex;
use Drupal\purl\Plugin\Purl\Method\RequestAlteringInterface;
use Drupal\purl\PurlEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestSubscriber implements EventSubscriberInterface
{
  /**
   * @var ModifierIndex
   */
  protected $modifierIndex;

  /**
   * @var MatchedModifiers
   */
  protected $matchedModifiers;

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(
    ModifierIndex $modifierIndex,
    MatchedModifiers $matchedModifiers,
    ModuleHandlerInterface $moduleHandler
  )
  {
    $this->modifierIndex = $modifierIndex;
    $this->matchedModifiers = $matchedModifiers;
    $this->moduleHandler = $moduleHandler;
  }

  public static function getSubscribedEvents()
  {
    return array(
      // RouterListener comes in at 32. We need to go before it.
      KernelEvents::REQUEST => array('onRequest', 50),
    );
  }

  /**
   * @return \Drupal\purl\Modifier[]
   */
  protected function getModifiers()
  {
    return $this->modifierIndex->findAll();
  }

  protected function getMethodForProvider($providerId)
  {
    return Provider::load($providerId)->getMethodPlugin();
  }

  public function onRequest(GetResponseEvent $event, $eventName, EventDispatcherInterface $dispatcher)
  {
    $request = $event->getRequest();
    $modifiers = $this->getModifiers();

    $matches = array();

    foreach ($modifiers as $modifier) {

      $provider = $modifier->getProvider();
      $modifierKey = $modifier->getModifierKey();
      $method = $modifier->getMethod();

      if ($method->contains($request, $modifierKey)) {
        $matches[$provider->getProviderId()] = array(
          'method' => $method,
          'modifier' => $modifierKey,
          'provider_key' => $provider->getProviderId(),
          'provider' => $modifier->getProvider(),
          'value' => $modifier->getValue()
        );
      }
    }

    $this->moduleHandler->alter('purl_matched_modifiers', $matches);

    foreach ($matches as $match) {

      if ($match['method'] instanceof RequestAlteringInterface) {
        $oldrequest = $request;
        $request = $request->duplicate();
        $newrequest = $match['method']->alterRequest($request, $match['modifier']);
        if (!$newrequest) {
          $request = $oldrequest;
        }
      }
    }

    foreach ($matches as $match) {
      $previous_modifiers = $this->matchedModifiers->getMatched();
      if (count($previous_modifiers)) {
        /** @var ModifierMatchedEvent $previous */
        $previous = end($previous_modifiers);
        if ($previous->getProvider() == $match['provider_key']
          && $previous->getModifier() == $match['modifier']
          && $previous->getValue() == $match['value']
        ) {
          return;
        }
      }
      $modifier_event = new ModifierMatchedEvent(
        $request,
        $match['provider_key'],
        $match['method'],
        $match['modifier'],
        $match['value']
      );
      $dispatcher->dispatch(PurlEvents::MODIFIER_MATCHED, $modifier_event);
      $this->matchedModifiers->add($modifier_event);
      $request->attributes->set('purl.matched_modifiers', $matches);

      if ($match['method'] instanceof RequestAlteringInterface && $newrequest) {
        $response = $event->getKernel()
          ->handle($request, HttpKernelInterface::SUB_REQUEST);
        if ($response) {
          $event->setResponse($response);
        }
      }
    }
  }

}
