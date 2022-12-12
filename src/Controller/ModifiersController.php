<?php

namespace Drupal\purl\Controller;

use Drupal;
use Drupal\purl\Plugin\ModifierIndex;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ModifiersController extends BaseController {

  /**
   * @var ModifierIndex
   */
  protected $modifierIndex;

  protected $providerManager;

  protected $methodManager;

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('purl.modifier_index')
    );
  }

  public function __construct(
    ModifierIndex $modifierIndex
  ) {

    $this->modifierIndex = $modifierIndex;
  }

  private function stringify($value) {
    // This can be improved a lot more.
    if (is_scalar($value) || is_array($value)) {
      return json_encode($value);
    }
    else {
      return (string) $value;
    }
  }

  public function modifiers(Request $request) {
    $build = [];

    $ids = Drupal::entityQuery('purl_provider')
      ->execute();

    $headers = ['provider', 'modifier', 'value'];

    $headers = array_map(function ($header) {
      return ['data' => t($header)];
    }, $headers);

    $rows = [];

    foreach ($this->modifierIndex->findModifiers() as $modifier) {

      $provider = $modifier->getProvider();

      if (!$provider) {
        continue;
      }

      $row = [];

      $row[] = [
        'data' => $provider->getLabel(),
      ];

      $row[] = [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'code',
          '#value' => $modifier->getModifierKey(),
        ],
      ];

      $row[] = [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'code',
          '#value' => $this->stringify($modifier->getValue()),
        ],
      ];

      $rows[] = $row;
    }


    $build['modifiers'] = [
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    ];

    return $build;
  }

}
