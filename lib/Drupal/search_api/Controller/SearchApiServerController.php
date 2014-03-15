<?php

/**
* @file
* Contains \Drupal\search_api\Controller\SearchApiIndexController.
*
* Small controller to handle Index enabling without confirmation task
*/

namespace Drupal\search_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\search_api\Server\ServerInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchApiServerController extends ControllerBase {

  /**
   * Displays information about a Search API server.
   * 
   * @param \Drupal\search_api\Server\ServerInterface $server
   *   An instance of ServerInterface.
   * 
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(ServerInterface $search_api_server) {
    // Initialize the indexes variable to an empty array. This will hold links
    // to the different attached index config entities.
    $indexes = array(
      '#theme' => 'links',
      '#attributes' => array('class' => array('inline')),
      '#links' => array(),
    );
    // Iterate through the attached indexes.
    foreach ($search_api_server->getIndexes() as $index) {
      // Build and append the link to the index.
      $indexes['#links'][] = array(
        'title' => $index->label(),
        'href' => $index->url('canonical'),
      );
    }
    // Build the Search API server information.
    $render = array(
      'view' => array(
        '#theme' => 'search_api_server',
        '#server' => $search_api_server,
        '#indexes' => $indexes,
      ),
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'search_api') . '/css/search_api.admin.css'
        ),
      ),
    );
    // Check if the server is enabled.
    if ($search_api_server->status()) {
      // Attach the server status form.
      // @todo: Specify the form class for deleting all indexed data.
      //$render['form'] = $this->formBuilder()->getForm('?', $search_api_server);
    }
    return $render;
  }

  public function serverBypassEnable(Request $request, ServerInterface $search_api_server) {
    if (($token = $request->get('token')) && \Drupal::csrfToken()->validate($token, $search_api_server->id())) {

      $search_api_server->setStatus(TRUE)->save();
      $route = $search_api_server->urlInfo();

      return $this->redirect($route['route_name'], $route['route_parameters']);
    }
  }

}