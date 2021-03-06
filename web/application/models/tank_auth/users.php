<?php
/**
 * Cobub Razor
 *
 * An open source mobile analytics system
 *
 * PHP versions 5
 *
 * @category  MobileAnalytics
 * @package   CobubRazor
 * @author    Cobub Team <open.cobub@gmail.com>
 * @copyright 2011-2016 NanJing Western Bridge Co.,Ltd.
 * @license   http://www.cobub.com/docs/en:razor:license GPL Version 3
 * @link      http://www.cobub.com
 * @since     Version 0.1
 */

/**
 * Hint Message
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Users
 *
 * This model represents user authentication data. It operates the following
 * tables:
 * - user account data,
 * - user profiles
 *
 * @category PHP
 * @package  Model
 * @author   Cobub Team <open.cobub@gmail.com>
 * @license  http://www.cobub.com/docs/en:razor:license GPL Version 3
 * @link     http://www.cobub.com
 */
class Users extends CI_Model
{

    private $table_name = 'users'; // user accounts
    private $profile_table_name = 'user_profiles'; // user profiles
    function __construct ()
    {
        parent::__construct();
        
        $ci = & get_instance();
        $this->load->database();
        $this->table_name = $ci->config->item('db_table_prefix', 'tank_auth') .
                 $this->table_name;
        $this->profile_table_name = $ci->config->item('db_table_prefix', 
                'tank_auth') . $this->profile_table_name;
    }

