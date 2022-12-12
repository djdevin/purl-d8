<?php

namespace Drupal\purl\Menu;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\purl\MatchedModifiers;


/**
 * Provides a couple of menu link tree manipulators.
 *
 * This class provides menu link tree manipulators to:
 * - perform render cached menu-optimized access checking
 * - optimized node access checking
 * - generate a unique index for the elements in a tree and sorting by it
 * - flatten a tree (i.e. a 1-dimensional tree)
 */
class PurlMenuLinkTreeManipulators {

  /**
   * @var MatchedModifiers
   */
  private $matchedModifiers;

  public function __construct(MatchedModifiers $matchedModifiers) {
    $this->matchedModifiers = $matchedModifiers;
  }

  public function contexts(array $tree) {
    /* @var $data MenuLinkTreeElement */
    return $tree;

    foreach ($tree as $data) {
      $link = $data->link;
      $this->contexts($data->subtree);
    }
    exit;

    return $tree;
  }

}
