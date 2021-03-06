<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	
	require_once($C->INCPATH.'helpers/func_externalprofiles.php');
	
	$D->page_title	= $this->lang('settings_contacts_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE));
	
	$D->submit	= FALSE;
	$D->errmsg1	= '';
	$D->errmsg2	= '';
	$D->errmsg3	= '';
	
	$D->i	= (object) array (
		'website'	=> '',
		'personal_email'	=> '',
		'personal_phone'	=> '',
		'work_phone'	=> '',
		'im_skype'	=> '',
		'im_icq'	=> '',
		'im_gtalk'	=> '',
		'im_msn'	=> '',
		'im_yahoo'	=> '',
		'im_aim'	=> '',
		'im_jabber'	=> '',
		'prof_linkedin'	=> '',
		'prof_facebook'	=> '',
		'prof_twitter'	=> '',
		'prof_flickr'	=> '',
		'prof_friendfeed'	=> '',
		'prof_delicious'	=> '',
		'prof_digg'	=> '',
		'prof_myspace'	=> '',
		'prof_orcut'	=> '',
		'prof_youtube'	=> '',
		'prof_mixx'	=> '',
		'prof_edno23'	=> '',
		'prof_favit'	=> '',
	);
	$db2->query('SELECT * FROM users_details WHERE user_id="'.$this->user->id.'" LIMIT 1');
	if($obj = $db->fetch_object()) {
		unset($obj->user_id);
		foreach($obj as $k=>$v) {
			if( substr($k,0,5)=='prof_' && !empty($v) ) {
				if( preg_match('/\#\#\#(.*)$/', $v, $m) ) {
					$D->i->$k	= stripslashes($m[1]);
				}
			}
			else {
				$D->i->$k	= stripslashes($v);
			}
		}
	}
	
	$tmphash	= md5(serialize($D->i));
	
	if( isset($_POST['sbm']) ) {
		$D->submit	= TRUE;
		foreach($D->i as $k=>$v) {
			$D->i->$k	= isset($_POST[$k]) ? trim($_POST[$k]) : '';
		}
		
		$update_fields	= array();
		
		if( $D->i->website == 'http://' ) {
			$D->i->website	= '';
		}
		if( !empty($D->i->website) && !preg_match('/^((http|ftp|https):\/\/)?([a-z0-9.-]+\.)+[a-z]{2,4}(\/([a-z0-9-_\/]+)?)?$/iu', $D->i->website) ) {
			$D->errmsg1	= 'st_cnt_error_website';
		}
		elseif(!empty($D->i->website) && $D->i->website != 'http://') {
			if( ! preg_match('/^(http|ftp|https):\/\//iu', $D->i->website) ) {
				$D->i->website	= 'http://'.$D->i->website;
			}
			$update_fields['website']	= $D->i->website;
		}
		if(!empty($D->i->personal_email) && !is_valid_email($D->i->personal_email)){
			$D->errmsg1	= 'st_cnt_error_pemail';
		}elseif(!empty($D->i->personal_email)){
			$update_fields['personal_email']	= $D->i->personal_email;
		}
		
		if(!empty($D->i->personal_phone)){
			$update_fields['personal_phone']	= htmlspecialchars($D->i->personal_phone);
		}
		if(!empty($D->i->work_phone)){
			$update_fields['work_phone']	= htmlspecialchars($D->i->work_phone);
		}
		
		// not bad idea to validate messengers... some day ;)
		$update_fields['im_skype']	= $D->i->im_skype;
		$update_fields['im_icq']	= $D->i->im_icq;
		$update_fields['im_gtalk']	= $D->i->im_gtalk;
		$update_fields['im_msn']	= $D->i->im_msn;
		$update_fields['im_yahoo']	= $D->i->im_yahoo;
		$update_fields['im_jabber']	= $D->i->im_jabber;
		
		$tmp	= array();
		foreach($D->i as $k=>$v) {
			if( ! preg_match('/^prof_(.*)$/i', $k, $m) ) {
				continue;
			}
			$tmp[]	= $m[1];
		}
		$tmp	= array_reverse($tmp);
		foreach($tmp as $site) {
			$valfunc	= 'validate_'.$site.'_profile_url';
			if( !empty($D->i->{'prof_'.$site}) && !$m=$valfunc($D->i->{'prof_'.$site}) ) {
				$D->errmsg3	= 'st_cnt_error_'.$site;
			}
			else {
				$update_fields['prof_'.$site]	= empty($D->i->{'prof_'.$site}) ? '' : ($m[1].'###'.$m[0]);
				$D->i->{'prof_'.$site}		= empty($D->i->{'prof_'.$site}) ? '' : $m[0];
			}
		}
		
		if( count($update_fields) > 0 ) {
			$insql	= array();
			foreach($update_fields as $k=>$v) {
				$insql[]	= '`'.$k.'`="'.$db2->e($v).'"';
			}
			$insql	= implode(', ', $insql);
			$db2->query('REPLACE INTO users_details SET user_id="'.$this->user->id.'", '.$insql);
			$this->user->sess['cdetails']	= $this->db2->fetch('SELECT * FROM users_details WHERE user_id="'.$this->user->id.'" LIMIT 1');
			
			if( $tmphash != md5(serialize($D->i)) ) {
				$notif = new notifier();
				$notif->onEditProfileInfo();
			}
		}
	}
	
	$this->load_template('settings_contacts.php');
	
?>