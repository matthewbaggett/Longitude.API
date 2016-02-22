<?php
namespace Longitude\Models;

use \Thru\ActiveRecord\ActiveRecord;
use Thru\UUID;

/**
 * Class AuthCode
 * @package Longitude\Models
 * @var $auth_code_id INTEGER
 * @var $user_id INTEGER
 * @var $auth_code UUID
 * @var $created DATETIME
 */
class AuthCode extends ActiveRecord
{
    protected $_table = "users_authcodes";
    public $auth_code_id;
    public $user_id;
    public $auth_code;
    public $created;

    private $_user;

    public function save($automatic_reload = true)
    {
        if(!$this->auth_code){
            $this->auth_code = UUID::v4();
        }
        if (!$this->created) {
            $this->created = date("Y-m-d H:i:s");
        }
        parent::save($automatic_reload);
    }

    /**
     * @return $this
     * @throws \Thru\ActiveRecord\Exception
     */
    public function getUser(){
        if(!$this->_user) {
            $this->_user = User::search()->where('user_id', $this->user_id);
        }
        return $this->_user;
    }
}
