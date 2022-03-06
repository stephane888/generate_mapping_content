<?php

/**
 * @file
 * Contains content_generate_entity.page.inc.
 *
 * Page callback for Contenu generer pour le referencement entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Contenu generer pour le referencement templates.
 *
 * Default template: content_generate_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_content_generate_entity(array &$variables) {
  // Fetch ContentGenerateEntity Entity Object.
  $content_generate_entity = $variables['elements']['#content_generate_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
