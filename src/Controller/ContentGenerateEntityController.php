<?php

namespace Drupal\generate_mapping_content\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentGenerateEntityController.
 *
 *  Returns responses for Contenu generer pour le referencement routes.
 */
class ContentGenerateEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Contenu generer pour le referencement revision.
   *
   * @param int $content_generate_entity_revision
   *   The Contenu generer pour le referencement revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($content_generate_entity_revision) {
    $content_generate_entity = $this->entityTypeManager()->getStorage('content_generate_entity')
      ->loadRevision($content_generate_entity_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('content_generate_entity');

    return $view_builder->view($content_generate_entity);
  }

  /**
   * Page title callback for a Contenu generer pour le referencement revision.
   *
   * @param int $content_generate_entity_revision
   *   The Contenu generer pour le referencement revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($content_generate_entity_revision) {
    $content_generate_entity = $this->entityTypeManager()->getStorage('content_generate_entity')
      ->loadRevision($content_generate_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $content_generate_entity->label(),
      '%date' => $this->dateFormatter->format($content_generate_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Contenu generer pour le referencement.
   *
   * @param \Drupal\generate_mapping_content\Entity\ContentGenerateEntityInterface $content_generate_entity
   *   A Contenu generer pour le referencement object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ContentGenerateEntityInterface $content_generate_entity) {
    $account = $this->currentUser();
    $content_generate_entity_storage = $this->entityTypeManager()->getStorage('content_generate_entity');

    $langcode = $content_generate_entity->language()->getId();
    $langname = $content_generate_entity->language()->getName();
    $languages = $content_generate_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $content_generate_entity->label()]) : $this->t('Revisions for %title', ['%title' => $content_generate_entity->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all contenu generer pour le referencement revisions") || $account->hasPermission('administer contenu generer pour le referencement entities')));
    $delete_permission = (($account->hasPermission("delete all contenu generer pour le referencement revisions") || $account->hasPermission('administer contenu generer pour le referencement entities')));

    $rows = [];

    $vids = $content_generate_entity_storage->revisionIds($content_generate_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\generate_mapping_content\ContentGenerateEntityInterface $revision */
      $revision = $content_generate_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $content_generate_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.content_generate_entity.revision', [
            'content_generate_entity' => $content_generate_entity->id(),
            'content_generate_entity_revision' => $vid,
          ]));
        }
        else {
          $link = $content_generate_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.content_generate_entity.translation_revert', [
                'content_generate_entity' => $content_generate_entity->id(),
                'content_generate_entity_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.content_generate_entity.revision_revert', [
                'content_generate_entity' => $content_generate_entity->id(),
                'content_generate_entity_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.content_generate_entity.revision_delete', [
                'content_generate_entity' => $content_generate_entity->id(),
                'content_generate_entity_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['content_generate_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
