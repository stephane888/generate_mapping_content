<?php

namespace Drupal\generate_mapping_content\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Contenu generer pour le referencement edit forms.
 *
 * @ingroup generate_mapping_content
 */
class ContentGenerateEntityForm extends ContentEntityForm {
  
  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;
  
  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    $instance->messenger = \Drupal::messenger();
    return $instance;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\generate_mapping_content\Entity\ContentGenerateEntity $entity */
    $form = parent::buildForm($form, $form_state);
    //
    $form['mapping']['widget']['#ajax'] = [
      'callback' => '::selectMappingCallback',
      'wrapper' => 'mapping-content-generate-entity',
      'effect' => 'fade'
    ];
    // dump($this->entity->get('introduction')->getSettings());
    //
    $form['fields_mappings'] = [
      '#tree' => TRUE,
      '#prefix' => '<div >',
      '#suffix' => '</div>'
    ];
    $form['#attributes']['id'] = "mapping-content-generate-entity";
    //
    $first = $this->entity->get('mapping')->first();
    //
    if ($first) {
      $value = $first->getValue();
    }
    if ($form_state->hasValue('mapping')) {
      $value = $form_state->getValue('mapping');
      $value = reset($value);
    }
    if (!empty($value['value']))
      $this->displayKey($value['value'], $form['fields_mappings'], $form_state);
    
    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10
      ];
    }
    
    return $form;
  }
  
  public function selectMappingCallback($form, FormStateInterface $form_state) {
    $this->messenger->addStatus("addMoreCallback");
    return $form;
  }
  
  /**
   * Permet d'afficher les clées pour effetuer le remplacement.
   */
  protected function displayKey($plugin_id, &$forms = [], FormStateInterface $form_state) {
    $mappingsStorage = $this->entityTypeManager->getStorage("mappings_entity");
    /**
     *
     * @var \Drupal\generate_mapping_content\Entity\MappingsEntity $mappings
     */
    $mappings = $mappingsStorage->load($plugin_id);
    if ($mappings) {
      $forms = $mappings->getDisplayMappings();
    }
    $inputs = $form_state->getUserInput();
    dump($inputs, $mappings->getDefaultValues());
    foreach ($mappings->getDefaultValues() as $k => $val) {
      if (!empty($inputs[$k][0])) {
        $inputs[$k][0]['value'] = $val;
      }
    }
    $form_state->setUserInput($inputs);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /**
     *
     * @var \Drupal\generate_mapping_content\Entity\ContentGenerateEntity $entity
     */
    $entity = $this->entity;
    // $title = $entity->get('name')->getValue();
    // $title = reset($title);
    // if (!empty($title['value']))
    // dump($entity->search_remplace($title['value']));
    // die();
    
    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();
      
      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }
    
    $status = parent::save($form, $form_state);
    
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Contenu generer pour le referencement.', [
          '%label' => $entity->label()
        ]));
        break;
      
      default:
        $this->messenger()->addMessage($this->t('Saved the %label Contenu generer pour le referencement.', [
          '%label' => $entity->label()
        ]));
    }
    $form_state->setRedirect('entity.content_generate_entity.canonical', [
      'content_generate_entity' => $entity->id()
    ]);
  }
  
}
