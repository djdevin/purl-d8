<?php

namespace Drupal\purl\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PurlModifier
 *
 * @author yourname
 *
 * @Condition(
 *  id = "purl_modifier",
 *  label = @Translation("PURL Modifier")
 * )
 */
class PurlModifier extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  protected $requestStack;

  public function __construct(RequestStack $requestStack, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('request_stack'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public function summary() {
    return t('Any PURL modifier');
  }

  private function getRequest() {
    return $this->requestStack->getCurrentRequest();
  }

  public function evaluate() {
    $modifiers = $this->getRequest()->attributes->get('purl.matched_modifiers', []);
    return count($modifiers) > 0;
  }

}
