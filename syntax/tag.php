<?php
/**
 * DokuWiki Plugin tagextract (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Hamann <michael@content-space.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_tagextract_tag extends DokuWiki_Syntax_Plugin {
    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'substition';
    }
    /**
     * @return string Paragraph type
     */
    public function getPType() {
        return 'normal';
    }
    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 360;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        // the syntax can only be used inside lists
        if ($mode == 'listblock')
            $this->Lexer->addSpecialPattern('(?:@[^\n ]*)+(?=\n)',$mode,'plugin_tagextract_tag');
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
        global $ID;

        $tags = explode(' ', $match);
        $data = array();

        foreach ($tags as $tag) {
            $data[substr($tag, 1)] = substr(md5($ID.'#'.$pos.'#'.$tag), 0, 6); // strip @, generate unique id
        }

        return $data;
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
        if ($mode == 'metadata') {
            /** @var $renderer Doku_Renderer_metadata */
            if (empty($renderer->meta['plugin_tagextract'])) {
                $renderer->meta['plugin_tagextract'] = array_keys($data);
            } else {
                $renderer->meta['plugin_tagextract'] = array_merge($renderer->meta['plugin_tagextract'], array_keys($data));
            }
        } elseif ($mode == 'xhtml') {
            global $ID; // include the id in the link in order to have a link back to this page in the tag extracts listing
            foreach ($data as $tag => $uid) {
                $renderer->doc .= '<a href="'.wl($ID).'#tagextract__'.$uid.'" id="tagextract__'.$uid.'" class="wikilink1">@'.hsc($tag).'</a> ';
            }
        } else {
            foreach ($data as $tag => $uid) {
                $renderer->emphasis_open();
                $renderer->cdata(' @'.$tag.' ');
                $renderer->emphasis_close();
            }
        }
        return true;
    }
}

// vim:ts=4:sw=4:et:
