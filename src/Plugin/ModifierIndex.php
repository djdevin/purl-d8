<?php

namespace Drupal\purl\Plugin;

use Drupal\purl\Entity\Provider;
use Drupal\purl\Modifier;

/**
 * @todo Create caching version by wrapping `getProviderModifiers`
 */
class ModifierIndex
{
  /**
   * @return Modifier[]
   */
  public function findAll()
  {
    return array_reduce(array_map(array($this, 'getProviderModifiers'), Provider::loadMultiple()), 'array_merge', []);
  }
  
  /**
   * @param Provider $provider
   * @return Modifier[]
   */
  public function getProviderModifiers(Provider $provider)
  {
    $modifiers = [];
    
    foreach ($provider->getProviderPlugin()->getModifierData() as $key => $value) {
      $modifiers[] = new Modifier($key, $value, $provider->getMethodPlugin(), $provider);
    }
    
    return $modifiers;
  }
  
  /**
   * Get a list of all modifiers that match a given id.
   *
   * @param int $id
   * @return Modifier[]
   */
  public function getModifiersById($id) {
    /** @var Modifier[] $modifiers */
    static $modifiers = [];
    
    if (empty($modifiers)) {
      $modifiers = $this->findAll();
    }
    
    $selected = [];
    foreach ($modifiers as $m) {
      if ($m->getValue() == $id) {
        $selected[] = $m;
      }
    }
    
    return $selected;
  }
  
  public function findModifiers() {
    return array();
  }
}
