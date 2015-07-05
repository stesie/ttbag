<?php

require_once __DIR__.'/vendor/content-extractor/SiteConfig.php';
require_once __DIR__.'/vendor/content-extractor/ContentExtractor.php';
require_once __DIR__.'/vendor/readability/Readability.php';
require_once __DIR__.'/vendor/makefulltextfeedHelpers.php';

spl_autoload_register('__autoload');
require_once __DIR__.'/vendor/simplepie/autoloader.php';


class Ttbag extends Plugin implements IHandler {

	function about() {
		return array(1.0,
			"Tiny Tiny Bag aka TTRSS Pocket clone",
			"stesie",
			true);
	}

	function init($host) {
		$this->host = $host;
		$this->dbh = Db::get();
		$host->add_handler("public", "sharepopup", $this);
	}

	function sharepopup() {
		$action = $_REQUEST["action"];

		if ($_SESSION["uid"]) {
			if ($action == 'share') {
				$this->store_shared_article();
				require __DIR__.'/views/share.php';

			} else {
				require __DIR__.'/views/index.php';
			}

		} else {
			require __DIR__.'/views/login.php';
		}
	}

	function api_version() {
		return 2;
	}

	function csrf_ignore($method) {
		return true;
	}

	function before($method) {
		return true;
	}

	function after() {
		return true;
	}

	protected function store_shared_article() {
		$extracted_content = $this->extract_content($_REQUEST["url"]);

		$url = $this->dbh->escape_string(strip_tags($_REQUEST["url"]));
		$labels = $this->dbh->escape_string(strip_tags($_REQUEST["labels"]));

		if($extracted_content) {
			$content = $extracted_content['html'];
			$title = $extracted_content['title'];
		}
		else {
			$content = '';
			$title = strip_tags($_REQUEST["title"]);
		}

		if(!empty($_REQUEST["xdebug"]) && $_REQUEST['xdebug'] == 2) {
			die("<hr><h1>$title</h1>\n$content");
		}

		$content = $this->dbh->escape_string($content, false);
		$title = $this->dbh->escape_string($title);

		$this->create_archived_article($title, $url, $content, $labels, $_SESSION["uid"]);
	}

	protected function extract_content($url) {
		if(!filter_var($url, FILTER_VALIDATE_URL)) {
			return null;
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);

		if(!in_array($scheme, array("http", "https", "ftp"))) {
			return false;
		}

		$html = file_get_contents($url);

		if(empty($html)) {
			return false;
		}

		echo "<pre>";
		$extractor = new ContentExtractor(__DIR__.'/ftr-site-config', __DIR__.'/site-config.local');
		$extractor->debug = true;
		$this->check_single_page($extractor, $url, $html);
		$extract_result = $extractor->process($html, $url);
		echo "</pre>";

		if(!$extract_result) {
			return false;
		}

		$content_block = $extractor->getContent();
		$extractor->readability->clean($content_block, 'select');

		// get base URL
		$base_url = get_base_url($extractor->readability->dom);
		if (!$base_url) {
			$base_url = $url;
		}

		// rewrite URLs
		makeAbsolute($base_url, $content_block);

		// remove nesting: <div><div><div><p>test</p></div></div></div> = <p>test</p>
		while ($content_block->childNodes->length == 1 && $content_block->firstChild->nodeType === XML_ELEMENT_NODE) {
			// only follow these tag names
			if (!in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer'))) {
				break;
			}
			$content_block = $content_block->firstChild;
		}

		// convert content block to HTML string
		// Need to preserve things like body: //img[@id='feature']
		if (in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer'))) {
			$html = $content_block->innerHTML;
		} else {
			$html = $content_block->ownerDocument->saveXML($content_block); // essentially outerHTML
		}

		// post-processing cleanup
		$html = preg_replace('!<p>[\s\h\v]*</p>!u', '', $html);

		return array(
			'title' => $extractor->getTitle(),
			'html' => $html,
		);
	}

