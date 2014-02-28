<?php

/**
 * file contains autoshare
 */

/**
 * class account_locales
 */
class autoshare {
    
    /**
     * events: attach the account_locales module to a parent module
     * @param type $args
     * @return type
     */
    public static function events ($args) {
        if ($args['action'] == 'attach_module_menu') {
            return self::getModuleMenuItem($args);
        }

        // we autoshare on items which has published = 1
        // on both insert and update
        if ($args['action'] == 'insert' || $args['action'] == 'update') {            
            return self::insertEvent($args);
        }
    }
    
    public function getFbUserShareRow ($user_id) {
        return db_q::select('autoshare_facebook')->
                filter('user_id =', $user_id)->
                fetchSingle();
    }
    
    public static function insertEvent ($args) {
        $user_id = $args['user_id'];
       
        if (!$user_id) {
            return false;
        }
        
        $s = new autoshare();
        
        $row = $s->getFbUserShareRow($user_id);
        if (empty($row) OR $row['enabled'] != 1) {
            return false;
        }
        
        $entry = blog::getEntry($args['parent_id']);
        

        if ($entry['published'] != 1 ) {
            
            return false;
        }
        //print_r($entry); die;
        
        if (empty($entry['entry'])) {
            $share_str = $entry['title'];
        } else {
            $share_str = $entry['entry'];
        }
         
        return $s->fbPost($row['facebook_id'], $share_str);
    }
    
    /**
     * post a string|text to a facebook user wall
     * @param int $user_id
     * @param string $share_str
     * @return boolean $res1111
     */
    public function fbPost ($user_id, $share_str) {
        $res = false;
        try {
            $fb = new autoshare_facebook();
            $facebook = $fb->getFBObject();
        //$share = $this->getShare();
            $res = $facebook->api("/$user_id/feed", 'POST', array('message' => $share_str));
        } catch (Exception $e) {
            log::error($e->getMessage());
        }
        return $res;
    }
    
        /**
     * create a module menu item for setting locales per account
     * @param array $args
     * @return array $menu menu item
     */
    public static function getModuleMenuItem ($args) {
        
        $ary = array(
            'title' => lang::translate('Facebook'),
            'url' => '/autoshare/facebook?no_action=true',
            'auth' => 'user');
        return $ary;
    }
    
    public function revokefbAction () {
        $res = db_q::update('autoshare_facebook')->
                values(array('enabled' => '0'))->
                filter('user_id =', session::getUserId())->
                exec();

        http::locationHeader('/autoshare/facebook?no_action=true');
    }
    
    public function displayRevokeFb () {
        echo html::createLink(
                '/autoshare/revokefb', 
                lang::translate('Stop autosharing to facebook')
        );
    }
    
    public function facebookAction () {
        
        // set layout
        if (!session::checkAccess('user')) {
            return;
        }
        
        $parent = config::getModuleIni('autoshare_parent');        
        layout::setParentModuleMenu($parent);
        
        if ($this->userFbShareEnabled()) {
            $this->displayRevokeFb ();
            return;
        }
        
        if (!isset($_GET['no_action']) ) {
        
            try {
                $fb = new autoshare_facebook();
                $facebook = $fb->getFBObject();

                $fb_info = $this->getUserInfo('/me');
                // print_r($fb_info);

                $perms = $this->getUserPerms('/me');
                if (isset($perms['data'][0]['publish_actions'])) {
                    $this->createFbSharingDb(session::getUserId(), $fb_info['id']);
                    http::locationHeader('/autoshare/facebook');
                }
            } catch (Exception $e) {
                // echo $e->getMessage();
            }
        }
        
        
        $fb = new autoshare_facebook();
        $facebook = $fb->getFBObject();
        
        $args['redirect_uri'] = config::getSchemeWithServerName() . "/autoshare/facebook";
        $args['scope'] = 'publish_actions';
        
        $url = $facebook->getLoginUrl($args);
        
        echo html::createLink($url, lang::translate('Enable auto posting to facebook'));
    }
    
    public function createFbSharingDb ($user_id, $facebook_id) {
        $bean = db_rb::getBean('autoshare_facebook', 'user_id', $user_id);
        $bean->user_id = $user_id;
        $bean->facebook_id = $facebook_id;
        $bean->enabled = 1;
        return r::store($bean);
    }
    
    public function userFbShareEnabled () {
        $row = db_q::select('autoshare_facebook')->
                filter('user_id =', session::getUserId())->
                condition('AND')->
                filter('enabled =', 1)->
                fetchSingle();
        if (!empty($row)) {
            try {
                $perms = $this->getUserPerms($row['facebook_id']);
            } catch (Exception $e) {
                //log::error($e->getMessage());
                return false;
            }
            
            
            if (isset($perms['data'][0]['publish_actions'])) {
                return true;
            }
        }
        return false;
    }
    
    public function getUserInfo ($id = 'me') {
        $fb = new autoshare_facebook();
        $facebook = $fb->getFBObject();
        $user_profile = $facebook->api("/$id",'GET');
        return $user_profile;
    }
    
    public function getUserPerms ($id = 'me') {
        $fb = new autoshare_facebook();
        $facebook = $fb->getFBObject();
        $perms = $facebook->api("/$id/permissions",'GET');
        return $perms;
    }
    
    
}
