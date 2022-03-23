<?php

namespace Drupal\generate_mapping_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\generate_mapping_content\GenerateMappingContentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\generate_mapping_content\Entity\MappingsEntity;
use Drupal\generate_mapping_content\Entity\ContentGenerateEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    $ContentCreate = [];
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
      $values['image']['all'] = $mapping->label();
    }
    //
    for ($i = 0; $i < $numbers; $i++) {
      $contentGenerate = ContentGenerateEntity::create($values);
      $contentGenerate->save();
      //
      $ContentCreate[] = [
        'id' => $contentGenerate->id(),
        'title' => $contentGenerate->getName()
      ];
    }
    return new JsonResponse($ContentCreate);
  }
  
  /**
   *
   * @param string $mapping_id
   * @throws GenerateMappingContentException
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   */
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
