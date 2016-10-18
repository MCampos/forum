<?php
class atkforum {
	
	public $site, $fconfig;
	
	var $access = 1;
	
	public function __construct() {
		global $loadini, $db;
		$query = "SELECT ifnull(f.user_level,1) as user_level FROM accounts a LEFT OUTER JOIN forum_admin_".$loadini['site']." f ON a.account_id = f.user_id WHERE a.site =  '".$loadini['site']."' AND a.username =  '".$_SESSION['username']."';";
		$this->access = $db->queryOne($query);
		$query = "SELECT site_id FROM sites where site_name = '".$loadini['site']."';";
		$this->site = $db->queryOne($query);
		$query = "SELECT * FROM forum_config_".$loadini['site'];
		$this->fconfig = $db->queryRow($query);
	}
	public function secure_check($site_id, $level) {
		if($this->site == $site_id && $level <= $this->access) {
			return 1;
		} else {
			return 0;
		}
	}
	
	public function getCats() {
		global $loadini, $db;
		$query = "SELECT * FROM forum_categories_".$loadini['site']." where site_id = $this->site and access <= $this->access";
		$cats = $db->queryAll($query);
		return $cats;
	}
	
	public function userLevel() {
		return $this->access;
	}
	
	public function mostRecentPosts($cat) {
		global $loadini, $db;
		$query = "SELECT p.post_id, p.post_subject, p.post_content, p.post_date,p.post_by,a.alias FROM forum_posts_".$loadini['site']." p LEFT OUTER JOIN accounts a ON (p.post_by = a.account_id) WHERE site_id = '".$this->site."' AND cat_id = '".$cat."' and p.active = 1 ORDER BY post_date DESC LIMIT 1;";
		$result = $db->queryRow($query);
		return $result;
	}
	public function catPostCnt($cat) {
		global $loadini, $db;
		$query = "SELECT count(*) as postcount FROM forum_posts_".$loadini['site']." WHERE cat_id = '".$cat."' and active = 1";
		$result = $db->queryOne($query);
		return $result;
	}
	public function replyPostCnt($cat) {
		global $loadini, $db;
		$query = "SELECT count(*) as replycount  FROM forum_replies_".$loadini['site']." WHERE cat_id = '".$cat."' and active = 1";
		$result = $db->queryOne($query);
		return $result;
	}
	
	public function catInfo($cat) {
		global $loadini, $db;
		$query = "SELECT * FROM forum_categories_".$loadini['site']." WHERE cat_id = '".$cat."'";
		$result = $db->queryRow($query);
		return $result;
	}
	
	public function catPage() {
		global $loadini, $db;
		$cats = $this->getCats();
		$return = array();
		foreach ($cats as $c) {
			$return[$c['cat_id']]['cat_id'] = $c['cat_id'];
			$return[$c['cat_id']]['cat_name'] = $c['cat_name'];
			$return[$c['cat_id']]['cat_description']  = $c['cat_description'];
			$return[$c['cat_id']]['matt']  = 'awesome';
			foreach($this->mostRecentPosts($c['cat_id']) as $foo => $bar) {
				$return[$c['cat_id']][$foo] = $bar;
			}
			$return[$c['cat_id']]['posts'] = $this->catPostCnt($c['cat_id']);
			$return[$c['cat_id']]['replies'] = $this->replyPostCnt($c['cat_id']);
		}
		
		return $return;
	}
	
