<?php
/**
 * DokuWiki Plugin tagextract (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Hamann <michael@content-space.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_tagextract_list extends DokuWiki_Syntax_Plugin {
    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'container';
    }
    /**
     * @return string Paragraph type
     */
    public function getPType() {
        return 'block';
    }
    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 300;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{tagextracts>.*?}}',$mode,'plugin_tagextract_list');
    }

    /**
     * Handle matches of the tagextract syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, &$handler){
        $tag = substr($match, 14, -2);

        return array($tag);
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, &$renderer, $data) {
        global $ID;

        list($tag) = $data;

        if ($mode == 'metadata') {
            /** @var $renderer Doku_Renderer_metadata */
            $renderer->meta['plugin_tagextract_list'][$tag] = array();
        }

        /** @var helper_plugin_tagextract $helper */
        $helper = $this->loadHelper('tagextract');

        $indexer = idx_get_indexer();
        $pages = $indexer->lookupKey('plugin_tagextract', $tag);

        // only generate a list if there is actually content to be printed
        $outer_wrapper_opened = false;

        natsort($pages);
        foreach ($pages as $page) {
            if (page_exists($page) && auth_quickaclcheck($page) >= AUTH_READ && !isHiddenPage($page)) {
                if ($mode == 'metadata') {
                    /** @var $renderer Doku_Renderer_metadata */
                    $renderer->meta['plugin_tagextract_list'][$tag][] = $page;
                }

                // set the global $ID to the included page so relative links etc. will work
                // FIXME this might confuse a few syntax plugin, use some include helper function instead
                $oldID = $ID;
                $ID = $page;

                $include_ins = $helper->getTagExtracts($page, $tag);

                // if content will be printed, check for the wrapper and print it if needed
                if (!empty($include_ins) && !$outer_wrapper_opened) {
                    $renderer->listu_open();
                    $outer_wrapper_opened = true;
                }

                // print the included content (and hope it won't break anything...)
                $renderer->nest($include_ins);

                $ID = $oldID;
            }
        }
        if ($outer_wrapper_opened) $renderer->listu_close();

        return true;
    }
}

// vim:ts=4:sw=4:et:
