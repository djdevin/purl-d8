<?php

namespace Drupal\purl\PathProcessor;

use Drupal;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\purl\Context;
use Drupal\purl\ContextHelper;
use Drupal\purl\MatchedModifiers;
use Drupal\purl\Modifier;
use Symfony\Component\HttpFoundation\Request;

class PurlContextOutboundPathProcessor implements OutboundPathProcessorInterface {

  /**
   * @var MatchedModifiers
   */
  private $matchedModifiers;

  /**
   * @var ContextHelper
   */
  private $contextHelper;

  public function __construct(MatchedModifiers $matchedModifiers, ContextHelper $contextHelper) {
    $this->matchedModifiers = $matchedModifiers;
    $this->contextHelper = $contextHelper;
  }

  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (array_key_exists('purl_context', $options)) {
      // purl has been set manually
      if (isset($options['purl_context']['id'])) {
        /** @var Modifier[] $modifiers */
        if ($modifiers = Drupal::service('purl.modifier_index')->getModifiersById($options['purl_context']['id'])) {
          $mod = reset($modifiers); // TODO: Some way to make a provider preferred so we can make sure custom domains always take priority
          if ($bubbleable_metadata) {
            $bubbleable_metadata->setCacheContexts(['purl:' . $options['purl_context']['id']]);
          }
          return $this->contextHelper->processOutbound(
            array_merge($this->matchedModifiers->createContexts(Context::EXIT_CONTEXT), [new Context($mod->getModifierKey(), $mod->getMethod())]),
            $path,
            $options,
            $request,
            $bubbleable_metadata
          );
        }
      }
      // forced out of the purl
      if ($options['purl_context'] === FALSE) {
        if ($bubbleable_metadata) {
          $bubbleable_metadata->setCacheContexts(['purl:none']);
        }
        return $this->contextHelper->processOutbound(
          $this->matchedModifiers->createContexts(Context::EXIT_CONTEXT),
          $path,
          $options,
          $request,
          $bubbleable_metadata
        );
      }
    }

    // check if path already has a purl in it.

    if (count($this->matchedModifiers->getMatched()) && $bubbleable_metadata) {
      $cacheContexts = $bubbleable_metadata->getCacheContexts();
      $cacheContexts[] = 'purl';
      $bubbleable_metadata->setCacheContexts($cacheContexts);
    }

    return $this->contextHelper->processOutbound(
      $this->matchedModifiers->createContexts(),
      $path,
      $options,
      $request,
      $bubbleable_metadata
    );
  }

}
