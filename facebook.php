<?php

moduleloader::includeModule('account/facebook');

class autoshare_facebook extends account_facebook {
    // get token for publishing
    public function getPublishAuth () {
        $ret = $this->login('publish_actions');
    }
    
    public function indexAction () {
        //$p = new autoshare_facebook();
        $this->getPublishAuth();
    } 
    
    public function meAction () {
        $facebook = $this->getFBObject();
        $user = $facebook->getUser();
        print_r($user);
    }
    
    public function postAction () {
        $facebook = $this->getFBObject();
        $user = $facebook->getUser();
        $share = $this->getShare();
        $result = $facebook->api("/$user/feed", 'POST', array('message' => $share));
        var_dump($result);
    }
    
    public function getUserInfo ($id = 'me') {
        $facebook = $this->getFBObject();
        $user_profile = $facebook->api("/$id",'GET');
        return $user_profile;
    }
    
    public function infomeAction() {
        print_r($this->getUserInfo());
    }
    
    public function saveUserInfo($account_id, $values) {
        return db_rb::getBean('user', 'account_id', $account_id);
    } 
    
    public function autoAction () {
        $facebook = $this->getFBObject();
        $share = $this->getShare();
        $user_id = "100003840996213";
        $result = $facebook->api("/$user_id/feed", 'POST', array('message' => $share));
        print_r($result);
    }
    
    public function getShare () {
        return "http://www.youtube.com/watch?v=amLungGziP0&list=FLxDWzD2t4uhiZkvmXP6W2SA";
    }
}
