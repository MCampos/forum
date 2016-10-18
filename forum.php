<?php
class forum extends atk_controller {

	public function __construct() {

	}

	public function index($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		$this->assign('dir',$dir);
		$this->assign ('page_url',$_SERVER['REQUEST_URI']);
		$this->assign ('page','forum');		
		$this->assign ( 'title',$title );
		$this->display ();
		
		$this->view ( $controllervar, 'forum/forum-index.tpl' );
		include('ATK/forum.class.php');
		$forum = new atkforum();
		$this->assign('catPage', $forum->catPage());
		
		$this->display ();
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	}

	
	public function cat($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		
		if(isset($querystring['page']) && $querystring['page'] > 1 ) {
			$page = $querystring['page'];
		} else {
			$page = 1;
		}
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include_once('ATK/forum.class.php');
		$forum = new atkforum();
		$catinfo =  $forum->catInfo($urivar);
		$catposts = $forum->catPosts($urivar, $page);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$this->assign ('page_url',$_SERVER['REQUEST_URI']);
		$this->assign ('page','forum');		
		$css = array(
				"//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css",
		);
		$this->assign('css', $css);		
		$this->assign ( 'title',$title );
		$this->display ();
	
		
		$this->view ( $controllervar, 'forum/forum-category.tpl', 0 );
		$this->assign('next', $catposts['next']);
		$this->assign('previous', $catposts['previous']);
		$this->assign('pages', $catposts['pages']);
		$this->assign('cur_page', $page);
		$this->assign('catinfo', $catinfo);
		$this->assign('catposts', $catposts['posts']);
		$this->display ();
		
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	}
	
	
	public function post($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include_once('ATK/forum.class.php');
		$forum = new atkforum();
		if(isset($querystring['page']) && $querystring['page'] > 1 ) {
			$page = $querystring['page'];
		} else { 
			$page = 1;
		}
		$postinfo =  $forum->postDataNew($urivar, $page);
		$catinfo =  $forum->catInfo($postinfo['post']['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		if($_POST['B1'] == 'Submit') {
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', 'p,b,a[href],i,br,img[src],h1,h2,h3,h4,h5,h6,u,ul,li,ol,blockquote');
				
			$purifier = new HTMLPurifier($config);
			$thread = $purifier->purify($_POST['editor']);
			if(empty($thread)) {
				$error = '1';
				$this->assign('error', $error);
				$this->assign ( 'thread',$thread );
			} else {
				$forum->createReply($postinfo['post']['cat_id'],$urivar, $_SESSION['user_id'], $thread);
				redirect('/forum/post/'.$urivar);
			}
				
		}		
		$this->assign ('page_url',$_SERVER['REQUEST_URI']);
		$jsi = array(
				'function loadVal(){
					desc = $("#editor").html();
					document.form1.desc.value = desc;
				}'
		);
		$css = array(
				"//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css",
				"/css/bootstrap-wysihtml5.css"
		);
		$this->assign('jsi', $jsi);
		$this->assign('css', $css);
		$this->assign ('page','forum');
		$this->assign ( 'title',$title );
		$this->display ();
	
		
		$this->view ( $controllervar, 'forum/forum-post.tpl', 0 );
		$this->assign('user_id', $_SESSION['user_id']);
		$this->assign('page', $page);
		$this->assign('access', $forum->access);
		$this->assign('postinfo', $postinfo['post']);
		$this->assign('next', $postinfo['next']);
		$this->assign('previous', $postinfo['previous']);
		$this->assign('pages', $postinfo['pages']);
		$this->assign('cur_page', $page);
		$this->assign('replyinfo', $postinfo['replies']);
		$this->assign('catinfo', $catinfo);
		$this->assign('adminlist',explode(',',$loadini['admins']));
		$this->assign('photoglist',explode(',',$loadini['photogs']));
		$this->assign('modellist',explode(',',$loadini['models']));
		$this->assign('banned',$_SESSION['banned']);
		$this->display ();
		
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	}
	
