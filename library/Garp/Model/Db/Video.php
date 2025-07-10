<?php
/**
 * Garp_Model_Db_Video
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class Garp_Model_Db_Video extends Model_Base_Video {
    public function insert(array $data) {
        try {
            return parent::insert($data);
        } catch (Exception $e) {
            if (!str_contains($e->getMessage(), 'Duplicate entry')) {
                throw $e;
            }

            if (!array_key_exists('url', $data) || !$data['url']) {
                throw new Exception("Missing the 'url' parameter in provided video data.");
            }

            $videoUrl = trim((string) $data['url']);
            $this->unregisterObserver('Translatable');

            if ($this->_isVimeoUrl($videoUrl)) {
                $queryUrl = parse_url($videoUrl);
                $queryUrl = $queryUrl['host'] . $queryUrl['path'];
                $select = $this->select()->where('url LIKE ?', "%{$queryUrl}%");
            } elseif ($this->_isYouTuBeUrl($videoUrl) || $this->_isYouTubeComUrl($videoUrl)) {
                $ytVideoId = $this->_getYouTubeIdFromURL($videoUrl);
                $select = $this->select()->where('identifier = ?', $ytVideoId);
            } else throw new Exception("Unknown video type.");

            if (isset($select) && $select) {
                $videoRow = $this->fetchRow($select);
                if ($videoRow) {
                    return $videoRow->id;
                }
            }
        }
        return null;
    }


    protected function _isVimeoUrl($url) {
        return str_contains((string) $url, 'vimeo.com');
    }

    protected function _isYouTuBeUrl($url) {
        return str_contains((string) $url, 'youtu.be');
    }

    protected function _isYouTubeComUrl($url) {
        return str_contains((string) $url, 'youtube.com');
    }

    protected function _getYouTubeIdFromURL($url) {
        $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
        preg_match($pattern, (string) $url, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        throw new Exception("Cannot find YouTube video id in url.");
    }
}
