<?php

namespace Drupal\purl\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\purl\Plugin\MethodPluginManager;
use Drupal\purl\Plugin\ProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProviderForm extends EntityForm {

  /** @var EntityTypeManagerInterface $entityTypeManager */
  protected $entityTypeManager;

  /**
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity query.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ProviderManager $providerManager, MethodPluginManager $methodManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->providerManager = $providerManager;
    $this->methodManager = $methodManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('purl.plugin.provider_manager'),
      $container->get('purl.plugin.method_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $provider = $this->entity;

    $providerOptions = [];
    $methodOptions = [];

    foreach ($this->providerManager->getDefinitions() as $id => $definition) {
      $providerOptions[$id] = $definition['label'];
    }

    foreach ($this->methodManager->getDefinitions() as $id => $definition) {
      $methodOptions[$id] = $definition['label'];
    }


    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $provider->getLabel(),
      '#description' => $this->t("Label for the provider."),
      '#required' => TRUE,
    ];

    $form['provider_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider Plugin'),
      '#default_value' => $provider->getProviderKey(),
      '#options' => $providerOptions,
      '#disabled' => !$provider->isNew(),
    ];

    $form['method_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Method Plugin'),
      '#default_value' => $provider->getMethodKey(),
      '#options' => $methodOptions,
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $provider = $this->entity;

    $status = $provider->save();

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label provider.', [
        '%label' => $provider->getLabel(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('The %label provider was not saved.', [
        '%label' => $provider->getLabel(),
      ]));
    }

    $form_state->setRedirect('entity.purl_provider.collection');
  }

  public function exist($id) {
    $entities = $this->entityTypeManager->getStorage('purl_provider')
      ->loadByProperties(['provider_key' => $id])
      ->execute();
    return (bool) $entities;
  }

}
