<?php

namespace Drupal\generate_mapping_content\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Stephane888\HtmlBootstrap\ThemeUtility;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MappingsEntityForm.
 */
class MappingsEntityForm extends EntityForm {
  
  /**
   *
   * @var ThemeUtility
   */
  protected $ThemeUtility;
  
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  private static $key_map = 'mapp_';
  private static $arrayKeys = 'mappings_nbr';
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->ThemeUtility = $container->get('generate_style_theme.theme-utility');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->messenger = \Drupal::messenger();
    return $instance;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /**
     *
     * @var \Drupal\generate_mapping_content\Entity\MappingsEntity $mappings_entity
     */
    $mappings_entity = $this->entity;
    // dump($mappings_entity->toArray());
    
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $mappings_entity->label(),
      '#description' => $this->t("Label for the Mappings entity."),
      '#required' => TRUE
    ];
    
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $mappings_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\generate_mapping_content\Entity\MappingsEntity::load'
      ],
      '#disabled' => !$mappings_entity->isNew()
    ];
    
    $form['add_more'] = [
      '#type' => 'submit',
      '#value' => 'Ajouter mapping',
      '#submit' => [
        [
          $this,
          'addMoreSubmit'
        ]
      ],
      '#ajax' => [
        'callback' => '::addMoreCallback',
        'wrapper' => 'generate-mapping-content-mapping-entity',
        'effect' => 'fade'
      ],
      '#description' => ' Vous devez definir deux elements ( par defaut localisation et specialité) '
    ];
    //
    $image = $mappings_entity->get('image');
    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => 'Image',
      '#default_value' => !empty($image) ? $image : [],
      '#upload_location' => 'public://generate-mapping-content'
    ];
    //
    $form['mappings'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="generate-mapping-content-mapping-entity" >',
      '#suffix' => '</div>'
    ];
    $mappings = $mappings_entity->get('mappings');
    // dump($mappings);
    /**
     * //
     */
    if (!$form_state->has(self::$arrayKeys)) {
      $form_state->set(self::$arrayKeys, array_keys($mappings));
    }
    
    $this->buildFieldMappings($mappings, $form, $form_state);
    // On contruit les champs qui doivent contenir le texte formatter.
    $default_values = $mappings_entity->get('default_values');
    // dump($mappings_entity->toArray());
    // $this->ThemeUtility->addContainerTree('default_values', $form, ' Champs ', true, true);
    /**
     *
     * @var \Drupal\generate_mapping_content\ContentGenerateEntityStorage $genrateContentStorage
     */
    $genrateContentStorage = $this->entityTypeManager->getStorage('content_generate_entity');
    
    /**
     *
     * @deprecated : on doit trouver un autre moyen d'avoir la liste de tous les champs par defaut, et voir memem si cest possible.
     * @var \Drupal\Core\Entity\ContentEntityType $generateContent
     */
    $generateContent = $genrateContentStorage->create([
      'mapping' => 'faux_content'
    ]);
    // dump($generateContent->get($property));
    $fields = $generateContent->getFieldDefinitions();
    $form['default_values'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="gen--pping-content-default_values" >',
      '#suffix' => '</div>'
    ];
    foreach ($generateContent::listOverrideValueFields() as $value) {
      if (!empty($fields[$value])) {
        /**
         *
         * @var \Drupal\Core\Field\BaseFieldDefinition $field
         */
        $field = $fields[$value];
        $val = null;
        if (!empty($default_values[$value])) {
          if (!empty($default_values[$value]['value']))
            $val = $default_values[$value]['value'];
          else
            $val = $default_values[$value];
        }
        $form['default_values'][$value] = [
          '#type' => 'textfield',
          '#title' => $field->getLabel(),
          '#default_value' => $val
        ];
        // dump($field->getType());
        if ($field->getType() == 'text_long') {
          $form['default_values'][$value]['#type'] = 'text_format';
        }
      }
    }
    /* You will need additional form elements for your custom properties. */
    return $form;
  }
  
  private function buildFieldMappings(array $mappings, &$form, FormStateInterface $form_state) {
    if ($form_state->has(self::$arrayKeys)) {
      foreach ($form_state->get(self::$arrayKeys) as $indexKey) {
        $value = !empty($mappings[$indexKey]) ? $mappings[$indexKey] : [];
        $this->ThemeUtility->addContainerTree($indexKey, $form['mappings'], 'mapping : ' . $indexKey);
        $wrapper_id = "source-mappings-class" . $indexKey;
        $this->MappingFields($value, $form_state, $form['mappings'][$indexKey], $wrapper_id, $indexKey);
      }
    }
  }
  
  /**
   * Permet juste de retourner une partie du formulaire.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function addMoreCallback($form, FormStateInterface $form_state) {
    // $this->messenger->addStatus(" Ajout d'une reference ");
    return $form['mappings'];
  }
  
  /**
   * Pour l'ajout on ajouter un nouveau index.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function addMoreSubmit($form, FormStateInterface $form_state) {
    // $this->messenger->addStatus('addMoreSubmit');
    if ($form_state->has(self::$arrayKeys)) {
      $mappings_nbr = $form_state->get(self::$arrayKeys);
      $mappings_nbr[] = self::$key_map . (count($mappings_nbr) + 1);
      if (count($mappings_nbr) > 2)
        $this->messenger->addWarning(' Vous ne pouvez definir plus de 2 references ');
      else
        $form_state->set(self::$arrayKeys, $mappings_nbr);
    }
    // Permet de re-executer la construction du formulaire.
    $form_state->setRebuild();
  }
  
  /**
   * Les champs present permette au cron d'evoluer.
   *
   * @param array $default
   * @param FormStateInterface $form_state
   * @param array $form
   * @param string $wrapper_id
   */
  public function MappingFields($default, FormStateInterface $form_state, &$form = [], $wrapper_id = null, $indexKey = null) {
    // $this->messenger->addStatus(' add block : ' . $wrapper_id, true);
    $source = isset($default['source']) ? $default['source'] : null;
    // $this->messenger->addStatus(' key_mapping value : ' . $default['key_mapping'], true);
    $form['key_mapping'] = [
      '#type' => 'textfield',
      '#title' => 'Cle du mapping',
      '#default_value' => isset($default['key_mapping']) ? $default['key_mapping'] : null
    ];
    $form['source'] = [
      '#type' => 'select',
      '#title' => 'Source des données',
      '#options' => [
        'terms' => 'Taxonomie',
        'custom' => 'Données personnalisé'
      ],
      '#required' => true,
      '#default_value' => $source,
      '#ajax' => [
        'callback' => '::selectSourceCallback',
        'wrapper' => $wrapper_id,
        'effect' => 'fade'
      ]
    ];
    $form['source_info'] = [
      '#prefix' => '<div id="' . $wrapper_id . '" >',
      '#suffix' => '</div>'
    ];
    
    if ($source == 'terms') {
      $StorageVocab = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
      $options = [];
      //
      if ($StorageVocab) {
        $vocabs = $StorageVocab->loadMultiple();
        foreach ($vocabs as $vocab) {
          /**
           *
           * @var \Drupal\taxonomy\Entity\Vocabulary $vocab
           */
          $options[$vocab->id()] = $vocab->label();
        }
      }
      //
      $form['source_info']['vocab'] = [
        '#type' => 'select',
        '#title' => 'Selectionner le vocabulaire',
        '#options' => $options,
        '#default_value' => isset($default['source_info']['vocab']) ? $default['source_info']['vocab'] : ''
      ];
      //
      $form['source_info']['lasttid'] = [
        '#type' => 'textfield',
        '#title' => 'Dernier tid',
        '#attributes' => [ // 'readonly' => 'readonly'
        ],
        '#default_value' => isset($default['source_info']['lasttid']) ? $default['source_info']['lasttid'] : ''
      ];
      
      //
      $form['source_info']['status'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => !empty($default['source_info']['status']) ? ' Status : ' . $default['source_info']['status'] : ' En attente ( non demarrer ) '
      ];
    }
    $form['remove_current'] = [
      '#type' => 'submit',
      '#value' => 'Remove mapping',
      '#name' => $indexKey,
      '#weight' => 20,
      '#submit' => [
        '::removeCurrentSubmit'
      ],
      '#ajax' => [
        'callback' => '::RemoveCurrentCallback',
        'wrapper' => 'generate-mapping-content-mapping-entity',
        'effect' => 'fade'
      ]
    ];
  }
  
  protected function optionsStatus() {
    return [
      'running' => 'En cours',
      'end' => 'Terminer'
    ];
  }
  
  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function RemoveCurrentCallback($form, FormStateInterface $form_state) {
    $this->messenger->addStatus(' execution RemoveCurrentCallback ');
    return $form['mappings'];
  }
  
  /**
   * * On supprimer on bloc puis on range les cles.
   * ( objectif avoir des blocs qui se suivent).
   * NB : on doit mettre à jour $form_state['value'] et $form_state['input']
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function removeCurrentSubmit($form, FormStateInterface $form_state) {
    // On recupere l'indice qui doit etre supprimer.
    $select = $form_state->getTriggeringElement();
    array_pop($select['#parents']);
    $currentIndex = array_pop($select['#parents']);
    // On se rassure que le tableaux des ids est disponible.
    if ($form_state->has(self::$arrayKeys)) {
      // Les données par defaut sont dans les variables temporaires.
      $temporaireMappings = $form_state->getValue('mappings');
      $temporaireNewMappings = [];
      $k = 0;
      foreach ($temporaireMappings as $key => $value) {
        if ($key !== $currentIndex) {
          $temporaireNewMappings[self::$key_map . $k] = $value;
          $k++;
        }
      }
      
      // $temporaireNewMappings['mapp_0']['key_mapping'] = 'hummmmmmmmmmmmmmmmmmm update from tempon';
      // dump($temporaireNewMappings);
      $form_state->setValue('mappings', $temporaireNewMappings);
      // on reconstruit le tabbleaux d'index.
      $form_state->set(self::$arrayKeys, array_keys($temporaireNewMappings));
      //
      // On met à jour le tableau des enttrées utilisateurs. (c'est consideré comme les données brutes).
      $inputs = $form_state->getUserInput();
      if (!empty($inputs['mappings'])) {
        $newInputMapping = [];
        $i = 0;
        foreach ($inputs['mappings'] as $key => $value) {
          if ($key !== $currentIndex) {
            $newInputMapping[self::$key_map . $i] = $value;
            $i++;
          }
        }
        $inputs['mappings'] = $newInputMapping;
        $form_state->setUserInput($inputs);
      }
    }
    // on reconstruit le formulaire
    $form_state->setRebuild();
  }
  
  /**
   * On supprimer on bloc puis on range les cles.
   * ( objectif avoir des blocs qui se suivent).
   * Error: de logique
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function removeCurrentSubmitOLd($form, FormStateInterface $form_state) {
    // $this->messenger->addStatus(' execution removeCurrentSubmit ');
    $select = $form_state->getTriggeringElement();
    array_pop($select['#parents']);
    $currentIndex = array_pop($select['#parents']);
    
    if ($form_state->has(self::$arrayKeys)) {
      $mappings_nbr = $form_state->get(self::$arrayKeys);
      $index = array_search($currentIndex, $mappings_nbr);
      $mappings = $this->entity->get('mappings');
      if ($index !== false) {
        if (!empty($mappings[$currentIndex])) {
          $this->messenger->addStatus(' Suppresion du block, index : ' . $index . '; id bloc' . $currentIndex);
          $newMappings = [];
          // dump($mappings, $currentIndex, $index);
          $k = 0;
          foreach ($mappings as $key => $value) {
            if ($key !== $currentIndex) {
              $newMappings[self::$key_map . $k] = $value;
              $k++;
            }
            
            // dump(self::$key_map . $key);
          }
          $this->entity->set('mappings', $newMappings);
        }
        // unset($mappings_nbr[$index]);
        $form_state->set(self::$arrayKeys, array_keys($newMappings));
        // dump($newMappings, $form_state->get(self::$arrayKeys));
      }
    }
    $form_state->setRebuild();
  }
  
  public function selectSourceCallback($form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();
    array_pop($select['#parents']);
    $wrappers_forms = [];
    foreach ($select['#parents'] as $value) {
      if (empty($wrappers_forms))
        $wrappers_forms = $form[$value];
      elseif (!empty($wrappers_forms[$value]))
        $wrappers_forms = $wrappers_forms[$value];
    }
    return $wrappers_forms['source_info'];
  }
  
  public function essaie() {
    return " Essaie numero : " . rand(9, 999);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    \Drupal::messenger()->addStatus('save');
    $mappings_entity = $this->entity;
    $status = $mappings_entity->save();
    //
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t(' Created the %label Mappings entity. ', [
          '%label' => $mappings_entity->label()
        ]));
        break;
      default:
        $this->messenger()->addMessage($this->t('Saved the %label Mappings entity.', [
          '%label' => $mappings_entity->label()
        ]));
    }
    $form_state->setRedirectUrl($mappings_entity->toUrl('collection'));
  }
  
}
