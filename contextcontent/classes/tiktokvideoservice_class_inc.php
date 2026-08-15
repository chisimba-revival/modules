<?php
/**
 * Validate TikTok post URLs and build safe hosted-player page content.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   contextcontent
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

/**
 * TikTok video authoring and rendering service.
 *
 * The service stores only an allow-listed TikTok post identifier in the
 * generated iframe URL. Page templates never reproduce arbitrary iframe HTML
 * supplied by an author.
 *
 * @category Chisimba
 * @package  contextcontent
 * @author   Derek Keats <derek@dkeats.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class tiktokvideoservice extends ChisimbaObject
{
    /** @var object Chisimba language service. */
    private $language;

    /**
     * Load the language service.
     *
     * @return void
     */
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
    }

    /**
     * Extract a numeric TikTok post identifier from a supported full URL.
     *
     * Supported inputs include normal post URLs and TikTok player URLs.
     * Short redirect links are deliberately rejected because their target is
     * not stable enough to become stored course content without resolution.
     *
     * @param string $url TikTok post or player URL.
     *
     * @return string|null Numeric post identifier, or null when unsupported.
     */
    public function extractVideoId($url)
    {
        $parts = parse_url(trim((string) $url));
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        if (strtolower($parts['scheme']) !== 'https') {
            return null;
        }
        $host = strtolower(rtrim($parts['host'], '.'));
        if ($host !== 'tiktok.com' && substr($host, -11) !== '.tiktok.com') {
            return null;
        }
        $path = isset($parts['path']) ? $parts['path'] : '';
        $patterns = array(
            '#/(?:@[^/]+/)?video/([0-9]{10,24})(?:/|$)#',
            '#/player/v1/([0-9]{10,24})(?:/|$)#',
            '#/embed(?:/v2)?/([0-9]{10,24})(?:/|$)#',
        );
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path, $match)) {
                return $match[1];
            }
        }
        parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
        foreach (array('item_id', 'video_id') as $key) {
            if (isset($query[$key]) && is_string($query[$key])
                && preg_match('/^[0-9]{10,24}$/', $query[$key])) {
                return $query[$key];
            }
        }
        return null;
    }

    /**
     * Build trusted page-body markup for a TikTok learning item.
     *
     * @param string $url        TikTok post or player URL.
     * @param string $caption    Optional learner-facing caption.
     * @param string $transcript Optional accessible transcript.
     *
     * @return string Safe HTML generated entirely by this service.
     * @throws InvalidArgumentException When the URL is not a supported post.
     */
    public function buildBody($url, $caption = '', $transcript = '')
    {
        $videoId = $this->extractVideoId($url);
        if ($videoId === null) {
            throw new InvalidArgumentException($this->language->languageText(
                'mod_contextcontent_tiktok_invalid_url',
                'contextcontent'
            ));
        }
        $caption = trim((string) $caption);
        $transcript = trim((string) $transcript);
        $escape = function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $playerUrl = 'https://www.tiktok.com/player/v1/' . $videoId
            . '?controls=1&progress_bar=1&play_button=1&volume_control=1'
            . '&fullscreen_button=1&timestamp=1';
        $title = $caption !== '' ? $caption : $this->language->languageText(
            'mod_contextcontent_type_tiktok_video',
            'contextcontent'
        );
        $body = '<div class="contextcontent-tiktok-body" data-tiktok-video-id="'
            . $videoId . '"><figure><div class="contextcontent-tiktok-embed">'
            . '<iframe src="' . $escape($playerUrl) . '" title="'
            . $escape($title) . '" loading="lazy" '
            . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; '
            . 'gyroscope; picture-in-picture; web-share" '
            . 'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>'
            . '</iframe></div>';
        if ($caption !== '') {
            $body .= '<figcaption>' . $escape($caption) . '</figcaption>';
        }
        $body .= '</figure>';
        if ($transcript !== '') {
            $body .= '<details class="contextcontent-tiktok-transcript"><summary>'
                . $escape($this->language->languageText(
                    'mod_contextcontent_tiktok_transcript',
                    'contextcontent'
                )) . '</summary><p>' . nl2br($escape($transcript))
                . '</p></details>';
        }
        return $body . '</div>';
    }
}
