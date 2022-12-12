<?php

namespace Drupal\purl\Plugin\Purl\Provider;

interface ProviderInterface {

  /**
   * @return array
   */
  public function getModifierData();

  public function getProviderId();

  public function getLabel();

}
