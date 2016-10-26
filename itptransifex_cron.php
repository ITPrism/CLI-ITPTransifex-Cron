<?php
/**
 * @package      ITPTransifex
 * @subpackage   CLI
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2015 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

/**
 * ITP Transifex CRON.
 *
 * This is a command-line script to help with management of ITP Transifex.
 *
 * Called with no arguments: php itptransifex_cron.php
 *                           Load CRON plug-ins and triggers event "onCronExecute".
 *
 * Called with --create:     php itptransifex_cron.php --create
 *                           Load CRON plug-ins and triggers event "onCronCreate".
 *
 * Called with --update:     php itptransifex_cron.php --update
 *                           Load CRON plug-ins and triggers event "onCronUpdate".
 */

// Make sure we're being called from the command line, not a web interface.
if (PHP_SAPI !== 'cli') {
    die('This is a command line only application.');
}

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php')) {
    require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(__DIR__));
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * A command line cron job to run ITP Transifex cron job.
 *
 * @package      ITP Transifex
 * @subpackage   CLI
 */
class ItptransifexCronCli extends JApplicationCli
{
    /**
     * Start time for the process.
     *
     * @var    string
     */
    private $time;

    public function doExecute()
    {
        // Print a blank line.
        $this->out(JText::_('ITPTransifex CRON'));
        $this->out('============================');

        // Initialize the time value.
        $this->time = microtime(true);

        // Remove the script time limit.
        @set_time_limit(0);

        // Fool the system into thinking we are running as JSite with Smart Search as the active component.
        $_SERVER['HTTP_HOST'] = 'domain.com';
        JFactory::getApplication('site');

        // Get options.
        $create  = $this->input->getString('create', false);
        $update  = $this->input->getString('update', false);
        $execute = $this->input->getString('execute', false);

        $context = $this->input->getCmd('context');

        // Import the finder plugins.
        JPluginHelper::importPlugin('itptransifexcron');

        try {
            if ($create) {
                $context = 'com_itptransifex.cron.create.' . $context;
                $this->out('create context: '.$context);
                $this->out('============================');

                JEventDispatcher::getInstance()->trigger('onCronCreate', array($context));

            } elseif ($update) {
                $context = 'com_itptransifex.cron.update.' . $context;
                $this->out('update context: '.$context);
                $this->out('============================');

                JEventDispatcher::getInstance()->trigger('onCronUpdate', array($context));

            } elseif ($execute) { // Execute
                $context = 'com_itptransifex.cron.execute.' . $context;
                $this->out('execute context: '.$context);
                $this->out('============================');

                JEventDispatcher::getInstance()->trigger('onCronExecute', array($context));
            }

        } catch (Exception $e) {
            $this->logErrors($e->getMessage());
            $this->out($e->getMessage());
        }

        // Total reporting.
        $this->out(JText::sprintf('Total Processing Time: %s seconds.', round(microtime(true) - $this->time, 3)), true);

        // Print a blank line at the end.
        $this->out();
    }

    protected function logErrors($content)
    {
        $config = JFactory::getConfig();

        if (is_writable($config->get('log_path'))) {
            $logFile = $config->get('log_path').DIRECTORY_SEPARATOR.'error_cron.txt';
            file_put_contents($logFile, $content ."\n", FILE_APPEND);
        }
    }
}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('ItptransifexCronCli')->execute();
