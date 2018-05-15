<?php
namespace Swoole;

/**
 * 用户验证类
 * @author Han Tianfeng
 * @package SwooleSystem
 * @subpackage Login
 */
class Auth
{
    public $select = '*';
    public $db = '';
    public $user;
    public $profile;
    public $is_login = true;
    public $dict;

    public $errCode;
    public $errMessage;

    static $login_url = '/login.php?';
    static $username = 'username';
    static $password = 'password';
    static $userid = 'id';

    static $lastlogin = 'lastlogin';
    static $lastip = 'lastip';
    static $session_prefix = '';
    static $mk_password = 'username,password';
    static $password_hash = 'sha1';

    static $password_cost = 10;
    static $password_salt_size = 22;

    static $cookie_life = 2592000;
    static $session_destroy = false;

    protected $config;
    protected $login_table = '';
    protected $login_db = '';
    protected $profile_table = '';

    const ERR_NO_EXIST = 1;
    const ERR_PASSWORD = 2;

    const HASH_SHA1 = 'sha1';
    const HASH_CRYPT = 'crypt';

    function __construct($config)
    {
        $this->config = $config;
        if (empty($config['login_table']))
        {
            throw new \Exception(__CLASS__ . ' request login_table config.');
        }
        if (!empty($config['login_db']))
        {
            $this->login_db = $config['login_db'];
        }
        else
        {
            $this->login_db = 'master';
        }

        $this->login_table = $config['login_table'];
        $this->db = \Swoole::$php->db($this->login_db);
        $_SESSION[self::$session_prefix . 'save_key'] = array();
    }

    function saveUserinfo($key='userinfo')
    {
        $_SESSION[self::$session_prefix.$key] = $this->user;
        $_SESSION[self::$session_prefix.'save_key'][] = self::$session_prefix.$key;
    }
    /**
     * 更新用户信息
     * @param $set
     * @return bool
     */
    function updateStatus($set = null)
    {
        if (empty($set))
        {
            $set = array(
                self::$lastlogin => date('Y-m-d H:i:s'),
                self::$lastip => \Swoole::$php->request->getClientIP()
            );
        }
        return $this->db->update($this->user['id'], $set, $this->login_table);
    }

    function setSession($key)
    {
        $_SESSION[$key] = $this->user[$key];
        $_SESSION[self::$session_prefix . 'save_key'][] = self::$session_prefix . $key;
    }

    /**
     * 获取登录用户的UID
     * @return int
     */
    function getUid()
    {
        return $_SESSION[self::$session_prefix.'user_id'];
    }

    /**
     * 获取登录用户的信息
     * @return array
     */
    function getUserInfo($key = 'userinfo')
    {
        return $this->user;
    }

    /**
     * 登录
     * @param $username
     * @param $password
     * @return bool
     */
    function login($username, $password)
    {
        Cookie::set(self::$session_prefix . 'username', $username, time() + self::$cookie_life, '/');
        $this->user = $this->db->query('select ' . $this->select . ' from ' . $this->login_table . " where " . self::$username . "='$username' limit 1")->fetch();
        if (empty($this->user))
        {
            $this->errCode = self::ERR_NO_EXIST;
            return false;
        }
        else
        {
            //验证密码是否正确
            if (self::verifyPassword($username, $password, $this->user[self::$password]))
            {
                $_SESSION[self::$session_prefix . 'isLogin'] = true;
                $_SESSION[self::$session_prefix . 'user_id'] = $this->user['id'];
                return true;
            }
            else
            {
                $this->errCode = self::ERR_PASSWORD;
                return false;
            }
        }
    }

    /**
     * 检查是否登录
     * @return bool
     */
    function isLogin()
    {
        if (isset($_SESSION[self::$session_prefix . 'isLogin']) and $_SESSION[self::$session_prefix . 'isLogin'] == 1)
        {
            return true;
        }
        return false;
    }

