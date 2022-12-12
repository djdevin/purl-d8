<?php

namespace Drupal\purl\Entity;

use Drupal\purl\Plugin\Purl\Method\MethodInterface;
use Drupal\purl\Plugin\Purl\Provider\ProviderInterface;

interface ProviderConfigInterface {

  /**
   * @return string
   */
  public function getProviderKey();

  /**
   * @return string
   */
  public function getLabel();

  /**
   * @return string
   */
  public function getMethodKey();

  /**
   * @return MethodInterface
   */
  public function getMethodPlugin();

  /**
   * @return ProviderInterface
   */
  public function getProviderPlugin();

}
