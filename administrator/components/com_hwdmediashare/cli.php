<?php
/**
 * @package     Joomla.administrator
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

define('_JEXEC', 1);
define('_JCLI', 1);

define('JPATH_BASE', realpath(dirname(__FILE__).'/../../..'));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_LIBRARIES . '/import.php';
require_once JPATH_LIBRARIES . '/cms.php';
                
class HWDMediaShareCli extends JApplicationCli
{
        const SITE  = 0;
        const ADMIN = 1;
        const CLI   = 2; 
        protected $_clientId     = HWDMediaShareCli::CLI;
        protected $_application  = null;
        protected $_options      = array();
        
        /**
         * Initialise the application.
         *
         * Loads the necessary Joomla libraries to make sure
         * the Joomla application can run and sets up the JFactory properties.
         *
         * @protected
         * @return  void
         */
        protected function _initialize()
        {
                // Load dependencies.
                jimport('joomla.application');
                jimport('joomla.application.component.helper');
                jimport('joomla.application.menu');
                jimport('joomla.environment.uri');
                jimport('joomla.event.dispatcher');
                jimport('joomla.utilities.utility');
                jimport('joomla.utilities.arrayhelper');
                jimport('joomla.application.module.helper');
                jimport('joomla.filesystem.file');

                // Tell JFactory where to find the current application object.
                JFactory::$application = $this;

                // Start a new session and tell JFactory where to find it if we're on Joomla 3.
                if(version_compare(JVERSION, '3.0.0', '>='))
                {
                        JFactory::$session = $this->_startSession();
                }

                // Load HWD factory.
                JLoader::register('hwdMediaShareFactory', JPATH_BASE . '/components/com_hwdmediashare/libraries/factory.php');

                // Instiantiate the Joomla application object if we need either admin or site.
                $name = $this->getName();           
                if (in_array($name, array('administrator', 'site')))
                {
                        $this->_application = \JApplicationCms::getInstance($name);
                }
        }

        /**
         * Get the current application name.
         *
         * @public
         * @return string
         */
        public function getName()
        {
                $name = '';
                switch ($this->_clientId)
                {
                        case HWDMediaShareCli::SITE:
                                $name = 'site';
                        break;
                        case HWDMediaShareCli::ADMIN:
                                $name = 'administrator';
                        break;
                        default:
                                $name = 'cli';
                        break;
                }
                return $name;
        }
        
        /**
         * Retrieves value from the Joomla configuration.
         *
         * @public
         * @param   string  $varname  Name of the configuration property.
         * @param   mixed   $default  Default value.
         * @return mixed
         */
        public function getCfg($varname, $default = null)
        {
                return JFactory::getConfig()->get($varname, $default);
        }
    
        /**
         * Checks if interface is site or not.
         *
         * @public
         * @return  boolean
         */
        public function isSite()
        {
                return $this->_clientId == HWDMediaShareCli::SITE;
        }
        
        /**
         * Checks if interface is admin or not.
         *
         * @public
         * @return  boolean
         */
        public function isAdmin()
        {
                return $this->_clientId == HWDMediaShareCli::ADMIN;
        }

        /**
         * Creates a new Joomla session.
         *
         * @protected
         * @return     JSession
         */
        protected function _startSession()
        {
                $name     = md5($this->getCfg('secret') . get_class($this));
                $lifetime = $this->getCfg('lifetime') * 60 ;
                $handler  = $this->getCfg('session_handler', 'none');
                $options = array(
                    'name' => $name,
                    'expire' => $lifetime
                );
                $session = JSession::getInstance($handler, $options);
                $session->initialise($this->input, $this->dispatcher);
                if ($session->getState() == 'expired') {
                    $session->restart();
                } else {
                    $session->start();
                }
                return $session;
        }

        /**
         * Run the processor.
         *
         * @protected
         * @return  void (see HWD log for debugging)
         */
        protected function process()
        {            
                // Load processes library.
                hwdMediaShareFactory::load('processes');
                $model = hwdMediaShareProcesses::getInstance();
 
                $processes = array();
                $args = $GLOBALS['argv'];             
                if (count($args) > 2)
                {
                        $processes = array_slice($args, 2);
                        JArrayHelper::toInteger($processes);
                        array_filter($processes);
                }

                if (count($processes))
                {
                        foreach($processes as $process)
                        {   
                                $process = (int) $process;
                                if ($process > 0)
                                {
                                        // $this->out($arg);
                                        $model->run(array($process));
                                }
                        }   
                }
                else
                {
                        for ($i = 1; $i <= 50; $i++)
                        {
                                $model->run();
                        }
                }                                                
        }

        /**
         * Run the CDN transfer maintenance.
         *
         * @protected
         * @return  void (see HWD log for debugging)
         */
        protected function cdn()
        {
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Import HWD CDN plugin and run maintenance method.
                $pluginClass = 'plgHwdmediashare' . $config->get('cdn','cdn_amazons3');
                $pluginPath = JPATH_ROOT . '/plugins/hwdmediashare/' . $config->get('cdn','cdn_amazons3') . '/' . $config->get('cdn','cdn_amazons3') . '.php';
                if (file_exists($pluginPath))
                {
                        JLoader::register($pluginClass, $pluginPath);
                        $HWDcdn = call_user_func(array($pluginClass, 'getInstance'));
                        $HWDcdn->maintenance();
                }
        }

        /**
         * Run the CDN transfer maintenance.
         *
         * @protected
         * @return  void (see HWD log for debugging)
         */
        protected function addMediaFromServer()
        {
                // Load HWD API assets.
                JLoader::register('hwdMediaShareAPI', JPATH_BASE.'/components/com_hwdmediashare/libraries/api.php');
                $HWDapi = hwdMediaShareAPI::getInstance();

                // Define media to add.
                $path = '/path/to/file.mp4';
                $data = new JRegistry(array(
                    'title' => '',
                    'description' => '',
                    'created_user_id' => ''
                ));
                
                // Add the media file.
                $result = $HWDapi->addMediaFromServer($path, $data);   
                if ($result)
                {
                        $this->out('*******************');
                        $this->out('** SUCCESS');
                        $this->out('** Source: ' . $path);
                        $this->out('** ID: ' . $HWDapi->_item->id);
                        $this->out('*******************');
                        //var_dump($HWDapi->_item);
                }
                else
                {
                        $this->out('*******************');
                        $this->out('** FAIL');
                        $this->out('** Source: ' . $path);
                        $this->out('** Error: ' . $HWDapi->getError());
                        $this->out('*******************');
                }
        }
        
        /**
         * Create a test file to check background processing capability on server.
         *
         * @protected
         * @return  void
         */
        protected function createTestFile()
        {
                // Create test file.
                $filename = JPATH_SITE . '/tmp/hwdms.background';
                $buffer = '';
                JFile::write($filename, $buffer);
        }

        /**
         * Execute a task.
         *
         * @public
         * @return  void
         */
        public function execute()
        {
                // Initialise the application.
                $this->_initialize();
                
                // Get task.
                $task = null;
                
                // Check for passed arguments.
                $args = $GLOBALS['argv'];
                if (is_array($args) && isset($args[1]))
                {
                        $task = $args[1];
                }
                
                $args = $_SERVER["argv"];
                if (is_array($args) && isset($args[1]))
                {
                        $task = $args[1];
                }
                
                $task = preg_replace('/[^A-Za-z]/', '', $task);
                
                if ($task)
                {
                        switch ($task)
                        {
                                case 'test':
                                        $this->createTestFile();
                                        break;
                                case 'process':
                                        $this->process();
                                        break;
                                case 'cdn':
                                        $this->cdn();
                                        break;
                                case 'addMediaFromServer':
                                        $this->addMediaFromServer();
                                        break;                                 
                        }
                }
                else
                {
                        $this->out('No arguments have been passed to the script so exiting');
                }
        }
}

//JApplicationCli::getInstance('HWDMediaShareCli')->execute();
$application = new HWDMediaShareCli;
JFactory::$application = $application;
$application->execute();

