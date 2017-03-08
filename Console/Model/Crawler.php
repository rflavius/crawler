<?php 
/**
 * Crawler Web Application
 *
 * @copyright  Copyright (c) 2017 Flavius Rosu (http://www.webdesignrr.ro)
 * @version    1.0
 */

/**
 * Crawler.php
 * here we have the crawler model
 * @category   Crawler
 * @package    Console
 * @author     Flavius Rosu
 */


class Console_Model_Crawler
{
	protected static $maxRequests = 10;
	protected $_crawler;
	private static $tmp_col = 'crawler_done';
	
	/**
	 * Constructor
	 * initialize the DB and conf object
	 * @access public
	 * @param none
	 * @return void
	 */
	public function __construct()
	{
		// here we will start the new class based on PDO
		$this->db = Zend_Registry::get('database');
	}
	
	/**
	 * here we check if we have any process ready to be executed
	 * @access public
	 * @param optional int $websiteID
	 * @return boolean
	 */
	public function ready($websiteID = 0)
	{
		$select = $this->db->select()
							->from(array('a' => 'processes'), array('a.id', 'a.status', 'a.website_id'))
							->leftJoin(array('b' => 'websites'), 'a.website_id = b.id', array('b.url', 'b.interface'))
							->where('a.status = ?', 'ready')
							->where('((TIME_TO_SEC(NOW()) - TIME_TO_SEC(a.date))%a.run_time) >= (a.run_time/2)');
		if(!empty($websiteID)) $select->where('a.website_id = ?', (int)$websiteID);
		$result = $this->db->fetchRow($select);
		if(!empty($result))
		{
			//file_put_contents(APPLICATION_PATH.'/log.txt', "\n".date('Y-m-d H:i:s').' :: '.implode(" => ",$result), FILE_APPEND);
			// store the process into object property
			$this->_crawler = $result;
			// update process status
			$data = array('status' => 'in progress');
			$where = array('id = ?' => $result['id']);
			$this->updateProcess($data, $where);
			return true;
		}
		else return false;
	}
	
	public function run()
	{
		print_r($this->_crawler);exit;
		// create temporary column if needed for the pages
		$this->createTmpCol('pages');
		
		
		
		if($this->countPages())
		{
			
		}
		
		
	}
	
	/**
	 * here we generate the crawler process tree
	 * @access public
	 * @param none
	 * @throws Exception
	 * @return boolean
	 */
	public function createProcessTree()
	{
		try
		{
			$websites = $this->listWebsites();
			if(count($websites)>0)
			{
				foreach ($websites as $key => $value)
				{
					$data = array('website_id' => $value['id'], 'date' => new Zend_Db_Expr('NOW()')); // optional you can add the run_time which as default is set to 60sec
					$this->addProcess($data);
				}
			}
			else throw new Exception("There is NO websites to crawl.");
		}
		catch (Exception $e)
		{
			echo $e->getMessage().PHP_EOL;
			return false;
		}
		return true;
	}
	
	/**
	 * add new record into crawler processes tree
	 * @access private
	 * @param array $data
	 * @return void
	 */
	private function addProcess($data)
	{
		$this->db->insert('processes', $data);
	}
	
	/**
	 * here we list the websites we want to crawl
	 * @access public
	 * @param optional array $where
	 * @return array
	 */
	public function listWebsites($where = array())
	{
		$select = $this->db->select()
							->from('websites');
		if(!empty($where))
		{
			foreach ($where as $key => $value)
			{
				$select->where($key, $value);
			}
		}
		return $this->db->fetchAll($select);
	}
	
	/**
	 * add temporary column for dbTable if needed
	 * @access protected
	 * @param string $db_table
	 * @return void
	 */
	protected function createTmpCol($db_table)
	{
		if (count($this->db->query("SHOW COLUMNS FROM $db_table LIKE '".self::$tmp_col."'")->fetchAll())==0)
		{
			$this->db->query("ALTER TABLE $db_table ADD ".self::$tmp_col." ENUM('Y','N') NOT NULL DEFAULT 'N'");
		}
	}
	