	public function forumUpdate() {
		global $loadini, $db;
		$threads = array();
		$query = "SELECT cat_id FROM forum_categories_".$loadini['site']." where site_id = $this->site and access <= $this->access";
		$cats = $db->queryCol($query);
		foreach($cats as $foo) {
			$thread_q = "SELECT p.*,c.cat_name, a.alias, r_max.reply_date, r_max.reply_by, IFNULL(reply_count.replies,0) as replies, a1.alias AS reply_alias FROM forum_posts_".$loadini['site']." p LEFT JOIN forum_categories_".$loadini['site']." c ON p.cat_id = c.cat_id LEFT JOIN (SELECT reply_id, reply_post, reply_date, reply_by FROM forum_replies_".$loadini['site']." JOIN (SELECT MAX(reply_id) AS id FROM forum_replies_".$loadini['site']." WHERE active = 1 GROUP BY reply_post) AS grp ON grp.id = reply_id) AS r_max ON (r_max.reply_post = p.post_id) LEFT JOIN (SELECT reply_post, COUNT(reply_post) AS replies FROM forum_replies_".$loadini['site']." WHERE active = 1 GROUP BY reply_post) AS reply_count ON (reply_count.reply_post = p.post_id) LEFT JOIN forum_replies_".$loadini['site']." r ON (r.reply_id = r_max.reply_id) LEFT JOIN accounts a1 ON (r_max.reply_by = a1.account_id) LEFT OUTER JOIN accounts a ON (p.post_by = a.account_id) WHERE p.cat_id = '".$foo."' AND p.site_id = '".$this->site."' AND p.active = 1 GROUP BY p.post_id, r.reply_post ORDER BY COALESCE(r.reply_date, p.post_date) DESC LIMIT 1;";
			$threads[] = $db->queryRow($thread_q); 
		}
		return $threads;
	}
	
	
	public function catPosts($cat, $page = 1) {
		global $loadini, $db;
		$paginate = 20;
		/*
		if($page == 1){
			$start = 0;
			$end = 20;
		} else {
			$start = (($paginate * $page) - $paginate) + 1;
			$end = (($paginate * $page) + 1;
		}
		*/
		$start = (($paginate * $page) - $paginate);
		$end = $paginate * $page;
		$count_q = "SELECT COUNT(*) from forum_posts_".$loadini['site']." p WHERE p.cat_id = '".$cat."' AND p.site_id = '".$this->site."' and p.active = '1'";
		$count_r = $db->queryOne($count_q);
		$pages = ceil($count_r / $paginate);
		$next = range($page+1, $page+6);
		$previous = range($page-6, $page-1);
		foreach($next as $f) {
			if($f <= $pages) {
				$n[] = $f;
			}
		}
		foreach($previous as $p) {
			if($p > 0) {
				$prev[] = $p;
			}
		}
		$return['count_r'] = $count_r;
		$return['previous'] = $prev;
		$return['next'] = $n;
		$return['pages'] = $pages; 
		//$query = "SELECT p.*, a.alias, r_max.reply_date, r.reply_by, a1.alias AS reply_alias, COUNT(r.reply_post) AS replies FROM forum_posts_".$loadini['site']." p LEFT JOIN forum_replies_".$loadini['site']." r ON (p.post_id = r.reply_post) LEFT JOIN accounts a1 ON (r.reply_by = a1.account_id) LEFT OUTER JOIN accounts a ON (p.post_by = a.account_id) WHERE p.cat_id = '".$cat."' AND p.site_id = '".$this->site."' and p.active = '1' GROUP BY p.post_id, r.reply_post ORDER BY p.sticky DESC, CASE WHEN r.reply_date >= p.post_date THEN r.reply_date ELSE  p.post_date END DESC LIMIT ".$start.",".$end;
		$query = "SELECT p.*, a.alias, r_max.reply_date, r_max.reply_by, IFNULL(reply_count.replies,0) as replies, a1.alias AS reply_alias FROM forum_posts_".$loadini['site']." p LEFT JOIN (SELECT reply_id, reply_post, reply_date, reply_by FROM forum_replies_".$loadini['site']." JOIN (SELECT MAX(reply_id) AS id FROM forum_replies_".$loadini['site']." WHERE active = 1 GROUP BY reply_post) AS grp ON grp.id = reply_id) AS r_max ON (r_max.reply_post = p.post_id) LEFT JOIN (SELECT reply_post, COUNT(reply_post) AS replies FROM forum_replies_".$loadini['site']." WHERE active = 1 GROUP BY reply_post) AS reply_count ON (reply_count.reply_post = p.post_id) LEFT JOIN forum_replies_".$loadini['site']." r ON (r.reply_id = r_max.reply_id) LEFT JOIN accounts a1 ON (r_max.reply_by = a1.account_id) LEFT OUTER JOIN accounts a ON (p.post_by = a.account_id) WHERE p.cat_id = '".$cat."' AND p.site_id = '".$this->site."' AND p.active = 1 GROUP BY p.post_id, r.reply_post ORDER BY p.sticky DESC, COALESCE(r.reply_date, p.post_date) DESC LIMIT ".$start.",".$end.";";	
		//$this->log($query);
		$result = $db->queryAll($query);
		$return['posts'] = $result;
		return $return;		
	}
	
	public function getThread($post_id) {
		global $loadini, $db;
		$query = "SELECT p.*, a.alias, MD5(LOWER(TRIM(a.email))) AS gravatar FROM forum_posts_".$loadini['site']." p LEFT OUTER JOIN accounts a ON (p.post_by = a.account_id) WHERE post_id = ".$db->quote($post_id, 'integer')." AND site_id = $this->site and active = 1;";
		$result = $db->queryRow($query);
		return $result;
	} 
	
