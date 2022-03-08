<?php

namespace Drupal\generate_mapping_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\generate_mapping_content\GenerateMappingContentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\generate_mapping_content\Entity\MappingsEntity;
use Drupal\generate_mapping_content\Entity\ContentGenerateEntity;

/**
 * Returns responses for Generate mapping content routes.
 */
class CronController extends ControllerBase {
  protected $MappingsDatas = null;
  
  public function testPage() {
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!')
    ];
    return $build;
  }
  
  /**
   * Builds the response.
   */
  public function build($mapping_id, $term_id_1, $numbers) {
    /**
     *
     * @var MappingsEntity $mapping
     */
    $mapping = $this->getMappingsDatas($mapping_id);
    $defaults = $mapping->getDefaultValues();
    $values = [
      'mapping' => [
        [
          'value' => $mapping_id
        ]
      ],
      'term1' => [
        [
          'target_id' => $term_id_1
        ]
      ]
    ];
    //
    foreach (ContentGenerateEntity::listOverrideValueFields() as $fielName) {
      if (!empty($defaults[$fielName])) {
        $values[$fielName] = [
          'value' => $defaults[$fielName]
        ];
        if ($fielName !== 'name') {
          $values[$fielName]['format'] = 'text_html';
        }
      }
    }
    for ($i = 0; $i < $numbers; $i++) {
      $contentGenerate = ContentGenerateEntity::create($values);
      $contentGenerate->save();
      // \Drupal::messenger()->addStatus(' ContentGenerate : ' . $contentGenerate->id());
      // $term = \Drupal\taxonomy\Entity\Term::load($contentGenerate->get('term2')->target_id);
      // if ($term)
      // \Drupal::messenger()->addStatus(' taxo nomie ' . $term->getName());
    }
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!')
    ];
    return $build;
  }
  
  protected function getMappingsDatas($mapping_id) {
    if (!$this->MappingsDatas) {
      $this->MappingsDatas = $this->entityTypeManager()->getStorage('mappings_entity');
    }
    $mapping = $this->MappingsDatas->load($mapping_id);
    if ($mapping)
      return $mapping;
    else {
      throw new GenerateMappingContentException("Le plugin id $mapping_id n'est pas definit");
    }
  }
  
}
