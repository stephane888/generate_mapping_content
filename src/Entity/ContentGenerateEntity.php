<?php

namespace Drupal\generate_mapping_content\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\generate_mapping_content\GenerateMappingContentDefault;
use Drupal\generate_mapping_content\ContentGenerateEntityStorage;
use Drupal\Component\Render\FormattableMarkup;
use phpDocumentor\Reflection\Types\Parent_;
use Drupal\generate_mapping_content\Entity\MappingsEntity;
use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines the Contenu generer pour le referencement entity.
 *
 * @ingroup generate_mapping_content
 *
 * @ContentEntityType(
 *   id = "content_generate_entity",
 *   label = @Translation("Contenu generer pour le referencement"),
 *   handlers = {
 *     "storage" = "Drupal\generate_mapping_content\ContentGenerateEntityStorage",
 *     "view_builder" = "Drupal\generate_mapping_content\ContentGenerateEntityViewBuilder",
 *     "list_builder" = "Drupal\generate_mapping_content\ContentGenerateEntityListBuilder",
 *     "views_data" = "Drupal\generate_mapping_content\Entity\ContentGenerateEntityViewsData",
 *     "translation" = "Drupal\generate_mapping_content\ContentGenerateEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\generate_mapping_content\Form\ContentGenerateEntityForm",
 *       "add" = "Drupal\generate_mapping_content\Form\ContentGenerateEntityForm",
 *       "edit" = "Drupal\generate_mapping_content\Form\ContentGenerateEntityForm",
 *       "delete" = "Drupal\generate_mapping_content\Form\ContentGenerateEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\generate_mapping_content\ContentGenerateEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\generate_mapping_content\ContentGenerateEntityAccessControlHandler",
 *   },
 *   base_table = "content_generate_entity",
 *   data_table = "content_generate_entity_field_data",
 *   revision_table = "content_generate_entity_revision",
 *   revision_data_table = "content_generate_entity_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer contenu generer pour le referencement entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/content-generate-entity/{content_generate_entity}",
 *     "add-form" = "/admin/structure/content_generate_entity/add",
 *     "edit-form" = "/admin/structure/content_generate_entity/{content_generate_entity}/edit",
 *     "delete-form" = "/admin/structure/content_generate_entity/{content_generate_entity}/delete",
 *     "version-history" = "/admin/structure/content_generate_entity/{content_generate_entity}/revisions",
 *     "revision" = "/admin/structure/content_generate_entity/{content_generate_entity}/revisions/{content_generate_entity_revision}/view",
 *     "revision_revert" = "/admin/structure/content_generate_entity/{content_generate_entity}/revisions/{content_generate_entity_revision}/revert",
 *     "revision_delete" = "/admin/structure/content_generate_entity/{content_generate_entity}/revisions/{content_generate_entity_revision}/delete",
 *     "translation_revert" = "/admin/structure/content_generate_entity/{content_generate_entity}/revisions/{content_generate_entity_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/content_generate_entity",
 *   },
 *   field_ui_base_route = "content_generate_entity.settings"
 * )
 */
class ContentGenerateEntity extends EditorialContentEntityBase implements ContentGenerateEntityInterface {
  
  use EntityChangedTrait;
  use EntityPublishedTrait;
  
