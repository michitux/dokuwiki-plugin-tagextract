<?php
/**
 * DokuWiki Plugin tagextract (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Hamann <michael@content-space.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_tagextract extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler &$controller) {

        $controller->register_hook('INDEXER_PAGE_ADD', 'BEFORE', $this, 'handle_indexer_page_add');
        $controller->register_hook('INDEXER_VERSION_GET', 'BEFORE', $this, 'handle_indexer_version_get');
   
    }

    /**
     * Index the tagextract tag information (which tags were used on the page)
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_indexer_page_add(Doku_Event &$event, $param) {
        $meta = p_get_metadata($event->data['page'], 'plugin_tagextract');
        if (!empty($meta)) {
            $event->data['metadata']['plugin_tagextract'] = $meta;
        } else {
            $event->data['metadata']['plugin_tagextract'] = array();
        }
    }

    /**
     * Set the version of the indexed data of the tagextract plugin
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_indexer_version_get(Doku_Event &$event, $param) {
        $event->data['plugin_tagextract'] = '0.1';
    }
}

// vim:ts=4:sw=4:et:
