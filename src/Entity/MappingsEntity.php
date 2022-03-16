<?php

namespace Drupal\generate_mapping_content\Entity;

use Drupal\generate_mapping_content\GenerateMappingContentException;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Mappings entity entity.
 *
 * @ConfigEntityType(
 *   id = "mappings_entity",
 *   label = @Translation("Mappings entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\generate_mapping_content\MappingsEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\generate_mapping_content\Form\MappingsEntityForm",
 *       "edit" = "Drupal\generate_mapping_content\Form\MappingsEntityForm",
 *       "delete" = "Drupal\generate_mapping_content\Form\MappingsEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\generate_mapping_content\MappingsEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "mappings_entity",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "content_generate_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "mappings",
 *     "default_values",
 *     "cron_status",
 *     "image",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/mappings_entity/{mappings_entity}",
 *     "add-form" = "/admin/structure/mappings_entity/add",
 *     "edit-form" = "/admin/structure/mappings_entity/{mappings_entity}/edit",
 *     "delete-form" = "/admin/structure/mappings_entity/{mappings_entity}/delete",
 *     "collection" = "/admin/structure/mappings_entity"
 *   }
 * )
 */
class MappingsEntity extends ConfigEntityBundleBase implements MappingsEntityInterface {
  
  /**
   * The Mappings entity ID.
   *
   * @var string
   */
  protected $id;
  
  /**
   * The Mappings entity label.
   *
   * @var string
   */
  protected $label;
  
  /**
   * Liste de données mappées
   *
   * @var array
   */
  protected $mappings = [];
  
  /**
   * Valeur par defaut des champs (avec les variables)
   *
   * @var array
   */
  protected $default_values = [];
  
  /**
   * Status d'execution (true=>Run, false=>Stop)
   *
   * @var boolean
   */
  protected $cron_status = true;
  
  /**
   * -
   *
   * @var array
   */
  protected $image = [];
  
  /**
   * Apres la sauvegrade.
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Entity\ContentEntityBase::postSave()
   */
  public function postSave($storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // save image
    $fid = $this->get('image');
    if (!empty($fid)) {
      $file = \Drupal\file\Entity\File::load($fid[0]);
      $file->setPermanent();
      $file->save();
    }
  }
  
  /**
   * On determine les valeurs qui doivent etre utiliser.
   * On retourne un tableau sous forme de cle valeur.
   * Cas de figure:
   * 1 - On est au tout debut (first run).
   * GetTermeValue va retouner toutes les valeurs de debut via sa premiere condition (lasttid vide).
   * 2 - Si on est au second tour.( second run et les suivant).
   * GetTermeValue va retourner le suivant terme pour le premier bloc, et le meme terme pour les blocs suivants.
   * 3 - Si le premier bloc est vide.
   * on serra dans le cas de figure pas de blocks, // Bloc vide. On met null pour comme tid pour ce bloc.
   * On rcupere la valeur du prochain bloc et on relance la fonction sinon, on arrete.
   */
  public function getReferenceValue($mid = null, &$formatters = [], &$formattersAll = []) {
    $termsMappings = [];
    foreach ($this->mappings as $key => $value) {
      if ($value['source'] == 'terms' && !empty($value['key_mapping'])) {
        $termsMappings[$key] = $value;
      }
    }
    //
    // if ($mid) {
    // // on verifie s'il ya deja des valeurs.
    // }
    // else
    $this->getReferenceTermValue($termsMappings, $formatters, $formattersAll);
    // update mappings
    $this->mappings = $termsMappings;
  }
  
  /**
   * --
   */
  protected function getReferenceTermValue(&$termsMappings, &$formatters, &$formattersAll) {
    $index = 0;
    
    foreach ($termsMappings as $key => $value) {
      
      $val = $this->getTermValue($value['source_info'], $index);
      
      // bloc vide (pas de terme de taxonomie).
      if (empty($val)) {
        $termsMappings[$key]['source_info']['lasttid'] = null;
        // Pour le bloc suivant, on incremente le tid.
        if (!empty($termsMappings['mapp_' . ($index + 1)])) {
          $source_info = $termsMappings['mapp_' . ($index + 1)]['source_info'];
          $tids = $this->getNextTid($source_info['vocab'], $source_info['lasttid']);
          if (!empty($tids)) {
            $termsMappings['mapp_' . ($index + 1)]['source_info']['lasttid'] = reset($tids);
            $this->getReferenceTermValue($termsMappings, $formatters);
            break;
          }
          // Si, le prochain terme est vide, alors on a terminé avec le second bloc.
          $this->stopAndCloseCron();
          $dbug = new GenerateMappingContentException(" Fin de l'execution des terms. ");
          $dbug->setContentToDebug($termsMappings);
          throw $dbug;
        }
        // Si le bloc suivant n'est pas definit, alors cest le dernier block.
        else {
          $this->stopAndCloseCron();
          $dbug = new GenerateMappingContentException(" Fin de l'execution des terms. ");
          $dbug->setContentToDebug($termsMappings);
          throw $dbug;
        }
      }
      else {
        $termsMappings[$key]['source_info']['lasttid'] = $val['tid'];
        $formatters['@' . $value['key_mapping']] = $val['name'];
        $formattersAll[$value['key_mapping']] = $val;
      }
      //
      $index++;
    }
  }
  