	public function postDataNew($post_id, $page = 1) {
		global $loadini, $db;
		$paginate = 20;
		$start = (($paginate * $page) - $paginate);
		$end = $paginate * $page;
		$cnt_post_q = "select count(*) from forum_posts_".$loadini['site']." p WHERE post_id = ".$db->quote($post_id, 'integer')." AND site_id = $this->site and active = 1;";
		$cnt_reply_q = "SELECT count(*) FROM forum_replies_".$loadini['site']."  WHERE reply_post = ".$db->quote($post_id, 'integer')." AND site_id = $this->site and active = 1;";
		$cnt_post = $db->queryOne($cnt_post_q);
		$cnt_reply = $db->queryOne($cnt_reply_q);
		$cnt_total = $cnt_post + $cnt_reply;
		$pages = ceil($cnt_reply / $paginate);
		$next = range($page+1, $page+6);
		$previous = range($page-6, $page-1);
		//-----------------------------------------
		//$from = 'system@atkcash.com'; // sender
		//$subject = 'Count data';
		//$message = 'Page: '.$page.'Post Data:'.$cnt_post.' - reply: '.$cnt_reply.' - Ttl: '.$cnt_total.' - Next: '.print_r($next,true).' - Prev: '.print_r($previous,true).' - Start: '.$start.' - End: '.$end . ' q: '.$cnt_reply_q;
		//mail("matt@atkcash.com",$subject,$message,"From: $from\n");
		//-------------------------------------------
		foreach($next as $f) {
			if($f <= $pages) {
				$n[] = $f;
			}
		}
		foreach($previous as $p) {
			if($p > 0) {
				$prev[] = $p;
			}
		}
		$return['count_r'] = $cnt_total;
		$return['previous'] = $prev;
		$return['next'] = $n;
		$return['pages'] = $pages;
		
		$query = "SELECT p.*, a.alias,a.username, MD5(LOWER(TRIM(a.email))) AS gravatar FROM forum_posts_".$loadini['site']." p LEFT OUTER JOIN accounts a ON (p.post_by = a.account_id) WHERE post_id = ".$db->quote($post_id, 'integer')." AND site_id = $this->site and active = 1;";
		$result = $db->queryRow($query);
		$return['post'] = $result;
		$query1 = "SELECT r.*, a.alias,a.username, MD5(LOWER(TRIM(a.email))) AS gravatar FROM forum_replies_".$loadini['site']." r LEFT OUTER JOIN accounts a ON (r.reply_by = a.account_id) WHERE reply_post = ".$db->quote($post_id, 'integer')." AND site_id = $this->site and active = 1 LIMIT ".$start.",".$paginate.";";
		$result1 = $db->queryAll($query1);
		//-----------------------------------------
		//$from = 'system@atkcash.com'; // sender
		//$subject = 'Page2 data';
		//$message = $query1.' - '.$result1;
	
		//mail("matt@atkcash.com",$subject,$message,"From: $from\n");
		//-------------------------------------------
		$return['replies'] = $result1;
		return $return;
	}
	
	public function postData($post_id) {
		global $loadini, $db;
		$query = "SELECT p.*, a.alias,a.username, MD5(LOWER(TRIM(a.email))) AS gravatar FROM forum_posts_".$loadini['site']." p LEFT OUTER JOIN accounts a ON (p.post_by = a.account_id) WHERE post_id = ".$db->quote($post_id, 'integer')." AND site_id = $this->site and active = 1;";
		$result = $db->queryRow($query);
		$return['post'] = $result;
		$query1 = "SELECT r.*, a.alias,a.username, MD5(LOWER(TRIM(a.email))) AS gravatar FROM forum_replies_".$loadini['site']." r LEFT OUTER JOIN accounts a ON (r.reply_by = a.account_id) WHERE reply_post = ".$db->quote($post_id, 'integer')." AND site_id = $this->site and active = 1;";
		$result1 = $db->queryAll($query1);
		$return['replies'] = $result1;
		return $return;
	}
	
	public function replyData($reply_id) {
		global $loadini, $db;
		$query = "SELECT * FROM forum_replies_".$loadini['site']." WHERE reply_id = ".$db->quote($reply_id, 'integer')." AND site_id = $this->site and active = 1;";
		$result = $db->queryRow($query);
		
		return $result;
	}
	
	public function createThread($cat, $post_by, $subject, $content) {
		global $loadini, $db;
		
		$query = "INSERT INTO forum_posts_".$loadini['site']." (post_subject, post_content, post_date, cat_id, post_by, site_id) VALUES (".$db->quote($subject).", ".$db->quote($content).", NOW(), ".$db->quote($cat).", ".$db->quote($post_by).", ".$db->quote($this->site).");";
		$result = $db->queryAll($query);
		
		return $result;
	}
	