	public function newthread($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include_once('ATK/forum.class.php');
		
		$forum = new atkforum();
		$catinfo =  $forum->catInfo($urivar);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		if($_POST['B1'] == 'Submit') {
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', 'p,b,a[href],i,br,img[src],h1,h2,h3,h4,h5,h6,u,ul,li,ol,blockquote');
			
			$purifier = new HTMLPurifier($config);
			$subject = htmlentities($_POST['subject']);
			$thread = $purifier->purify($_POST['editor']);
			if(empty($thread) || empty($subject)) {
				$error = '1';
				$this->assign('error', $error);
				$this->assign ( 'thread',$thread );
				$this->assign ( 'subject',$subject );
			} else {
				$forum->createThread($urivar, $_SESSION['user_id'], $subject, $thread);
				redirect('/forum/cat/'.$urivar);
			}
			
		}
		$this->assign ('page_url',$_SERVER['REQUEST_URI']);
		$this->assign ('page','forum');
		$this->assign ( 'title',$title );
		$jsi = array(
				'function loadVal(){
					desc = $("#editor").html();
					document.form1.desc.value = desc;
				}'
		);
		$css = array(
				"//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css",
				"/css/bootstrap-wysihtml5.css"
		);
		$this->assign('jsi', $jsi);
		$this->assign('css', $css);
		$this->display ();
	
	
		$this->view ( $controllervar, 'forum/forum-newthread.tpl');
		if(isset($error)) {
			$this->assign('error', $error);
			$this->assign ( 'thread',$thread );
			$this->assign ( 'subject',$subject );
		}
		$this->assign('catinfo', $catinfo);
		$this->assign('banned',$_SESSION['banned']);
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	}
	
	public function editthread($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
	
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include_once('ATK/forum.class.php');
	
		$forum = new atkforum();
		$postinfo =  $forum->getThread($urivar);
		if($postinfo['post_by'] != $_SESSION['user_id']) {
			redirect('/forum');
		}
		if($_POST['B1'] == 'Submit') {
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', 'p,b,a[href],i,br,img[src],h1,h2,h3,h4,h5,h6,u,ul,li,ol,blockquote');
				
			$purifier = new HTMLPurifier($config);
			$thread = $purifier->purify($_POST['editor']);
			$post_id = $purifier->purify($_POST['post_id']);
			if(empty($thread)) {
				$error = '1';
				$this->assign('error', $error);
				$this->assign ( 'thread',$thread );
			} else {
				$forum->editThread($post_id,$thread);
				redirect('/forum/post/'.$urivar);
			}
				
		}
		$this->assign ('page_url',$_SERVER['REQUEST_URI']);
		$this->assign ('page','forum');
		$this->assign ( 'title',$title );
		
		$jsi = array(
				'function loadVal(){
					desc = $("#editor").html();
					document.form1.desc.value = desc;
				}'
		);
		$css = array(
				"//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css",
				"/css/bootstrap-wysihtml5.css"
		);
		$this->assign('jsi', $jsi);
		$this->assign('css', $css);
		$this->assign('banned',$_SESSION['banned']);
		$this->display ();
	
	
		$this->view ( $controllervar, 'forum/forum-editthread.tpl');
		if(isset($error)) {
			$this->assign('error', $error);
			$this->assign ( 'thread',$thread );
			$this->assign ( 'subject',$subject );
		}
		$this->assign('postData', $postinfo);
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	}
	
	public function editreply($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
	
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include_once('ATK/forum.class.php');
	
		$forum = new atkforum();
		$postinfo =  $forum->replyData($urivar);
		if($postinfo['reply_by'] != $_SESSION['user_id']) {
			redirect('/forum');
		}
		if($_POST['B1'] == 'Submit') {
			require_once 'htmlpurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', 'p,b,a[href],i,br,img[src],h1,h2,h3,h4,h5,h6,u,ul,li,ol,blockquote');
	
			$purifier = new HTMLPurifier($config);
			$thread = $purifier->purify($_POST['editor']);
			$post_id = $purifier->purify($_POST['post_id']);
			if(empty($thread)) {
				$error = '1';
				$this->assign('error', $error);
				$this->assign ( 'thread',$thread );
			} else {
				$forum->editReply($post_id,$thread);
				redirect('/forum/post/'.$postinfo['reply_post']);
			}
	
		}
		$this->assign ('page_url',$_SERVER['REQUEST_URI']);
		$this->assign ('page','forum');
		$this->assign ( 'title',$title );
		$this->assign('banned',$_SESSION['banned']);
		$jsi = array(
				'function loadVal(){
					desc = $("#editor").html();
					document.form1.desc.value = desc;
				}'
		);
		$css = array(
				"//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css",
				"/css/bootstrap-wysihtml5.css"
		);
		$this->assign('jsi', $jsi);
		$this->assign('css', $css);
		$this->display ();
	
	
		$this->view ( $controllervar, 'forum/forum-editreply.tpl');
		if(isset($error)) {
			$this->assign('error', $error);
			$this->assign ( 'thread',$thread );
			$this->assign ( 'subject',$subject );
		}
		$this->assign('postData', $postinfo);
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	}
	
	public function pdelete($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include('ATK/forum.class.php');
		$forum = new atkforum();
		if($forum->access < 2 || $forum->access > 3) {
			redirect('/forum');
		}
		$postinfo =  $forum->postData($urivar);
		$catinfo =  $forum->catInfo($postinfo['post']['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$forum->pdelete($urivar);
		redirect("/forum/cat/".$postinfo['post']['cat_id']);
		$this->display ();
		
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();

	}
	
	public function rdelete($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include('ATK/forum.class.php');
		$forum = new atkforum();
		if($forum->access < 2 || $forum->access > 3) {
			redirect('/forum');
		}
		$replyinfo =  $forum->replyData($urivar);
		$catinfo =  $forum->catInfo($replyinfo['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$forum->rdelete($urivar);
		redirect("/forum/post/".$replyinfo['reply_post']);
		$this->display ();
		
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();

	}
	
	public function lock($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include('ATK/forum.class.php');
		$forum = new atkforum();
		if($forum->access < 2 || $forum->access > 3) {
			redirect('/forum');
		}
		$postinfo =  $forum->postData($urivar);
		$catinfo =  $forum->catInfo($postinfo['post']['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$forum->lock($urivar);
		redirect("/forum/post/".$urivar);
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	
	}
	
	public function unlock($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include('ATK/forum.class.php');
		$forum = new atkforum();
		if($forum->access < 2 || $forum->access > 3) {
			redirect('/forum');
		}
		$postinfo =  $forum->postData($urivar);
		$catinfo =  $forum->catInfo($postinfo['post']['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$forum->unlock($urivar);
		redirect("/forum/post/".$urivar);
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	
	}

	public function sticky($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include('ATK/forum.class.php');
		$forum = new atkforum();
		if($forum->access < 2 || $forum->access > 3) {
			redirect('/forum');
		}
		$postinfo =  $forum->postData($urivar);
		$catinfo =  $forum->catInfo($postinfo['post']['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$forum->sticky($urivar);
		redirect("/forum/post/".$urivar);
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	
	}
	
	
	public function unsticky($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include('ATK/forum.class.php');
		$forum = new atkforum();
		if($forum->access < 2 || $forum->access > 3) {
			redirect('/forum');
		}
		$postinfo =  $forum->postData($urivar);
		$catinfo =  $forum->catInfo($postinfo['post']['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$forum->unsticky($urivar);
		redirect("/forum/post/".$urivar);
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	
	}

	public function report($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build;
		$this->view ( $controllervar, 'forum/header.tpl', 0 );
		include ('ATK/atk_header.php');
		include('ATK/forum.class.php');
		$forum = new atkforum();
		if($forum->access < 2 || $forum->access > 3) {
			redirect('/forum');
		}
		$postinfo =  $forum->postData($urivar);
		$catinfo =  $forum->catInfo($postinfo['post']['cat_id']);
		if($forum->secure_check($catinfo['site_id'], $catinfo['access']) == 0) {
			redirect('/forum');
		}
		$forum->report($urivar);
		redirect("/forum/post/".$urivar."?reported=1");
		$this->display ();
	
		//Footer
		$this->view ( $controllervar, 'footer.tpl' );
		include('footerModel.php');
		$this->display ();
	
	}
	
	public function update($controllervar, $action, $querystring, $urivar) {
		global $loadini,$build,$db;
		include ('ATK/forum.class.php');
		$this->view ( $controllervar, 'forum/forum-update.tpl', 0 );
		$forum = new atkforum();
		$forum_updates = $forum->forumUpdate();
		$this->assign('forum_updates',$forum_updates);
		$this->display ();
	
	}
	
}