    /**
     * 修改密码
     * @param $uid
     * @param $old_pwd
     * @param $new_pwd
     * @return bool
     * @throws \Exception
     */
    function changePassword($uid, $old_pwd, $new_pwd)
    {
        $table = table($this->login_table, $this->login_db);
        $table->primary = self::$userid;
        $_res = $table->gets(array('select' => self::$username . ',' . self::$password, 'limit' => 1, self::$userid => $uid));
        if (count($_res) < 1)
        {
            $this->errMessage = '用户不存在';
            $this->errCode = 1;
            return false;
        }

        $user = $_res[0];
        if ($user[self::$password] != self::makePasswordHash($user[self::$username], $old_pwd))
        {
            $this->errMessage = '原密码不正确';
            $this->errCode = 2;
            return false;
        }
        else
        {
            $table->set($uid, array(self::$password => self::makePasswordHash($user[self::$username], $new_pwd)), self::$userid);
            return true;
        }
    }

    /**
     * 注销登录
     * @return bool
     */
    function logout()
    {
        /**
         * 启动Session
         */
        if (!\Swoole::$php->session->isStart)
        {
            \Swoole::$php->session->start();
        }
        /**
         * 如果设置为true，退出登录时，销毁所有Session
         */
        if (self::$session_destroy)
        {
            $_SESSION = array();
            return true;
        }
        unset($_SESSION[self::$session_prefix . 'isLogin']);
        unset($_SESSION[self::$session_prefix . 'user_id']);
        if (!empty($_SESSION[self::$session_prefix . 'save_key']))
        {
            foreach ($_SESSION[self::$session_prefix . 'save_key'] as $sk)
            {
                unset($_SESSION[$sk]);
            }
        }
        unset($_SESSION[self::$session_prefix . 'save_key']);
        return true;
    }

    /**
     * 验证密码
     * @param $username
     * @param $input_password
     * @param $real_password
     * @return bool
     * @throws \Exception
     */
    public static function verifyPassword($username, $input_password, $real_password)
    {
        //使用PHP内置的password
        if (self::$password_hash == 'crypt')
        {
            if (!function_exists('password_verify'))
            {
                throw new \Exception("require password_verify function.");
            }
            return password_verify($input_password, $real_password);
        }
        else
        {
            $pwd_hash = self::makePasswordHash($username, $input_password);
            return $real_password === $pwd_hash;
        }
    }

    /**
     * 产生一个密码串，连接用户名和密码，并使用sha1产生散列
     * @param $username
     * @param $password
     * @throws \Exception
     * @return string
     */
    public static function makePasswordHash($username, $password)
    {
        //sha1 用户名+密码
        if (self::$password_hash == 'sha1')
        {
            return sha1($username . $password);
        }
        //使用PHP内置的password
        elseif(self::$password_hash == 'crypt')
        {
            if (!function_exists('password_hash'))
            {
                throw new \Exception("require password_hash function.");
            }
            $options = [
                'cost' => self::$password_cost,
                'salt' => mcrypt_create_iv(self::$password_salt_size, MCRYPT_DEV_URANDOM),
            ];
            return password_hash($password, PASSWORD_BCRYPT, $options);
        }
        //md5 用户名+密码
        elseif (self::$password_hash == 'md5')
        {
            return md5($username . $password);
        }
        elseif (self::$password_hash == 'sha1_single')
        {
            return sha1($password);
        }
        elseif (self::$password_hash == 'md5_single')
        {
            return md5($password);
        }
        return false;
    }

    /**
     * 验证登录
     * @return bool
     */
    public static function loginRequire()
    {
        /**
         * 启动Session
         */
        if (!\Swoole::$php->session->isStart)
        {
            \Swoole::$php->session->start();
        }
        $user = \Swoole::$php->user;
        if (!$user->isLogin())
        {
            if (strpos($_SERVER["REQUEST_URI"], '?') === false and !empty($_SERVER['QUERY_STRING']))
            {
                $url = $_SERVER["REQUEST_URI"] . '?' . $_SERVER['QUERY_STRING'];
            }
            else
            {
                $url = $_SERVER["REQUEST_URI"];
            }
            $login_url = $user->config['login_url'] . '?refer=' . urlencode($url);
            \Swoole::$php->http->redirect($login_url);
            return false;
        }
        return true;
    }
}

