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
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handle_parser_cache_use');
   
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

    /**
     * Invalidate the cache of tag listings if needed
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return bool              If the cache may be used
     */
    public function handle_parser_cache_use(Doku_Event &$event, $param) {
        global $conf;
        /** @var $cache cache_parser */
        $cache = $event->data;

        if ($cache->mode == 'i') return true; // don't handle instructions

        $id = $cache->page;
        if (!$id) {
            // try to reconstruct the id from the filename
            $path = $cache->file;
            if (strpos($path, $conf['datadir']) === 0) {
                $path = substr($path, strlen($conf['datadir'])+1);
                $id = pathID($path);
            }
        }
        if ($id) {
            $meta = p_get_metadata($id, 'plugin_tagextract_list');
            $cache_mtime = @filemtime($cache->cache);
            $modified_check_needed =  ($cache_mtime < @filemtime($conf['cachedir'].'/purgefile'));

            if (!empty($meta)) {
                $tags = array_keys($meta);

                $indexer = idx_get_indexer();
                $pages = $indexer->lookupKey('plugin_tagextract', $tags);

                foreach ($meta as $tag => $meta_pages) {
                    natsort($pages[$tag]);

                    $used_pages = array();

                    foreach ($pages[$tag] as $page) {
                        if (page_exists($page) && auth_quickaclcheck($page) >= AUTH_READ && !isHiddenPage($page)) {
                            $used_pages[] = $page;
                            if ($modified_check_needed) {
                                $cache->depends['files'][] = wikiFN($page);
                            }
                        }
                    }

                    if ($used_pages != $meta_pages) {
                        $cache->depends['purge'] = true;
                        $event->stopPropagation();
                        $event->preventDefault();
                        return false;
                    }
                }
            }
        }

        return true;
    }
}

// vim:ts=4:sw=4:et:
