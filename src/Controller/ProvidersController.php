<?php

namespace Drupal\purl\Controller;

use Drupal;
use Drupal\Core\Url;
use Drupal\purl\Plugin\MethodPluginManager;
use Drupal\purl\Plugin\ModifierIndex;
use Drupal\purl\Plugin\ProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ProvidersController extends BaseController {

  protected $modifierIndex;

  protected $providerManager;

  protected $methodManager;

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('purl.plugin.provider_manager'),
      $container->get('purl.plugin.method_manager'),
      $container->get('purl.modifier_index')
    );
  }

  public function __construct(ProviderManager $providerManager, MethodPluginManager $methodManager, ModifierIndex $modifierIndex) {

    $this->modifierIndex = $modifierIndex;
    $this->providerManager = $providerManager;
    $this->methodManager = $methodManager;
  }

  public function saveProviderSettings(Request $request) {
    if ($request->getMethod() === "POST") {
      $providers = $request->request->get('providers', []);
      foreach ($providers as $providerId => $data) {

        $provider = $this->providerManager->getProvider($providerId);

        if ($data['method']) {
          $this->providerManager->saveProviderConfiguration(
            $providerId,
            $data['method'],
            isset($data['settings']) ? $data['settings'] : []
          );
          $this->modifierIndex->indexModifiers($provider, $data['method']);
        }
        else {
          $this->providerManager->deleteProviderConfiguration($providerId);
          $this->modifierIndex->deleteEntriesByProvider($providerId);
        }
      }
    }

    return $this->redirect('purl.admin');
  }

  public function providers(Request $request) {
    $methods = ['' => sprintf('-- %s --', t('Disabled'))];

    foreach ($this->methodManager->getDefinitions() as $definition) {
      $methods[$definition['id']] = $definition['name'];
    }

    $providers = [];

    $defaultConfig = [
      'method' => NULL,
      'settings' => [],
    ];

    $headers = ['providers', 'methods', 'settings'];
    $rows = [];

    foreach ($this->providerManager->getDefinitions() as $id => $definition) {
      $row = [
        [
          'data' => $definition['name'],
        ],
        [
          'data' => [
            '#theme' => 'select',
            '#value' => $definition['method'],
            '#options' => $methods,
            '#name' => sprintf('providers[%s][method]', $id),
          ],
        ],
        [
          'data' => '',
        ],
      ];
      $rows[] = $row;
    }

    $tableData = [
      '#theme' => 'table',
      '#header' => array_map(function ($header) {
        return ['data' => t($header)];
      }, $headers),
      '#rows' => $rows,
    ];

    $build = [];

    $build['providers_settings_form'] = [
      '#type' => 'html_tag',
      '#tag' => 'form',
      '#attributes' => [
        'method' => 'POST',
        'action' => Url::fromRoute('purl.admin.save_providers_config')->toString(),
      ],
    ];

    $submitData = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'class' => ['button button--primary form-submit'],
        'type' => 'submit',
        'value' => 'Save',
      ],
    ];

    $formContents = [
      'table' => $tableData,
      'submit' => $submitData,
    ];
    $form = Drupal::service('renderer')->render($formContents);

    $build['providers_settings_form']['#value'] = $form;

    return $build;
  }

}
