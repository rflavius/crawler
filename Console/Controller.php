<?php
/**
 * Crawler Controller
 *
 * @category   Crawler
 * @package    Console
 * @copyright  Copyright (c) 2017 Flavius Rosu (http://www.webdesignrr.ro)
 * @version    1.0
 * @author     Flavius Rosu
 */

// 1. populate the websites DB table with the websites you need to crawl

// 2. once you've added the websites then you need to create process tree, by CLI command: php FULL-PATH/index.php -a create_process -e production

$crawler = new Console_Model_Crawler();

switch($registry->action)
{
	case 'crawl':
		if($crawler->ready())
		{
			$crawler->run();
		}
		else echo 'process not ready yet!';
	break;
	
	case 'create_process':
		if($crawler->createProcessTree()) echo 'Process tree successfully created !'.PHP_EOL;
	break;
	
	default:
		echo "Action doesn't exist.\n";
		exit(1);
	break;
}
