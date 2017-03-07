<?php
/**
 * Crawler Web Application
 *
 * @category   Crawler
 * @package    Console
 * @copyright  Copyright (c) 2017 Flavius Rosu (http://www.webdesignrr.ro)
 * @version    1.0
 * @author  Flavius Rosu
 */

$startTime = microtime();

if(!defined('STDIN'))
{
	echo "Console scripts should be run from the command line interface";
	exit;
}

// Define PATH's (absolute paths)  to configuration and DotKernel  directories
$rootPath = realpath(dirname(__FILE__) . "/..");
defined('CONFIGURATION_PATH') || define('CONFIGURATION_PATH', $rootPath.'/configs');

// Define application path
define('APPLICATION_PATH', realpath(dirname(__DIR__)));

// Load Zend Framework
require_once 'Zend/Loader/Autoloader.php';
$zendLoader = Zend_Loader_Autoloader::getInstance();
$zendLoader->registerNamespace('Console_');

// Parse the command line arguments
try
{
	$opts = new Zend_Console_Getopt(array(
		'action|a=s'		=> 'the action that will be executed',
		'environment|e=w'	=> '[optional] environment parameter, defaults to production'
	));
	$opts->parse();
}
catch (Zend_Console_Getopt_Exception $e)
{
	echo $e->getUsageMessage();
	exit;
}

if ($opts->getOption('environment') === NULL)
{
	// environment variable not set, falling back to production
	define('APPLICATION_ENV', 'production');
}
else
{
	define('APPLICATION_ENV', $opts->getOption('environment'));
}

// Create registry object, as read-only object to store there config, settings, and database
$registry = new Zend_Registry(array(), ArrayObject::ARRAY_AS_PROPS);
Zend_Registry::setInstance($registry);

$registry->startTime = $startTime;

// Load configuration settings from application.ini file and store it in registry
$config = new Zend_Config_Ini(CONFIGURATION_PATH.'/application.ini', APPLICATION_ENV);
$registry->configuration = $config;

// Create  connection to database, as singleton , and store it in registry
$db = Zend_Db::factory('Pdo_Mysql', $config->database->params->toArray());
$registry->database = $db;

$registry->option = array();

// define some const variables
define('SITE_BASE', $registry->configuration->website->params->url);
define('SSL_SITE_BASE', str_replace("http:", "https:", $registry->configuration->website->params->url));

// Get the action and the other command line arguments
$registry->action = $opts->getOption('action');
$registry->arguments = $opts->getRemainingArgs();

if ($registry->action === NULL)
{
	echo $opts->getUsageMessage();
	exit;
}

include('Controller.php');