	public function editThread($post_id, $content) {
		global $loadini, $db;
		$query = "UPDATE forum_posts_".$loadini['site']." SET post_content = ".$db->quote($content).", edited = NOW() WHERE post_id =  ".$db->quote($post_id);
		$result = $db->queryAll($query);
		return $result;
	}
	
	public function createReply($cat, $reply_id, $post_by, $content) {
		global $loadini, $db;
	
		$query = "INSERT INTO forum_replies_".$loadini['site']." (reply_content, reply_date, reply_post, reply_by, cat_id, site_id) VALUES (".$db->quote($content).", NOW(), ".$db->quote($reply_id).", ".$db->quote($post_by).", ".$db->quote($cat).", ".$db->quote($this->site).");";
		$result = $db->queryAll($query);
	
		return $result;
	}
	
	public function editReply($reply_id, $content) {
		global $loadini, $db;
		$query = "UPDATE forum_replies_".$loadini['site']." SET reply_content = ".$db->quote($content).", edited = NOW() WHERE reply_id =  ".$db->quote($reply_id);
		$result = $db->queryAll($query);
		return $result;
	}
	
	public function pdelete($post_id) {
		global $loadini, $db;
		
		$postinfo = $this->postData($post_id);
		$postdata =  $postinfo['post'];
		$replyinfo = $postinfo['replies'];
		$query = "UPDATE forum_posts_".$loadini['site']." p SET p.active = 0 WHERE post_id = ".$db->quote($postdata['post_id']).";";
		$result = $db->queryAll($query);
		foreach($replyinfo as $foo) {
				$this->rdelete($foo['reply_id']);
		}
		return $result;
	}
	
	public function rdelete($reply_id) {
		global $loadini, $db;
		
		$query = "UPDATE forum_replies_".$loadini['site']." r SET r.active = 0 WHERE reply_id = $db->quote($reply_id);";
		$result = $db->queryAll($query);
	}
	
	public function lock($post_id) {
		global $loadini, $db;
	
		$query = "UPDATE forum_posts_".$loadini['site']." p SET p.locked = 1 WHERE post_id = ".$db->quote($post_id).";";
		$result = $db->queryAll($query);
	}
	
	public function unlock($post_id) {
		global $loadini, $db;
	
		$query = "UPDATE forum_posts_".$loadini['site']." p SET p.locked = 0 WHERE post_id = ".$db->quote($post_id).";";
		$result = $db->queryAll($query);
	}
	public function sticky($post_id) {
		global $loadini, $db;
	
		$query = "UPDATE forum_posts_".$loadini['site']." p SET p.sticky = 1 WHERE post_id = ".$db->quote($post_id).";";
		$result = $db->queryAll($query);
	}
	public function unsticky($post_id) {
		global $loadini, $db;
	
		$query = "UPDATE forum_posts_".$loadini['site']." p SET p.sticky = 0 WHERE post_id = ".$db->quote($post_id).";";
		$result = $db->queryAll($query);
	}
	
	public function report($post_id) {
		global $loadini, $db;
		$postinfo = $this->postData($post_id);
		$postdata =  $postinfo['post'];
		$replyinfo = $postinfo['replies'];
		
		$subject = "Post reported by ".$_SESSION['username'];
		$content = "An User has reported thread: <a href=\"/forum/post/$post_id\">".$postdata['post_subject']."</a> as being in volation of the rules. The violation could be in the post or one of the replies, please read the entire thread. Please delete this thread once you have dealt with the thread in violation.";
		$query = "INSERT INTO forum_posts_".$loadini['site']." (post_subject, post_content, post_date, cat_id, post_by, site_id) VALUES  ('$subject', '$content', NOW(), '".$this->fconfig['report_forum']."', '".$this->fconfig['admin_user']."', '$this->site');";
		$result = $db->queryAll($query);
		return $result;
	}
	function log($data, $default = 'email', $name = 'log', $email = 'matt@atkcash.com') {
		global $loadini;
		if ($default == 'email') {
			$from = 'system@atkcash.com'; // sender
			$subject = 'Logger';
			if (is_array ( $data )) {
				$message = print_r ( $data, true );
			} else {
				$message = $data;
			}
			mail ( $email, $subject, $message, "From: $from\n" );
		} elseif ($default == 'log') {
			$log = $loadini ['log'] . $name;
			$fh = fopen ( $log, 'w' ) or die ( "can't open file" );
			if (is_array ( $data )) {
				$message = print_r ( $data, true );
			} else {
				$message = $data;
			}
			fwrite ( $fh, $data );
			fclose ( $fh );
		}
	}
	
}