<?php

namespace Drupal\purl;

use Drupal;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\purl\Entity\Provider;
use Drupal\purl\Plugin\ModifierIndex;
use Drupal\purl\Plugin\Purl\Method\MethodInterface;
use Drupal\purl\Plugin\Purl\Method\PreGenerateHookInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class ContextHelper {

  /**
   * @var EntityStorageInterface
   */
  protected $storage;

  public function __construct() {
  }

  /**
   * @param array $contexts
   * @param $path
   * @param array $options
   * @param Request|null $request
   * @param BubbleableMetadata|null $metadata
   *
   * @return mixed
   */
  public function processOutbound(array $contexts, $path, array &$options, Request $request = NULL, BubbleableMetadata $metadata = NULL) {

    $result = $path;


    /** @var Context $context */
    foreach ($contexts as $context) {

      if (!in_array(MethodInterface::STAGE_PROCESS_OUTBOUND, $context->getMethod()->getStages())) {
        continue;
      }

      $contextResult = NULL;

      if ($context->getAction() == Context::ENTER_CONTEXT) {
        $contextResult = $context->getMethod()->enterContext($context->getModifier(), $result, $options);
      }
      elseif ($context->getAction() == Context::EXIT_CONTEXT) {
        $contextResult = $context->getMethod()->exitContext($context->getModifier(), $result, $options);
      }

      $result = $contextResult ?: $result;
    }

    return $result;
  }

  /**
   * @param array $contexts
   * @param $routeName
   * @param array $parameters
   * @param array $options
   * @param $collect_bubblable_metadata
   */
  public function preGenerate(array $contexts, $routeName, array &$parameters, array &$options, $collect_bubblable_metadata) {
    $this->ensureContexts($contexts);

    /** @var Context $context */
    foreach ($contexts as $context) {

      if (!in_array(MethodInterface::STAGE_PRE_GENERATE, $context->getMethod()->getStages()) || !($context->getMethod() instanceof PreGenerateHookInterface)) {
        continue;
      }

      if ($context->getAction() == Context::ENTER_CONTEXT) {
        $context->getMethod()->preGenerateEnter($context->getModifier(), $routeName, $parameters, $options, $collect_bubblable_metadata);
      }
      elseif ($context->getAction() == Context::EXIT_CONTEXT) {
        $context->getMethod()->preGenerateExit($context->getModifier(), $routeName, $parameters, $options, $collect_bubblable_metadata);
      }

    }
  }

  /**
   * @param array $contexts
   *
   * @return bool
   */
  private function ensureContexts(array $contexts) {
    foreach ($contexts as $index => $context) {
      if (!$context instanceof Context) {
        throw new InvalidArgumentException(sprintf('#%d is not a context.', $index + 1));
      }
    }
  }

  /**
   * @param array $map
   *   Provider Id => modifier.
   *
   * @return array
   */
  public function createContextsFromMap(array $map) {
    if (isset($map['id'])) {
      // get the context for a specific purl object
      /** @var ModifierIndex $modifierIndex */
      $modifierIndex = Drupal::service('purl.modifier_index');
      $modifiers = $modifierIndex->getModifiersById($map['id']);
      $mod = reset($modifiers);
      return [new Context(trim($mod->getModifierKey(), '/'), $mod->getMethod())];
    }

    if (count($map) === 0) {
      return [];
    }

    $providers = $this->getStorage()->loadMultiple(array_keys($map));

    return array_map(function (Provider $provider) use ($map) {
      return new Context($map[$provider->id()], $provider->getMethodPlugin());
    }, $providers);
  }

  protected function getStorage(): EntityStorageInterface {
    if (empty($this->storage)) {
      $this->storage = Drupal::entityTypeManager()->getStorage('purl_provider');
    }

    return $this->storage;
  }

}
