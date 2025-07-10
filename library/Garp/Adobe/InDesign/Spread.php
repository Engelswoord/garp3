<?php
/**
 * Garp_Adobe_InDesign_Spread
 * Wrapper around various InDesign related functionality.
 * Note: currently only works with pages that are horizontally laid out on the spread.
 *
 * @package Garp_Adobe_InDesign
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Adobe_InDesign_Spread {

    const PATH = 'Spreads/Spread_%s.xml';

    /**
     * @var array The pages within this spread. Values are Garp_Adobe_InDesign_Page objects.
     *            These are ordered by x-coordinate, where the most left page comes first.
     */
    public $pages = [];

    public $textFrames = [];

    /**
     * @var array An array of Story IDs, where the key is the Page index (number, not Page ID)
     *            where this Story is placed upon.
     *                      array(
     *                          2 => array('eu3f', 'p3fe'),
     *                          3 => array('3ri2')
     *                      )
     */
    public $stories = [];

    protected $_path;

    /**
     * @var SimpleXMLElement $_xml
     */
    protected $_xml;

    /**
     * @var SimpleXMLElement $_spreadNodes
     */
    protected $_spreadNodes;

    public function __construct(protected $_id, protected $_workingDir) {
        $this->_path        = $this->_buildPath($this->_id);
        $spreadContent      = file_get_contents($this->_path);
        $this->_xml         = new SimpleXMLElement($spreadContent);
        $this->_spreadNodes = $this->_xml->Spread->children();

        $this->pages      = $this->_buildPages();
        $this->textFrames = $this->_buildTextFrames();
        $this->stories    = $this->_buildStories();
    }

    public static function getSpreadIdFromPath($path) {
        $spreadId = preg_replace('/.+Spread_(\w+)\.xml/', '$1', (string) $path);
        return $spreadId;
    }


    public function setTextFrameAttribute($storyId, $attribute, $newValue) {
        $newValue = match ($attribute) {
            'FillColor' => 'Color/' . $newValue,
            default => throw new Exception(
                'Setting other Spread TextFrame attributes than ' .
                'FillColor is not supported at the time.'
            ),
        };

        $node = $this->_xml->xpath("//TextFrame[@ParentStory='{$storyId}']");
        if ($node) {
            $node[0]->attributes()->$attribute = $newValue;
        }
        $this->save();
    }


    public function save() {
        if (file_put_contents($this->_path, $this->_xml->asXml()) === false) {
            throw new Exception('Could not write to ' . $this->_path);
        }
    }


    /**
     * Returns a list of stories with the same structure as $this->stories,
     * but only including the stories that have an InDesign tag appended.
     *
     * @return array
     */
    public function getStoriesWithTaggedTextFrames() {
        $filteredStories = [];
        foreach ($this->stories as $pageIndex => $storyNodes) {
            $pageStories = [];

            foreach ($storyNodes as $storyIndex => $storyId) {
                if ($this->_storyHasTaggedTextFrame($storyId)) {
                    $pageStories[$storyIndex] = $storyId;
                }
            }

            if ($pageStories) {
                $filteredStories[$pageIndex] = $pageStories;
            }
        }

        return $filteredStories;
    }


    public function usesStory($storyId) {
        foreach ($this->stories as $stories) {
            if (in_array($storyId, $stories)) {
                return true;
            }
        }
        return false;
    }


    protected function _storyHasTaggedTextFrame($storyId) {
        $filepath       = $this->_workingDir . "Stories/Story_{$storyId}.xml";
        $storyContent   = file_get_contents($filepath);
        $xml            = new SimpleXMLElement($storyContent);
        return (bool)$xml->xpath('//XMLElement');
    }


    protected function _buildPath($spreadId) {
        return $this->_workingDir . sprintf(self::PATH, $spreadId);
    }


    /**
     * This method retrieves the Stories referenced by TextFrame entries on the current Spread,
     * that geometrically map to this page.
     * This has be calculated by geometry, since a TextFrame is not directly linked to a Page,
     * but to a Spread.
     *
     * @return array
     */
    protected function _buildStories() {
        $storiesByPageNumber    = [];
        $storiesByTag           = [];
        $pagesCount             = count($this->pages);

        // Build an array with all textframe positions, divided into the accompanying story XML tags
        foreach ($this->textFrames as $textFrame) {
            $story = new Garp_Adobe_InDesign_Story($textFrame->storyId, $this->_workingDir);
            if ($tag = $story->getTag()) {
                $storiesByTag[$tag][] = [
                    'storyId' => $textFrame->storyId,
                    'x' => $textFrame->x
                ];
            }
        }

        //  sort the stories by horizontal position
        $sortFunction = (fn($storyA, $storyB) => $storyA['x'] <=> $storyB['x']);

        foreach ($storiesByTag as &$stories) {
            usort($stories, $sortFunction);
        }

        //  now divide the stories in pages
        foreach ($storiesByTag as $tagStories) {
            $tagStoriesCount    = count($tagStories);
            $tagStoriesPerPage  = $tagStoriesCount / $pagesCount;

            foreach ($tagStories as $s => $tagStory) {
                $page           = floor($s / $tagStoriesPerPage);
                $pageNumber     = $this->pages[$page]->index;
                $storiesByPageNumber[$pageNumber][] = $tagStory['storyId'];
            }
        }

        return $storiesByPageNumber;
    }

    protected function _buildTextFrames() {
        $textFrames = [];

        foreach ($this->_spreadNodes as $tag => $nodeConfig) {
            switch ($tag) {
            case 'TextFrame':
                $textFrames[] = new Garp_Adobe_InDesign_TextFrame($this->_xml, $nodeConfig);
                break;
            case 'Group':
                foreach ($nodeConfig as $groupNodeTag => $groupNodeValue) {
                    if ($groupNodeTag === 'TextFrame') {
                        $textFrames[] = new Garp_Adobe_InDesign_TextFrame(
                            $this->_xml,
                            $groupNodeValue
                        );
                    }
                }
                break;
            }
        }

        return $textFrames;
    }


    protected function _buildPages() {
        $pages = [];

        foreach ($this->_spreadNodes as $tag => $spreadNode) {
            if ($tag === 'Page') {
                $pages[] = new Garp_Adobe_InDesign_Page($this->_xml, $spreadNode);
            }
        }

        //  sort by x-coordinate
        usort(
            $pages, fn($a, $b) => $a->x <=> $b->x
        );

        return $pages;
    }
}
