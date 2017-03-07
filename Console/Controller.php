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


switch($registry->action)
{
	case 'crawl':
		echo 'here we start the real work ...';
	break;
	
	default:
		echo "Action doesn't exist.\n";
		exit(1);
	break;
}
