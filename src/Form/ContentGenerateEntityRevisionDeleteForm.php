<?php

namespace Drupal\generate_mapping_content\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Contenu generer pour le referencement revision.
 *
 * @ingroup generate_mapping_content
 */
class ContentGenerateEntityRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Contenu generer pour le referencement revision.
   *
   * @var \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface
   */
  protected $revision;

  /**
   * The Contenu generer pour le referencement storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contentGenerateEntityStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->contentGenerateEntityStorage = $container->get('entity_type.manager')->getStorage('content_generate_entity');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_generate_entity_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.content_generate_entity.version_history', ['content_generate_entity' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $content_generate_entity_revision = NULL) {
    $this->revision = $this->ContentGenerateEntityStorage->loadRevision($content_generate_entity_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->ContentGenerateEntityStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Contenu generer pour le referencement: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Contenu generer pour le referencement %title has been deleted.', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.content_generate_entity.canonical',
       ['content_generate_entity' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {content_generate_entity_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.content_generate_entity.version_history',
         ['content_generate_entity' => $this->revision->id()]
      );
    }
  }

}
