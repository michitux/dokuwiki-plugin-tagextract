<?php
/**
 * DokuWiki Plugin tagextract (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Hamann <michael@content-space.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_tagextract extends DokuWiki_Plugin {
    /**
     * Return the tag extracts for a certain page (either all or only a specific tag)
     *
     * @param string      $id  The id of the page
     * @param string|null $tag The tag (or null, if all shall be returned)
     * @return array           The array of instructions for the extracts
     */
    public function getTagExtracts($id, $tag = null) {
        $file = wikiFN($id);
        $cache = new cache_parser($id, $file, 'tagextract');
        $instruction_cache = new cache_instructions($id, $file);
        if (!$instruction_cache->useCache() || !$cache->useCache(array('files' => array($instruction_cache->cache)))) {
            $tagextracts = array();

            $instructions = p_cached_instructions($file, false, $id);

            $num_ins = count($instructions);
            foreach ($instructions as $i => $ins) {
                if ($ins[0] == 'plugin' && $ins[1][0] == 'tagextract_tag') {
                    // search listitem_open before and listitem_close of same level after the tag
                    for ($i_before = $i-1; $i_before > 0 && $instructions[$i_before][0] != 'listitem_open'; --$i_before) ;
                    if ($i_before == 0) {
                        // something went wrong, tag used outside of list environment, continue with next instruction
                        continue;
                    }
                    for ($i_after = $i+1, $o_count = 1; $i_after < $num_ins && $o_count > 0; ++$i_after)  {
                        if ($instructions[$i_after][0] == 'listitem_open') ++$o_count;
                        if ($instructions[$i_after][0] == 'listitem_close') --$o_count;
                    }
                    $include_ins = array_slice($instructions, $i_before, $i_after - $i_before);
                    // correct list item levels
                    $base_level = $include_ins[0][1][0];
                    foreach ($include_ins as $ii => $iins) {
                        if ($iins[0] == 'listitem_open') {
                            $include_ins[$ii][1][0] += 2 - $base_level;
                        }
                    }

                    foreach ($ins[1][1] as $t => $uid) {
                        if (isset($tagextracts[$t])) {
                            $tagextracts[$t] = array_merge($tagextracts[$t], $include_ins);
                        } else {
                            $tagextracts[$t] = $include_ins;
                        }
                    }
                }
            }

            foreach ($tagextracts as $t => $extracts) {
                $tagextracts[$t] = array_merge(
                    array(
                        array('listitem_open', array(1)),
                        array('listcontent_open', array()),
                        array('internallink', array($id)),
                        array('listcontent_close', array()),
                        array('listu_open', array())
                    ),
                    $extracts,
                    array(array('listu_close', array()))
                );
            }

            $cache->storeCache(serialize($tagextracts));
        } else {
            $tagextracts = unserialize($cache->retrieveCache(false));
        }

        if (is_null($tag)) return $tagextracts;
        elseif (isset($tagextracts[$tag])) return $tagextracts[$tag];
        else return array();
    }
}

// vim:ts=4:sw=4:et:
