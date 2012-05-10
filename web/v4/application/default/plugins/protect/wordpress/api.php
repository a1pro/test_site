<?php
class WordpressAPI{
    protected $_plugin;
    protected $_options = array();
    protected $_role_key = "";
    protected $_amember_roles = array('amember_active'=> 'aMember Active', 'amember_expired'=> 'aMember Expired');
    protected $COOKIEHASH;
    protected $AUTH_COOKIE;
    protected $SECURE_AUTH_COOKIE;
    protected $LOGGED_IN_COOKIE; 
    protected $COOKIE_PATH;
    protected $SITECOOKIEPATH;
    protected $PLUGINS_COOKIE_PATH;
    protected $ADMIN_COOKIE_PATH;
    
    function  __construct(Am_Protect_Wordpress $plugin) {
        $this->_plugin              = $plugin;
        $this->_role_key            = $this->_plugin->getConfig('prefix')."user_roles";
        $this->COOKIEHASH           = ($siteurl = $this->get_option('siteurl')) ? md5($siteurl) : ''; 
        $this->AUTH_COOKIE          = 'wordpress_'.$this->COOKIEHASH;
        $this->SECURE_AUTH_COOKIE   = 'wordpress_sec_'.$this->COOKIEHASH;
        $this->LOGGED_IN_COOKIE     = 'wordpress_logged_in_'.$this->COOKIEHASH;
        $this->COOKIE_PATH          = preg_replace('|https?://[^/]+|i', '', rtrim($this->get_option('home'), '/') . '/' );
        $this->SITECOOKIEPATH       = preg_replace('|https?://[^/]+|i', '', rtrim($this->get_option('siteurl'), '/') . '/' );
        $this->ADMIN_COOKIE_PATH    =   $this->SITECOOKIEPATH.'wp-admin';
        $this->PLUGINS_COOKIE_PATH  =   $this->SITECOOKIEPATH."wp-content/plugins";
        
    }

    function getPlugin(){
        return $this->_plugin;
    }

    function getDb(){
        return $this->getPlugin()->getDb();
    }

    function get_alloptions(){
        throw new Am_Exception("Deprecated!");
    }
    
    function get_option_cache($name){
        if(!array_key_exists($name, $this->_options)){ 
            $r = $this->getDb()->selectRow("select * from ?_options where autoload = 'yes' and option_name=?", $name);
            if(!$r) return false;
            if($this->is_serialized($r['option_value'])){
                $this->_options[$r['option_name']] = unserialize($r['option_value']);
            }else{
                $this->_options[$r['option_name']] = $r['option_value'];
            }
        }
        return @$this->_options[$name];
        
    }
    function get_option($name){
        return $this->get_option_cache($name);
    }

    function update_option($name, $value){
        $name = trim($name);
        if(!$name) return false;
        $old_value = $this->get_option_cache($name);
        if($old_value===$value)
            return false;

        if($old_value===false)
            return $this->add_option($name, $value);

        $this->_options[$name] = $value;
        $value = $this->maybe_serialize($value);
        $this->getDb()->query("update ?_options set option_value=? where option_name=?", $value, $name);
    }

    function maybe_serialize( $data ) {
	if ( is_array( $data ) || is_object( $data ) )
		return serialize( $data );

	if ( $this->is_serialized( $data ) )
		return serialize( $data );

	return $data;
    }

    function add_option($name, $value){
        $this->_options[$name] = $value;
        $value = $this->maybe_serialize($value);
        $this->getDb()->query("insert into ?_options (option_name, option_value, autoload) values (?,?,?)", $name, $value, 'yes');
    }

    function get_roles(){
        $roles = $this->get_option($this->_role_key);
        foreach($this->_amember_roles as $r=>$n){
            if(!array_key_exists($r, $roles)){
                $this->add_role($r, $n, array('read'=>1, 'level_0'=>1));
            }
        }
        return $roles;
    }

    function add_role($role, $name, $caps){
        $roles = $this->get_option($this->_role_key);
        $roles[$role] = array('name'=>$name, 'capabilities' =>$caps);
        $this->update_option($this->_role_key, $roles);
    }