	/**
	 * update crawler process record
	 * @access public
	 * @param array $data
	 * @param array $where
	 * @return void
	 */
	public function updateProcess($data, $where)
	{
		$this->db->update('processes', $data, $where);
	}
	
	/**
	 * new method to make multi curl
	 * @access public
	 * @param array $urls
	 * @param object $obj
	 * @param string $callback
	 * @param optional array $custom_options
	 * @return boolean
	 */
	public function multiCurl($urls, $obj, $callback, $custom_options = null)
	{
		$userAgents = array(
						'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:44.0) Gecko/20100101 Firefox/44.0',
						'Mozilla/4.0 (compatible; MSIE 8.0; AOL 9.7; AOLBuild 4343.19; Windows NT 5.1; Trident/4.0; GTB7.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)',
						'Mozilla/5.0 (compatible; MSIE 9.0; AOL 9.7; AOLBuild 4343.19; Windows NT 6.1; WOW64; Trident/5.0; FunWebProducts)',
						'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
						'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0',
						'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20120101 Firefox/29.0',
						'Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201',
						'Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16',
						'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14',
						'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0) Opera 12.14',
						'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
						'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25',
						'Mozilla/5.0 (Windows; U; Win 9x 4.90; SG; rv:1.9.2.4) Gecko/20101104 Netscape/9.1.0285',
						'Mozilla/5.0 (Windows; U; Win98; en-US; rv:1.8.1.8pre) Gecko/20070928 Firefox/2.0.0.7 Navigator/9.0RC1',
						'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.7pre) Gecko/20070815 Firefox/2.0.0.6 Navigator/9.0b3'
		);
		// make sure the rolling window isn't greater than the # of urls
		$rolling_window = self::$maxRequests;
		$rolling_window = (sizeof($urls) < $rolling_window) ? sizeof($urls) : $rolling_window;
	
		$master = curl_multi_init();
		$curl_arr = array();
	
		// add additional curl options here
		$std_options = array(
						CURLOPT_USERAGENT => $userAgents[rand(0, count($userAgents) -1)],
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_MAXREDIRS => 3,
						//CURLOPT_PROXY => Scrapper_Scrapper::$proxy['ip'].":".Scrapper_Scrapper::$proxy['port'],
						//CURLOPT_PROXYPORT => Scrapper_Scrapper::$proxy['port'],
						//CURLOPT_PROXYUSERPWD => Scrapper_Scrapper::$proxy['user'].":".Scrapper_Scrapper::$proxy['passwd'],
		);
		$options = ($custom_options) ? ($std_options + $custom_options) : $std_options;
	
		// start the first batch of requests
		for ($i = 0; $i < $rolling_window; $i++) {
			$ch = curl_init();
			$options[CURLOPT_URL] = $urls[$i];
			curl_setopt_array($ch,$options);
			curl_multi_add_handle($master, $ch);
		}
	
		do {
			while(($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
			if($execrun != CURLM_OK)
				break;
				// a request was just completed -- find out which one
				while($done = curl_multi_info_read($master))
				{
					$info = curl_getinfo($done['handle']);
					$content = curl_multi_getcontent($done['handle']);
					$output[] = array(
										'response' => $content, 
										'url' => $info['url'], 
										'http_code' => $info['http_code'],
										'referrer' => self::$referrer,
										
					);

					// request successful.  process output using the callback function.
					$obj->$callback($output);

					// start a new request (it's important to do this before removing the old one)
					$ch = curl_init();
					$options[CURLOPT_URL] = $urls[$i++];  // increment i
					curl_setopt_array($ch,$options);
					curl_multi_add_handle($master, $ch);

					// remove the curl handle that just completed
					curl_multi_remove_handle($master, $done['handle']);
				}
		} while ($running);
		curl_multi_close($master);
		return true;
	}
}