    /**
     * Get user record by Id
     *
     * @param
     *            int
     * @param
     *            bool
     * @return object
     */
    function get_user_by_id ($user_id, $activated)
    {
        $this->db->where('id', $user_id);
        $this->db->where('activated', $activated ? 1 : 0);
        
        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    /**
     * Get user record by login (username or email)
     *
     * @param
     *            string
     * @return object
     */
    function get_user_by_login ($login)
    {
        $this->db->where('LOWER(username)=', strtolower($login));
        $this->db->or_where('LOWER(email)=', strtolower($login));
        
        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    function insertDefaultRole ($email)
    {
        $user = $this->get_user_by_email($email);
        if ($user != null && isset($user->id)) {
            $data = array(
                    'userid' => $user->id
            );
            $this->db->insert('user2role', $data);
        }
    }

    /**
     * Get user record by username
     *
     * @param
     *            string
     * @return object
     */
    function get_user_by_username ($username)
    {
        $this->db->where('LOWER(username)=', strtolower($username));
        
        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    /**
     * Get user record by email
     *
     * @param
     *            string
     * @return object
     */
    function get_user_by_email ($email)
    {
        $this->db->where('LOWER(email)=', strtolower($email));
        
        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    /**
     * Check if username available for registering
     *
     * @param
     *            string
     * @return bool
     */
    function is_username_available ($username)
    {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(username)=', strtolower($username));
        
        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 0;
    }

    /**
     * Check if email available for registering
     *
     * @param
     *            string
     * @return bool
     */
    function is_email_available ($email)
    {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(email)=', strtolower($email));
        $this->db->or_where('LOWER(new_email)=', strtolower($email));
        
        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 0;
    }

    /**
     * Create new user record
     *
     * @param
     *            array
     * @param
     *            bool
     * @return array
     */
    function create_user ($data, $activated = TRUE)
    {
        $data['created'] = date('Y-m-d H:i:s');
        $data['activated'] = $activated ? 1 : 0;
        
        if ($this->db->insert($this->table_name, $data)) {
            $user_id = $this->db->insert_id();
            if ($activated)
                $this->create_profile($user_id);
            return array(
                    'user_id' => $user_id
            );
        }
        return NULL;
    }

    /**
     * Activate user if activation key is valid.
     * Can be called for not activated users only.
     *
     * @param
     *            int
     * @param
     *            string
     * @param
     *            bool
     * @return bool
     */
    function activate_user ($user_id, $activation_key, $activate_by_email)
    {
        $this->db->select('1', FALSE);
        $this->db->where('id', $user_id);
        if ($activate_by_email) {
            $this->db->where('new_email_key', $activation_key);
        } else {
            $this->db->where('new_password_key', $activation_key);
        }
        $this->db->where('activated', 0);
        $query = $this->db->get($this->table_name);
        
        if ($query->num_rows() == 1) {
            
            $this->db->set('activated', 1);
            $this->db->set('new_email_key', NULL);
            $this->db->where('id', $user_id);
            $this->db->update($this->table_name);
            
            $this->create_profile($user_id);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Purge table of non-activated users
     *
     * @param
     *            int
     * @return void
     */
    function purge_na ($expire_period = 172800)
    {
        $this->db->where('activated', 0);
        $this->db->where('UNIX_TIMESTAMP(created) <', time() - $expire_period);
        $this->db->delete($this->table_name);
    }

    /**
     * Delete user record
     *
     * @param
     *            int
     * @return bool
     */
    function delete_user ($user_id)
    {
        $this->db->where('id', $user_id);
        $this->db->delete($this->table_name);
        if ($this->db->affected_rows() > 0) {
            $this->delete_profile($user_id);
            return true;
        }
        return false;
    }

    /**
     * Set new password key for user.
     * This key can be used for authentication when resetting user's password.
     *
     * @param
     *            int
     * @param
     *            string
     * @return bool
     */
    function set_password_key ($user_id, $new_pass_key)
    {
        $this->db->set('new_password_key', $new_pass_key);
        date_default_timezone_set('PRC');
        $this->db->set('new_password_requested', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        
        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Can_reset_password function 
     * Check if given password key is valid and user is authenticated.
     *
     * @param int    $user_id       user id
     * @param string $new_pass_key  a new password key
     * @param  int   $expire_period expire_period = 900
     * 
     * @return void
     */
    function can_reset_password ($user_id, $new_pass_key, $expire_period = 900)
    {
        $this->db->select('1', false);
        $this->db->where('id', $user_id);
        $this->db->where('new_password_key', $new_pass_key);
        $this->db->where('UNIX_TIMESTAMP(new_password_requested) >', 
                time() - $expire_period);
        // echo time() - $expire_period;
        // echo "select * from users where id = $user_id and new_password_key =
        // '$new_pass_key' and UNIX_TIMESTAMP(new_password_requested) >".(time()
        // - $expire_period);
        $query = $this->db->get($this->table_name);
        // echo $query->num_rows();
        return $query->num_rows() == 1;
    }

    /**
     * Reset_password function
     * Change user password if password key is valid and user is authenticated.
     *
     * @param int    $user_id       user id
     * @param string $new_pass      a new password
     * @param string $new_pass_key  a new password key
     * @param int    $expire_period expire_period = 900
     * 
     * @return bool
     */
    function reset_password ($user_id, $new_pass, $new_pass_key, 
            $expire_period = 900)
    {
        $this->db->set('password', $new_pass);
        $this->db->set('new_password_key', null);
        $this->db->set('new_password_requested', null);
        $this->db->where('id', $user_id);
        $this->db->where('new_password_key', $new_pass_key);
        $this->db->where('UNIX_TIMESTAMP(new_password_requested) >=', 
                time() - $expire_period);
        
        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Change_password function
     * Change user password
     *
     * @param int    $user_id  user id
     * @param string $new_pass a new password
     * 
     * @return bool
     */
    function change_password ($user_id, $new_pass)
    {
        $this->db->set('password', $new_pass);
        $this->db->where('id', $user_id);
        
        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Set_new_email function
     * Set new email for user (may be activated or not).
     * The new email cannot be used for login or notification before it is
     * activated.
     *
     * @param int    $user_id       user id
     * @param string $new_email     a new email
     * @param string $new_email_key a new email key
     * @param  bool  $activated     activated
     * 
     * @return bool
     */
    function set_new_email ($user_id, $new_email, $new_email_key, $activated)
    {
        $this->db->set($activated ? 'new_email' : 'email', $new_email);
        $this->db->set('new_email_key', $new_email_key);
        $this->db->where('id', $user_id);
        $this->db->where('activated', $activated ? 1 : 0);
        
        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Activate_new_emai functionl
     * Activate new email (replace old email with new one) if activation key is
     * valid.
     *
     * @param int    $user_id       user id
     * @param string $new_email_key a new email key
     * 
     * @return bool
     */
    function activate_new_email ($user_id, $new_email_key)
    {
        $this->db->set('email', 'new_email', FALSE);
        $this->db->set('new_email', NULL);
        $this->db->set('new_email_key', NULL);
        $this->db->where('id', $user_id);
        $this->db->where('new_email_key', $new_email_key);
        
        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Update_login_info function
     * Update user login info, such as IP-address or login time, and
     * clear previously generated (but not activated) passwords.
     *
     * @param int  $user_id     user id
     * @param bool $record_ip   true or false
     * @param bool $record_time true or false
     * 
     * @return void
     */
    function update_login_info ($user_id, $record_ip, $record_time)
    {
        $this->db->set('new_password_key', NULL);
        $this->db->set('new_password_requested', NULL);
        
        if ($record_ip)
            $this->db->set('last_ip', $this->input->ip_address());
        if ($record_time)
            $this->db->set('last_login', date('Y-m-d H:i:s'));
        
        $this->db->where('id', $user_id);
        $this->db->update($this->table_name);
    }

    /**
     * Ban_user function 
     * Ban user
     *
     * @param int    $user_id user id
     * @param string $reason  $reason=null
     * 
     * @return void
     */
    function ban_user ($user_id, $reason = null)
    {
        $this->db->where('id', $user_id);
        $this->db->update($this->table_name, 
                array(
                        'banned' => 1,
                        'ban_reason' => $reason
                ));
    }

    /**
     * Unban_user function
     * Unban user
     *
     * @param int $user_id user id
     * 
     * @return void
     */
    function unban_user ($user_id)
    {
        $this->db->where('id', $user_id);
        $this->db->update($this->table_name, 
                array(
                        'banned' => 0,
                        'ban_reason' => null
                ));
    }

    /**
     * Create_profile function
     * Create an empty profile for a new user
     *
     * @param int $user_id user id
     * 
     * @return bool
     */
    private function create_profile ($user_id)
    {
        $this->db->set('user_id', $user_id);
        return $this->db->insert($this->profile_table_name);
    }

    /**
     * Delete_profile function
     * Delete user profile
     *
     * @param int $user_id user id
     * 
     * @return void
     */
    private function delete_profile ($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->delete($this->profile_table_name);
    }
}

/* End of file users.php */
/* Location: ./application/models/auth/users.php */