	protected function check_single_page($extractor, &$url, &$html) {
		$site_config = $extractor->buildSiteConfig($url, $html);
		$debug_enabled = defined('DAEMON_EXTENDED_DEBUG') || $_REQUEST['xdebug'];

		if (empty($site_config->single_page_link)) {
			_debug("SiteConfig doesn't declare single_page_link", $debug_enabled);
			return;
		}

		// Build DOM tree from HTML
		$readability = new Readability($html, $url);
		$xpath = new DOMXPath($readability->dom);

		// Loop through single_page_link xpath expressions
		$single_page_url = null;
		foreach ($site_config->single_page_link as $pattern) {
			_debug("Trying pattern: $pattern", $debug_enabled);
			$elems = @$xpath->evaluate($pattern, $readability->dom);
			if (is_string($elems)) {
				_debug(". matched and returned a string", $debug_enabled);
				$single_page_url = trim($elems);
				break;
			} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
				_debug(". matched and returned a node list", $debug_enabled);
				foreach ($elems as $item) {
					if ($item instanceof DOMElement && $item->hasAttribute('href')) {
						_debug("... got an element, using href attribute", $debug_enabled);
						$single_page_url = $item->getAttribute('href');
						break 2;
					} elseif ($item instanceof DOMAttr && $item->value) {
						_debug("... got an attribute, using its value", $debug_enabled);
						$single_page_url = $item->value;
						break 2;
					}
				}
			}
		}

		if(empty($single_page_url)) {
			_debug("no single_page_url found, continuing with main page", $debug_enabled);
			return;
		}

		_debug("extracted single_page_url: $single_page_url", $debug_enabled);

		// If we've got URL, resolve against $url
		$single_page_url = makeAbsoluteStr($url, $single_page_url);
		_debug("... converted to absolute single_page_url: $single_page_url", $debug_enabled);

		if($single_page_url == $url) {
			_debug("single_page_url equals current page", $debug_enabled);
			return;
		}

		$single_page_html = file_get_contents($single_page_url);

		if(empty($single_page_html)) {
			_debug("single_page_url document is empty", $debug_enabled);
			return;
		}

		$html = $single_page_html;
		$url = $single_page_url;
	}

	protected function create_archived_article($title, $url, $content, $labels_str, $owner_uid) {
		$guid = 'SHA1:' . sha1("tinybag:" . $url . $owner_uid); // include owner_uid to prevent global GUID clash
		$content_hash = sha1($content);

		if ($labels_str != "") {
			$labels = explode(",", $labels_str);
		} else {
			$labels = array();
		}

		$rc = false;

		if (!$title) $title = $url;
		if (!$title && !$url) return false;

		if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) return false;

		db_query("BEGIN");

		// only check for our user data here, others might have shared this with different content etc
		$result = db_query("SELECT id FROM ttrss_entries, ttrss_user_entries WHERE
			guid = '$guid' AND ref_id = id AND owner_uid = '$owner_uid' LIMIT 1");

		if (db_num_rows($result) != 0) {
			$rc = false;

		} else {
			$result = db_query("INSERT INTO ttrss_entries
				(title, guid, link, updated, content, content_hash, date_entered, date_updated)
				VALUES
				('$title', '$guid', '$url', NOW(), '$content', '$content_hash', NOW(), NOW())");

			$result = db_query("SELECT id FROM ttrss_entries WHERE guid = '$guid'");

			if (db_num_rows($result) != 0) {
				$ref_id = db_fetch_result($result, 0, "id");

				db_query("INSERT INTO ttrss_user_entries
					(ref_id, uuid, feed_id, orig_feed_id, owner_uid, published, tag_cache, label_cache, unread)
					VALUES
					('$ref_id', '', NULL, NULL, $owner_uid, false, '', '', true)");

				if (count($labels) != 0) {
					foreach ($labels as $label) {
						label_add_article($ref_id, trim($label), $owner_uid);
					}
				}

				$rc = true;
			}
		}

		db_query("COMMIT");

		return $rc;
	}
}
?>