  /**
   *
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id()
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    
    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    
    return $uri_route_parameters;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);
      
      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }
    
    // If no revision author has been set explicitly,
    // make the content_generate_entity owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
    
    // Remplace les variables avant la sauvegarde.
    // Les travaux d'isolation de votre maison à @localisation @title @simples et sans surprise.
    /**
     *
     * @var MappingsEntity $mappingDatas
     */
    $mappingDatas = $this->entityTypeManager()->getStorage("mappings_entity")->load($this->get('mapping')->value);
    if ($mappingDatas)
      $this->formattedReference($mappingDatas);
  }
  
  /**
   * Apres la sauvegrade.
   *
   * @param ContentGenerateEntityStorage $storage
   * {@inheritdoc}
   * @see \Drupal\Core\Entity\ContentEntityBase::postSave()
   */
  public function postSave($storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }
  
  /**
   * On determine les valeurs qui doivent etre utiliser.
   *
   * @param MappingsEntity $mappingDatas
   */
  protected function formattedReference(MappingsEntity $mappingDatas) {
    $references = $mappingDatas->getReferenceValue($this->id());
    $mappingDatas->save();
    foreach (self::listOverrideValueFields() as $fieldName) {
      $this->set($fieldName, $this->search_remplace($this->get($fieldName)->value, $references));
    }
  }
  
  /**
   *
   * @param EntityStorageInterface $storage
   * @param array $entities
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // dump(\Drupal::routeMatch()->getParameters());
    // $storageMAppings = \Drupal::entityTypeManager()->getStorage('mappings_entity');
    // foreach ($entities as $k => $entity) {
    // /**
    // *
    // * @var \Drupal\generate_mapping_content\Entity\ContentGenerateEntity $entity
    // */
    // $entities[$k] = self::seresetFormatterField($entity, $storageMAppings);
    // }
  }
  
  /**
   * On remet la valeur non formater dans les champs.
   */
  public function resetFormatterField(ContentGenerateEntity $entity, ConfigEntityStorage $storageMAppings) {
    $mapping = $entity->get('mapping')->first();
    if ($mapping) {
      $mapping = $mapping->getValue();
      $mappingEntity = $storageMAppings->load($mapping['value']);
      $default_values = $mappingEntity->get('default_values');
      foreach ($default_values as $key => $value) {
        if ($entity->hasField($key)) {
          $entity->set($key, $value);
        }
      }
    }
    return $entity;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }
  
  public function search_remplace(string $Content, $locals = []) {
    $FormattableMarkup = new FormattableMarkup($Content, $locals);
    return $FormattableMarkup->__toString();
  }
  
  /**
   * Permet de definir les champs donc les variables vont etre remplacer.
   */
  public static function listOverrideValueFields() {
    return [
      'name',
      'introduction',
      'description'
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    // Add specifis fields.
    $fields['image'] = BaseFieldDefinition::create('image')->setLabel(' Image ')->setRequired(false)->setDisplayConfigurable('form', [
      'type' => 'image'
    ])->setDisplayConfigurable('view', TRUE)->setSetting("min_resolution", "700x450")->setSetting('default_image', [
      'target_id' => 1162,
      'uuid' => '0515a5cc-8591-4f94-960f-7ca48f381a42',
      'width' => 100,
      'height' => 100,
      'alt' => '',
      'title' => ''
    ]);
    //
    $fields['introduction'] = BaseFieldDefinition::create('text_long')->setLabel(" Introduction ")->setRequired(TRUE)->setDisplayConfigurable('view', TRUE)->setDisplayConfigurable('form', [
      'type' => 'string_textfield',
      'format' => 'text_html'
    ])->setDisplayOptions('form', [
      'type' => 'text_textfield',
      'weight' => 0
    ])->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'text_default',
      'weight' => 0
    ])->setDefaultValue(GenerateMappingContentDefault::$introduction);
    //
    $fields['description'] = BaseFieldDefinition::create('text_long')->setLabel(" Description ")->setRequired(TRUE)->setDisplayConfigurable('form', [
      'type' => 'string_textfield'
    ])->setDisplayConfigurable('view', TRUE)->setDefaultValue(GenerateMappingContentDefault::$description);
    //
    $fields['mapping'] = BaseFieldDefinition::create('list_string')->setLabel(" Mapping ")->setRequired(TRUE)->setDisplayConfigurable('form', [
      'type' => 'options_select',
      'settings' => [],
      'weight' => -20
    ])->setDisplayConfigurable('view', TRUE)->setSetting('allowed_values_function', [
      '\Drupal\generate_mapping_content\GenerateMappingContent',
      'listMappings'
    ]);
    
    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    
    //
    $fields['name'] = BaseFieldDefinition::create('string')->setLabel(t('Name'))->setDescription(t('The name of the Contenu generer pour le referencement entity.'))->setRevisionable(TRUE)->setSettings([
      'max_length' => 150, // longeur de caractaire en BD.
      'size' => 100,
      'text_processing' => 0
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4,
      'size' => 100
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE)->setDefaultValue(GenerateMappingContentDefault::$title);
    
    $fields['status']->setDescription(t('A boolean indicating whether the Contenu generer pour le referencement is published.'))->setDisplayOptions('form', [
      'type' => 'boolean_checkbox',
      'weight' => -3
    ]);
    
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Authored by'))->setDescription(t('The user ID of author of the Contenu generer pour le referencement entity.'))->setRevisionable(TRUE)->setSetting('target_type', 'user')->setSetting('handler', 'default')->setTranslatable(TRUE)->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'author',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 5,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => '60',
        'autocomplete_type' => 'tags',
        'placeholder' => ''
      ]
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    
    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));
    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')->setLabel(t('Revision translation affected'))->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))->setReadOnly(TRUE)->setRevisionable(TRUE)->setTranslatable(TRUE);
    return $fields;
  }
  
}