    function get_user_meta($user_id, $key){
        if($user_id <= 0) return false;
        $meta = $this->getDb()->selectRow("select * from ?_usermeta where user_id=? and meta_key=?", $user_id, $key);
        if(!array_key_exists('umeta_id', $meta) || ($meta['umeta_id']<=0)) return false;
        return $this->maybe_unserialize( $meta['meta_value']);
    }

    function update_user_meta($user_id, $meta_key, $meta_value, $prev_value = ''){
        if($user_id <=0) return false ;
        if(!$meta_key) return false;
        $old_value = $this->get_user_meta($user_id, $meta_key);
        if($old_value === false){
            $this->getDb()->query("insert into ?_usermeta (user_id, meta_key, meta_value) values (?, ?, ?)", $user_id, $meta_key, $this->maybe_serialize($meta_value));
        }else{
            $this->getDb()->query("update ?_usermeta set meta_value=? where user_id=? and meta_key=?", $this->maybe_serialize($meta_value), $user_id, $meta_key);
        }
    }

    function maybe_unserialize( $original ) {
	if ( $this->is_serialized( $original ) )
		return @unserialize( $original );
	return $original;
    }

    function is_serialized($str){
        if($str === 'b:0;') return true;
        $data = @unserialize($str);
        if ($data !== false) {
            return true;
        } else {
            return false;

        }
    }
    
    function wp_set_auth_cookie($user_id, $remember = false, $secure = '', Am_Record $user=null) {
        $expiration = time()+172800;
        $expire= 0; 
	if ( '' === $secure )
		$secure = $this->is_ssl();
        if($secure){
            $cookie_name  = $this->SECURE_AUTH_COOKIE;
            $scheme = 'secure_auth';
        }else{
            $cookie_name  = $this->AUTH_COOKIE;
            $scheme = 'auth';
        }
        $auth_cookie = $this->wp_generate_auth_cookie($user->pk(), $expiration, $scheme,$user);
        $logged_in_cookie = $this->wp_generate_auth_cookie($user->pk(), $expiration, 'logged_in',$user);
        
        Am_Controller::setCookie($cookie_name, $auth_cookie, $expire, $this->PLUGINS_COOKIE_PATH, null, $secure);
        Am_Controller::setCookie($cookie_name, $auth_cookie, $expire, $this->ADMIN_COOKIE_PATH, null, $secure);
        Am_Controller::setCookie($this->LOGGED_IN_COOKIE,$logged_in_cookie,$expire,$this->COOKIE_PATH, null, $secure);
        if ( $this->COOKIE_PATH != $this->SITECOOKIEPATH )
            Am_Controller::setCookie($this->LOGGED_IN_COOKIE,$logged_in_cookie,$expire,$this->SITECOOKIEPATH, null, $secure);
    }

    function wp_hash($data, $scheme = 'auth') {
	$salt = $this->wp_salt($scheme);
        return hash_hmac('md5', $data, $salt);
    }
    
    function wp_salt($scheme){
        switch($scheme){
            case 'auth' :   
                $secret_key = $this->getPlugin()->getConfig('auth_key');
                $salt  = $this->getPlugin()->GetConfig('auth_salt');
                break;
            case 'secure_auth'  :   
                $secret_key = $this->getPlugin()->getConfig('secure_auth_key');
                $salt  = $this->getPlugin()->GetConfig('secure_auth_salt');
                break;
            case 'logged_in'    :   
                $secret_key = $this->getPlugin()->getConfig('logged_in_key');
                $salt  = $this->getPlugin()->GetConfig('logged_in_salt');
                break;
            default: 
                throw new Am_Exception("Unknown scheme");
        }
        return $secret_key . $salt;
    }
    
    
    function wp_generate_auth_cookie($user_id, $expiration, $scheme = 'auth', Am_Record $user=null){
	$pass_frag = substr($user->user_pass, 8, 4);

	$key = $this->wp_hash($user->user_login . $pass_frag . '|' . $expiration, $scheme);
	$hash = hash_hmac('md5', $user->user_login . '|' . $expiration, $key);

	$cookie = $user->user_login . '|' . $expiration . '|' . $hash;

	return $cookie;
    }
    