  /**
   * Permet de retouner un valeur de terme taxo, en function.
   * il retourne une valeur ou null si on est à la fin d'un parcourt des termes.
   *
   * @param array $infos
   * @param int $index
   * @param boolean $lastIndex
   * @throws \Drupal\generate_mapping_content\GenerateMappingContentException
   * @return NULL|Array
   */
  protected function getTermValue(array $infos, int $index) {
    if (!empty($infos['vocab'])) {
      // S'il nya aucun term.
      if (empty($infos['lasttid'])) {
        $value = $this->loadTermValue($infos['vocab']);
        // Si on ne parvient pas à recuperer la premiere valeur du terme.
        if (empty($value)) {
          $this->stopAndCloseCron();
          $dbug = new GenerateMappingContentException(" Aucun terme disponible dans le vocabulaire : " . $infos['vocab']);
          $dbug->setContentToDebug($infos);
          throw $dbug;
        }
        return $value;
      }
      else {
        // si c'est le premier index, on recupere l'id du terme suivant.
        if (!$index)
          $value = $this->loadTermValue($infos['vocab'], $infos['lasttid']);
        else
          $value = $this->loadCurrentTerm($infos['lasttid']);
        return $value;
      }
    }
    else {
      $this->stopAndCloseCron();
      $dbug = new GenerateMappingContentException(" Le vocubulaire n'est pas definie ");
      $dbug->setContentToDebug($infos);
      throw $dbug;
    }
  }
  
  /**
   * Permet de charger la valeur d'un terme taxo.
   *
   * @return []
   */
  protected function loadTermValue($vocabId, $lastTid = null) {
    if ($lastTid)
      $tids = $this->getNextTid($vocabId, $lastTid);
    else
      $tids = \Drupal::entityQuery('taxonomy_term')->condition('vid', $vocabId)->sort('tid', 'ASC')->range(0, 1)->execute();
    if ($tids) {
      /**
       *
       * @var \Drupal\taxonomy\Entity\Term $term
       */
      $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load(reset($tids));
      return [
        'name' => $term->getName(),
        'tid' => $term->id()
      ];
    }
    return null;
  }
  
  /**
   * Recupere la valeur du prochain node.
   *
   * @param string $vocabId
   * @param int $lastTid
   * @return array
   */
  protected function getNextTid($vocabId, $lastTid) {
    return \Drupal::entityQuery('taxonomy_term')->condition('vid', $vocabId)->condition('tid', $lastTid, '>')->sort('tid', 'ASC')->range(0, 1)->execute();
  }
  
  protected function loadCurrentTerm($tid) {
    /**
     *
     * @var \Drupal\taxonomy\Entity\Term $term
     */
    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($tid);
    if ($term) {
      return [
        'name' => $term->getName(),
        'tid' => $term->id()
      ];
    }
    return null;
  }
  
  /**
   * permet de mettre fin, à l'execution.
   */
  protected function stopAndCloseCron() {
    $this->cron_status = false;
    $this->save();
  }
  
  /**
   * Permet de retourner les mappings pour l'affichage des données.
   */
  public function getDisplayMappings() {
    $html = [];
    foreach ($this->mappings as $value) {
      $ht = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => 'Key => ' . $value['key_mapping'] . '<br>'
        ]
      ];
      if ($value['source'] == 'terms') {
        $ht[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => 'Vocabure => ' . $value['source_info']['vocab']
        ];
      }
      $html[] = $ht;
    }
    return $html;
  }
  
  /**
    * Undocumented function
    *
    * @return void
    */
  public function getDefaultValues() {
    return $this->get('default_values');
  }
  
}