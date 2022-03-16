<?php

namespace Drupal\generate_mapping_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\generate_mapping_content\GenerateMappingContentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\generate_mapping_content\Entity\MappingsEntity;
use Drupal\generate_mapping_content\Entity\ContentGenerateEntity;
use Symfony\Component\HttpFoundation\Request;

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
  public function build(Request $Request, $mapping_id, $numbers, $term_id_1 = null) {
    $showMessage = $Request->query->get('show-message');
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
      ]
    ];
    if ($term_id_1) {
      $values['term1'] = [
        [
          'target_id' => $term_id_1
        ]
      ];
    }
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
    // Check if image is defined
    $fid = $mapping->get('image');
    if (!empty($fid)) {
      $values['image']['target_id'] = $fid[0];
    }
    //
    for ($i = 0; $i < $numbers; $i++) {
      $contentGenerate = ContentGenerateEntity::create($values);
      $contentGenerate->save();
      if ($showMessage) {
        \Drupal::messenger()->addStatus(' ContentGenerate : ' . $contentGenerate->id());
        $term = \Drupal\taxonomy\Entity\Term::load($contentGenerate->get('term2')->target_id);
        if ($term)
          \Drupal::messenger()->addStatus(' taxo nomie ' . $term->getName());
      }
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