    function wp_clear_auth_cookie(){
	
        Am_Controller::setCookie($this->AUTH_COOKIE, ' ', time() - 31536000, $this->ADMIN_COOKIE_PATH);
        Am_Controller::setCookie($this->SECURE_AUTH_COOKIE, ' ', time() - 31536000, $this->ADMIN_COOKIE_PATH);
        Am_Controller::setCookie($this->AUTH_COOKIE, ' ', time() - 31536000, $this->PLUGINS_COOKIE_PATH);
        Am_Controller::setCookie($this->SECURE_AUTH_COOKIE, ' ', time() - 31536000, $this->PLUGINS_COOKIE_PATH);
        Am_Controller::setCookie($this->LOGGED_IN_COOKIE, ' ', time() - 31536000, $this->COOKIE_PATH);
        Am_Controller::setCookie($this->LOGGED_IN_COOKIE, ' ', time() - 31536000, $this->SITECOOKIEPATH);
        
	// Old cookies
        Am_Controller::setCookie($this->AUTH_COOKIE, ' ', time() - 31536000, $this->COOKIE_PATH);
        Am_Controller::setCookie($this->AUTH_COOKIE, ' ', time() - 31536000, $this->SITECOOKIEPATH);
        Am_Controller::setCookie($this->SECURE_AUTH_COOKIE, ' ', time() - 31536000, $this->COOKIE_PATH);
        Am_Controller::setCookie($this->SECURE_AUTH_COOKIE, ' ', time() - 31536000, $this->SITECOOKIEPATH);
        
    }
    
    function wp_validate_auth_cookie($cookie = '', $scheme = ''){
	if ( ! $cookie = $this->wp_parse_auth_cookie($cookie, $scheme) ) 
            return false;
        if($cookie['expiration']<time())
            return false; 
        $user = $this->getDb()->selectRow("select * from ?_users where user_login = ?", $cookie['username']);
        if(!$user) return false; 
	$pass_frag = substr($user['user_pass'], 8, 4);

	$key = $this->wp_hash($cookie['username'] . $pass_frag . '|' . $cookie['expiration'], $scheme);
	$hash = hash_hmac('md5', $cookie['username'] . '|' . $cookie['expiration'], $key);

	if ( $cookie['hmac'] != $hash ) {
		return false;
	}

        return $user['ID'];
    }
    
    function wp_parse_auth_cookie($cookie = '', $scheme = '') {    
	if ( empty($cookie) ) {
		switch ($scheme){
			case 'auth':
				$cookie_name = $this->AUTH_COOKIE;
				break;
			case 'secure_auth':
				$cookie_name = $this->SECURE_AUTH_COOKIE;
				break;
			case "logged_in":
				$cookie_name = $this->LOGGED_IN_COOKIE;
				break;
			default:
				if ( $this->is_ssl() ) {
					$cookie_name = $this->SECURE_AUTH_COOKIE;
					$scheme = 'secure_auth';
				} else {
					$cookie_name = $this->AUTH_COOKIE;
					$scheme = 'auth';
				}
                }
		if ( empty($_COOKIE[$cookie_name]) )
			return false;
		$cookie = $_COOKIE[$cookie_name];
	}
        $cookie_elements = explode('|', $cookie);
	if ( count($cookie_elements) != 3 )
		return false;

	list($username, $expiration, $hmac) = $cookie_elements;

	return compact('username', 'expiration', 'hmac', 'scheme');
    }
    
    function is_ssl() {
	if ( isset($_SERVER['HTTPS']) ) {
		if ( 'on' == strtolower($_SERVER['HTTPS']) )
			return true;
		if ( '1' == $_SERVER['HTTPS'] )
			return true;
	} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
		return true;
	}
	return false;
    }
    
    function getLoggedInCookie(){
        return $this->LOGGED_IN_COOKIE;
    }

}

?>