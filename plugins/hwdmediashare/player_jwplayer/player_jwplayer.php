<?php
/**
 * @package     Joomla.site
 * @subpackage  Plugin.hwdmediashare.player_jwplayer
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

class plgHwdmediasharePlayer_JwPlayer extends JObject
{
	/**
	 * Class constructor.
	 *
	 * @access  public
         * @return  void
	 */
	public function __construct()
	{
		parent::__construct();
	}
        
	/**
	 * Returns the plgHwdmediasharePlayer_JwPlayer object, only creating it if it
	 * doesn't already exist.
	 *
	 * @access  public
	 * @return  object  The plgHwdmediasharePlayer_JwPlayer object.
	 */
	public static function getInstance()
	{
		static $instance;

		if (!isset ($instance))
                {
			$c = 'plgHwdmediasharePlayer_JwPlayer';
                        $instance = new $c;
		}

		return $instance;
	}
            
        /**
	 * Method to preload any assets for this player.
         * 
         * @access  public
	 * @param   JRegistry  $params  The plyaer plugin parameters.
         * @return  void
	 **/
	public function preloadAssets($params)
	{
                // Initialise variable.
                $app = JFactory::getApplication();
                $doc = JFactory::getDocument();

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Add page assets.
                JHtml::_('bootstrap.framework');
                if ($params->get('hosting') == 'cloud')
                {
                        // Check if the user has entered the full script tag.
                        if (preg_match('/< *script[^>]*src *= *["\']?([^"\']*)/i', $params->get('cloud_host_key'), $matches))
                        {
                                list ($match, $src) = $matches;
                                $doc->addScript($src);                        
                        }
                        else
                        {
                                // Check if the user has entered the full URL.
                                if (filter_var($params->get('cloud_host_key'), FILTER_VALIDATE_URL))
                                { 
                                        $doc->addScript($params->get('cloud_host_key'));
                                }
                                else
                                {
                                        $doc->addScript('https://jwpsrv.com/library/' . $params->get('cloud_host_key', '1DQjeAHxEeSEyiIACyaB8g') . '.js');
                                }    
                        }
                }
                elseif ($params->get('hosting') == 'self')
                {
                        $doc->addScript(JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.js');
                        $doc->addScriptDeclaration('jwplayer.key="' . $params->get('self_host_key', 'bAwF3Hdn7fiSh7YpWL1YopRWDNTTd1bMHyE9Sg==') . '";'); 
                }
                elseif ($params->get('hosting') == 'cloud7')
                {
                        // Check if the user has entered the full script tag.
                        if (preg_match('/< *script[^>]*src *= *["\']?([^"\']*)/i', $params->get('cloud_host_key'), $matches))
                        {
                                list ($match, $src) = $matches;
                                $doc->addScript($src);                        
                        }
                        else
                        {
                                // Check if the user has entered the full URL.
                                if (filter_var($params->get('cloud_host_key'), FILTER_VALIDATE_URL))
                                { 
                                        $doc->addScript($params->get('cloud_host_key'));
                                }
                                else
                                {
                                        $doc->addScript('https://content.jwplatform.com/libraries/' . $params->get('cloud_host_key', 'TUbWc0by') . '.js');
                                }    
                        }
                }
                elseif ($params->get('hosting') == 'self7')
                {
                        $doc->addScript(JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer7/jwplayer.js');
                        $doc->addScriptDeclaration('jwplayer.key="' . $params->get('self_host_key', 'LaMVzynr6n2wuxsDzRfLdxSS8HfOaqMy06ICEg==') . '";'); 
                }
        }

        /**
	 * Method to render the player for a video.
         * 
	 * @access  public
	 * @param   object     $item     The item being displayed.
	 * @param   JRegistry  $sources  Media sources for the item.
	 * @return  void
	 **/
	public function getVideoPlayer($item, $sources)
	{
		// Initialise variables.
                $app = JFactory::getApplication();

                // Get HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Load plugin.
		$plugin = JPluginHelper::getPlugin('hwdmediashare', 'player_jwplayer');
		
                // Load the language file.
                $lang = JFactory::getLanguage();
                $lang->load('plg_hwdmediashare_player_jwplayer', JPATH_ADMINISTRATOR, $lang->getTag());

                if (!$plugin)
                {
                        $this->setError(JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_ERROR_NOT_PUBLISHED'));
                        return false;
                }

                // Load parameters.
                $params = new JRegistry($plugin->params);
                
                // Support quality toggle feature of JWPlayer
                $mp4_qualities = array();
                $resolution_array = array(14 => '360', 15 => '480', 16 => '720', 17 => '1080', );
                if (isset($item->mediafiles) && is_array($item->mediafiles))
                {
                        // We want to construct our array in ascending order, as this will be how it displays
                        // within the player window.
                        foreach(array(14, 15, 16, 17) as $file_type)
                        {                    
                                foreach($item->mediafiles as $file)
                                {
                                        if ($file->file_type == $file_type)
                                        {                            
                                                $quality = new stdClass;                                
                                                $quality->url = hwdMediaShareDownloads::url($item, $file->file_type);
                                                $quality->label = JText::_('COM_HWDMS_PLAYER_LABEL_FILETYPE_' . $file->file_type);
                                                $quality->default = hwdMediaShareVideos::getResolutionFromFileType($file->file_type) == hwdMediaShareVideos::getQuality() ? 'true' : 'false';

                                                $mp4_qualities[] = $quality; 
                                        }
                                } 
                        }
                }
                
                // The MP4 may not be local, so we do a little check here. This is messy, but people want the quality toggle.
                if (count($mp4_qualities) && $sources->get('mp4'))
                {
                        $sources->set('mp4', false);
                }
                 
                // Preload assets.
                $this->preloadAssets($params);
                               
                ob_start();
                ?>
<div class="media-respond" style="max-width:<?php echo $config->get('mediaitem_size', '500'); ?>px;">
  <div class="media-aspect" data-aspect="<?php echo $config->get('video_aspect', '0.75'); ?>"></div>
  <div class="media-content">
    <div id="media-jwplayer-<?php echo $item->id; ?>"><?php echo JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_LOADING_PLAYER'); ?></div>
  </div>
</div>
<script type="text/javascript">
    jwplayer("media-jwplayer-<?php echo $item->id; ?>").setup({
        // Sources.
        sources: [
            { 'file': 'dummy', type: 'none' } // Dummy source to prevent trailing commas.
            <?php echo ($sources->get('mp4') ?  ",{ 'file': '".$sources->get('mp4')->url."', type: 'mp4', label: 'HD MP4' }    // H.264 version\n" : ''); ?>
            <?php foreach($mp4_qualities as $mp4_quality) : echo ",{ 'file': '".$mp4_quality->url."', type: 'mp4', label: '".$mp4_quality->label."', \"default\": ".$mp4_quality->default." }\n"; endforeach; ?>
            <?php echo ($sources->get('webm') ? ",{ 'file': '".$sources->get('webm')->url."', type: 'webm', label: 'HD WEBM' } // WebM version\n" : ''); ?>
            <?php echo ($sources->get('ogg') ?  ",{ 'file': '".$sources->get('ogg')->url."', type: 'vorbis', label: 'HD OGG' } // Ogg Theora version\n" : ''); ?>
            <?php echo ($sources->get('flv') ?  ",{ 'file': '".$sources->get('flv')->url."', type: 'flv', label: 'HD FLV' }    // Flash version\n" : ''); ?>
        ],
        <?php if (file_exists(JPATH_ROOT . '/images/webvtt/captions-' . $item->id. '.vtt') || file_exists(JPATH_ROOT . '/images/webvtt/chapters-' . $item->id. '.vtt')) : ?>
        tracks: [
            <?php if (file_exists(JPATH_ROOT . '/images/webvtt/captions-' . $item->id. '.vtt')) : ?>
                { // WebVTT Captions.
                'file': '<?php echo JURI::root( true ); ?>/images/webvtt/captions-<?php echo $item->id; ?>.vtt', 
                'label': 'English',
                'kind': 'captions',
                'default': true
                },
            <?php endif; ?>
            <?php if (file_exists(JPATH_ROOT . '/images/webvtt/chapters-' . $item->id. '.vtt')) : ?>
                { // WebVTT Chapters.
                'file': '<?php echo JURI::root( true ); ?>/images/webvtt/chapters-<?php echo $item->id; ?>.vtt', 
                'label': 'English',
                'kind': 'chapters',
                'default': true
                },
            <?php endif; ?>
        ],
        <?php endif; ?>
   
        // Basic options.
        /** aspectratio: "16:9", // We don't set this because code from HWD manages the responsiveness of the player. */
        autostart: <?php echo $config->get('media_autoplay') ? 'true' : 'false'; ?>,
        controls: true,
        height: "100%",
        image: "<?php echo hwdMediaShareThumbnails::getVideoPreview($item); ?>",
        mute: false,
        primary: "<?php echo $config->get('fallback') == 3 ? 'flash' : 'html5'; ?>",
        repeat: false,
        <?php if ($params->get('hosting') == 'self7') : ?>
        skin: {
            name: "<?php echo $params->get('skin_custom') ? basename($params->get('skin_custom'), '.css') : $params->get('skin', 'seven'); ?>"
            <?php echo $params->get('skin_custom') ? ', url: "' . $params->get('skin_custom') . '"' : ''; ?>
        },
        <?php elseif ($params->get('hosting') == 'cloud' || $params->get('hosting') == 'self') :  ?>   
        skin: "<?php echo $params->get('hosting') == 'cloud' ? '' : ($params->get('skin_custom') ? $params->get('skin_custom') : JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer-skins/' . $params->get('skin', 'six') . '.xml'); ?>",
        <?php endif; ?>
        width: "100%",
        androidhls: false,
        stretching: "<?php echo $params->get('stretching', 'uniform'); ?>",
        // Logo block.
        <?php if ($params->get('logofile') != '') : ?>
        logo: {
            file: '<?php echo JURI::root(); ?><?php echo $params->get('logofile'); ?>',
            link: '<?php echo $params->get('logolink'); ?>',
            hide: '<?php echo $params->get('logohide'); ?>',
            margin: '<?php echo $params->get('logomargin'); ?>',
            position: '<?php echo $params->get('logoposition'); ?>'
        },
        <?php endif; ?>
        <?php if ($params->get('videoadsclient') != '') : ?>
        advertising: {
          client: '<?php echo $params->get('videoadsclient', 'vast'); ?>',
          tag: '<?php echo $params->get('videoadstag', ''); ?>'
        },
        <?php endif; ?>         
        <?php if ($params->get('hosting') == 'self') : ?>
        flashplayer: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.flash.swf'; ?>', 
        html5player: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.html5.js'; ?>',
        <?php endif; ?>         
        abouttext: '<?php echo $params->get('abouttext', 'HWD MediaShare for Joomla'); ?>',
        aboutlink: '<?php echo $params->get('aboutlink', 'http://hwdmediashare.co.uk'); ?>'
    });
</script>
                <?php
                $html = ob_get_contents();
                ob_end_clean();
                
                return $html;            
        }   

        /**
	 * Method to render the player for an audio
         * 
	 * @access  public
	 * @param   object     $item     The item being displayed.
	 * @param   JRegistry  $sources  Media sources for the item.
	 * @return  void
	 **/
	public function getAudioPlayer($item, $sources)
	{
		// Initialise variables.
                $app = JFactory::getApplication();

                // Get HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Load plugin.
		$plugin = JPluginHelper::getPlugin('hwdmediashare', 'player_jwplayer');
		
                // Load the language file.
                $lang = JFactory::getLanguage();
                $lang->load('plg_hwdmediashare_player_jwplayer', JPATH_ADMINISTRATOR, $lang->getTag());

                if (!$plugin)
                {
                        $this->setError(JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_ERROR_NOT_PUBLISHED'));
                        return false;
                }

                // Load parameters.
                $params = new JRegistry($plugin->params);
                   
                // Preload assets.
                $this->preloadAssets($params);
                        
                switch ($params->get('skin'))
                {
                        case 'six':
                        case 'beelden':
                        case 'bekle':
                        case 'five':
                        case 'roundster':
                                $height = 30;
                        break;
                        case 'glow':
                                $height = 27;
                        break;
                        case 'stormtrooper':
                                $height = 25;
                        break;
                        case 'vapor':
                                $height = 35;
                        break;                
                        default:
                                $height = 30;
                }
                ob_start();
                ?>
<div class="media-respond" style="max-width:<?php echo $config->get('mediaitem_size', '500'); ?>px;">
  <div id="media-jwplayer-<?php echo $item->id; ?>"><?php echo JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_LOADING_PLAYER'); ?></div>
</div>
<script type="text/javascript">
    jwplayer("media-jwplayer-<?php echo $item->id; ?>").setup({
        // Sources.
        sources: [
            { 'file': 'dummy', type: 'none' } // Dummy source to prevent trailing commas.
            <?php echo ($sources->get('mp3') ? ",{ 'file': '".$sources->get('mp3')->url."', type: 'mp3', label: 'MP3' } // MP3 version\n" : ''); ?>
            <?php echo ($sources->get('ogg') ? ",{ 'file': '".$sources->get('ogg')->url."', type: 'ogg', label: 'OGG' } // OGG version\n" : ''); ?>
        ],
        // Basic options.
        /** aspectratio: "16:9", // We don't set this because code from HWD manages the responsiveness of the player. */
        autostart: <?php echo $config->get('media_autoplay') ? 'true' : 'false'; ?>,
        controls: true,
        height: "<?php echo $height; ?>",
        image: "",
        mute: false,
        primary: "<?php echo $config->get('fallback') == 3 ? 'flash' : 'html5'; ?>",
        repeat: false,
        <?php if ($params->get('hosting') == 'self7') : ?>
        skin: {
            name: "<?php echo $params->get('skin_custom') ? basename($params->get('skin_custom'), '.css') : $params->get('skin', 'seven'); ?>"
            <?php echo $params->get('skin_custom') ? ', url: "' . $params->get('skin_custom') . '"' : ''; ?>
        },
        <?php elseif ($params->get('hosting') == 'cloud' || $params->get('hosting') == 'self') :  ?>   
        skin: "<?php echo $params->get('hosting') == 'cloud' ? '' : ($params->get('skin_custom') ? $params->get('skin_custom') : JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer-skins/' . $params->get('skin', 'six') . '.xml'); ?>",
        <?php endif; ?>
        width: "100%",
        androidhls: false,
        stretching: "<?php echo $params->get('stretching', 'uniform'); ?>",
        // Logo block.
        <?php if ($params->get('logofile') != '') : ?>
        logo: {
            file: '<?php echo JURI::root(); ?><?php echo $params->get('logofile'); ?>',
            link: '<?php echo $params->get('logolink'); ?>',
            hide: '<?php echo $params->get('logohide'); ?>',
            margin: '<?php echo $params->get('logomargin'); ?>',
            position: '<?php echo $params->get('logoposition'); ?>'
        },
        <?php endif; ?>
        <?php if ($params->get('hosting') == 'self') : ?>
        flashplayer: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.flash.swf'; ?>', 
        html5player: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.html5.js'; ?>',
        <?php endif; ?>         
        abouttext: '<?php echo $params->get('abouttext', 'HWD MediaShare for Joomla'); ?>',
        aboutlink: '<?php echo $params->get('aboutlink', 'http://hwdmediashare.co.uk'); ?>'
    });
</script>
                <?php
                $html = ob_get_contents();
                ob_end_clean();
                
                return $html; 
        }  
        
        /**
	 * Method to render the player for streams.
         * 
	 * @access  public
	 * @param   object     $item     The item being displayed.
	 * @param   JRegistry  $sources  Media sources for the item.
	 * @return  void
	 **/
	public function getStreamPlayer($item, $sources)
	{
		// Initialise variables.
                $app = JFactory::getApplication();

                // Get HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Load plugin.
		$plugin = JPluginHelper::getPlugin('hwdmediashare', 'player_jwplayer');
		
                // Load the language file.
                $lang = JFactory::getLanguage();
                $lang->load('plg_hwdmediashare_player_jwplayer', JPATH_ADMINISTRATOR, $lang->getTag());

                if (!$plugin)
                {
                        $this->setError(JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_ERROR_NOT_PUBLISHED'));
                        return false;
                }

                // Load parameters.
                $params = new JRegistry($plugin->params);
                  
                // Preload assets.
                $this->preloadAssets($params);
                               
                ob_start();
                ?>
<div class="media-respond" style="max-width:<?php echo $config->get('mediaitem_size', '500'); ?>px;">
  <div class="media-aspect" data-aspect="<?php echo $config->get('video_aspect', '0.75'); ?>"></div>
  <div class="media-content">
    <div id="media-jwplayer-<?php echo $item->id; ?>"><?php echo JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_LOADING_PLAYER'); ?></div>
  </div>
</div>
<script type="text/javascript">
    jwplayer("media-jwplayer-<?php echo $item->id; ?>").setup({
        // Sources.
        playlist: [{
        sources: [
            { 'file': 'dummy', type: 'none' } // Dummy source to prevent trailing commas.
            <?php foreach($sources as $i => $source): ?>
                <?php if ($source->type == 1): ?>
                    ,{ 'file': '<?php echo $source->url; ?>' }
                <?php elseif ($source->type == 2): ?>
                    ,{ 'file': '<?php echo $source->url; ?>' }
                <?php elseif ($source->type == 3): ?>
                    ,{ 'file': '<?php echo $source->url; ?>', type: 'mp4', label: '<?php echo $source->quality; ?>p' }
                <?php endif; ?>
            <?php endforeach; ?>
        ]
        }],
        // Basic options.
        /** aspectratio: "16:9", // We don't set this because code from HWD manages the responsiveness of the player. */
        autostart: <?php echo $config->get('media_autoplay') ? 'true' : 'false'; ?>,
        controls: true,
        height: "100%",
        image: "<?php echo hwdMediaShareThumbnails::getVideoPreview($item); ?>",
        mute: false,
        primary: "flash",
        repeat: false,
        <?php if ($params->get('hosting') == 'self7') : ?>
        skin: {
            name: "<?php echo $params->get('skin_custom') ? basename($params->get('skin_custom'), '.css') : $params->get('skin', 'seven'); ?>"
            <?php echo $params->get('skin_custom') ? ', url: "' . $params->get('skin_custom') . '"' : ''; ?>
        },
        <?php elseif ($params->get('hosting') == 'cloud' || $params->get('hosting') == 'self') :  ?>   
        skin: "<?php echo $params->get('hosting') == 'cloud' ? '' : ($params->get('skin_custom') ? $params->get('skin_custom') : JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer-skins/' . $params->get('skin', 'six') . '.xml'); ?>",
        <?php endif; ?>
        width: "100%",
        androidhls: true,
        stretching: "<?php echo $params->get('stretching', 'uniform'); ?>",
        // Logo block.
        <?php if ($params->get('logofile') != '') : ?>
        logo: {
            file: '<?php echo JURI::root(); ?><?php echo $params->get('logofile'); ?>',
            link: '<?php echo $params->get('logolink'); ?>',
            hide: '<?php echo $params->get('logohide'); ?>',
            margin: '<?php echo $params->get('logomargin'); ?>',
            position: '<?php echo $params->get('logoposition'); ?>'
        },
        <?php endif; ?>
        <?php if ($params->get('videoadsclient') != '') : ?>
        advertising: {
          client: '<?php echo $params->get('videoadsclient', 'vast'); ?>',
          tag: '<?php echo $params->get('videoadstag', ''); ?>'
        },
        <?php endif; ?>          
        <?php if ($params->get('hosting') == 'self') : ?>
        flashplayer: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.flash.swf'; ?>', 
        html5player: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.html5.js'; ?>',
        <?php endif; ?>         
        abouttext: '<?php echo $params->get('abouttext', 'HWD MediaShare for Joomla'); ?>',
        aboutlink: '<?php echo $params->get('aboutlink', 'http://hwdmediashare.co.uk'); ?>'
    });
</script>
                <?php
                $html = ob_get_contents();
                ob_end_clean();
                
                return $html;      
        }    

        /**
	 * Method to render the player for a Youtube video.
         * 
	 * @access  public
	 * @param   object  $item  The item being displayed.
	 * @param   string  $id    The Youtube ID.
	 * @return  void
	 **/
	public function getYoutubePlayer($item, $id)
	{
		// Initialise variables.
                $app = JFactory::getApplication();

                // Get HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Load plugin.
		$plugin = JPluginHelper::getPlugin('hwdmediashare', 'player_jwplayer');
		
                // Load the language file.
                $lang = JFactory::getLanguage();
                $lang->load('plg_hwdmediashare_player_jwplayer', JPATH_ADMINISTRATOR, $lang->getTag());

                if (!$plugin)
                {
                        $this->setError(JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_ERROR_NOT_PUBLISHED'));
                        return false;
                }

                // Load parameters.
                $params = new JRegistry($plugin->params);
                 
                // Preload assets.
                $this->preloadAssets($params);
                               
                ob_start();
                ?>
<div class="media-respond" style="max-width:<?php echo $config->get('mediaitem_size', '500'); ?>px;">
  <div class="media-aspect" data-aspect="<?php echo $config->get('video_aspect', '0.75'); ?>"></div>
  <div class="media-content">
    <div id="media-jwplayer-<?php echo $item->id; ?>"><?php echo JText::_('PLG_HWDMEDIASHARE_PLAYER_JWPLAYER_LOADING_PLAYER'); ?></div>
  </div>
</div>
<script type="text/javascript">
    jwplayer("media-jwplayer-<?php echo $item->id; ?>").setup({
        // Basic options.
        /** aspectratio: "16:9", // We don't set this because code from HWD manages the responsiveness of the player. */
        autostart: <?php echo $config->get('media_autoplay') ? 'true' : 'false'; ?>,
        controls: true,
        file: "http://www.youtube.com/watch?v=<?php echo $id; ?>",
        height: "100%",
        image: "<?php echo hwdMediaShareThumbnails::getVideoPreview($item); ?>",
        mute: false,
        primary: "<?php echo $config->get('fallback') == 3 ? 'flash' : 'html5'; ?>",
        repeat: false,
        <?php if ($params->get('hosting') == 'self7') : ?>
        skin: {
            name: "<?php echo $params->get('skin_custom') ? basename($params->get('skin_custom'), '.css') : $params->get('skin', 'seven'); ?>"
            <?php echo $params->get('skin_custom') ? ', url: "' . $params->get('skin_custom') . '"' : ''; ?>
        },
        <?php elseif ($params->get('hosting') == 'cloud' || $params->get('hosting') == 'self') :  ?>   
        skin: "<?php echo $params->get('hosting') == 'cloud' ? '' : ($params->get('skin_custom') ? $params->get('skin_custom') : JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer-skins/' . $params->get('skin', 'six') . '.xml'); ?>",
        <?php endif; ?>
        width: "100%",
        androidhls: false,
        stretching: "<?php echo $params->get('stretching', 'uniform'); ?>",
        // Logo block.
        <?php if ($params->get('logofile') != '') : ?>
        logo: {
            file: '<?php echo JURI::root(); ?><?php echo $params->get('logofile'); ?>',
            link: '<?php echo $params->get('logolink'); ?>',
            hide: '<?php echo $params->get('logohide'); ?>',
            margin: '<?php echo $params->get('logomargin'); ?>',
            position: '<?php echo $params->get('logoposition'); ?>'
        },
        <?php endif; ?>
        <?php if ($params->get('videoadsclient') != '') : ?>
        advertising: {
          client: '<?php echo $params->get('videoadsclient', 'vast'); ?>',
          tag: '<?php echo $params->get('videoadstag', ''); ?>'
        },
        <?php endif; ?>          
        <?php if ($params->get('hosting') == 'self') : ?>
        flashplayer: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.flash.swf'; ?>', 
        html5player: '<?php echo JURI::root( true ) . '/plugins/hwdmediashare/player_jwplayer/assets/jwplayer/jwplayer.html5.js'; ?>',
        <?php endif; ?>         
        abouttext: '<?php echo $params->get('abouttext', 'HWD MediaShare for Joomla'); ?>',
        aboutlink: '<?php echo $params->get('aboutlink', 'http://hwdmediashare.co.uk'); ?>'
    });
</script>
                <?php
                $html = ob_get_contents();
                ob_end_clean();
                
                return $html; 
        }      
}