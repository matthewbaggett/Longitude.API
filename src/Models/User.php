<?php
namespace Longitude\Models;

use \Thru\ActiveRecord\ActiveRecord;
use Thru\UUID;

/**
 * Class User
 * @package Longitude\Models
 * @var $user_id INTEGER
 * @var $user_uuid UUID
 * @var $username STRING
 * @var $displayname STRING
 * @var $phonenumber STRING
 * @var $password STRING(60)
 * @var $email STRING(320)
 * @var $type ENUM("User","Admin")
 * @var $deleted ENUM("Yes","No")
 * @var $banned ENUM("Yes","No")
 * @var $created DATETIME
 * @var $updated DATETIME
 */
class User extends ActiveRecord
{
    protected $_table = "users";

    public $user_id;
    public $user_uuid;
    public $username = '';
    public $displayname = '';
    public $phonenumber = '';
    public $password;
    public $email = '';
    public $type = "User";
    public $deleted = 'No';
    public $banned = 'No';
    public $created;
    public $updated;

    public function isAdmin()
    {
        if ($this->type == 'Admin') {
            return true;
        }
        return false;
    }

    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function checkPassword($password)
    {
        $passwordInfo = password_get_info($this->password);
        // Check for legacy unsalted SHA1
        if (strlen($this->password) == 40 && $passwordInfo['algoName'] == "unknown") {
            if (hash("SHA1", $password) == $this->password) {
                $this->setPassword($password);
                return true;
            }
        }
        if (password_verify($password, $this->password)) {
            // success. But check for needing to be rehashed.
            if (password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
                $this->setPassword($password);
                $this->save();
            }
            return true;
        }else {
            return false;
        }
    }

    public function save($automatic_reload = true)
    {
        if (!$this->user_uuid) {
            $this->user_uuid = UUID::v4();
        }

        // Set the created date & user
        if (!$this->created) {
            $this->created = date("Y-m-d H:i:s");
        }

        // Set the Updated date & user
        $this->updated = date("Y-m-d H:i:s");

        return parent::save($automatic_reload);
    }

    /**
     * @return AuthCode
     */
    public function getNextAuthCode(){
        $authCode = new AuthCode();
        $authCode->user_id = $this->user_id;
        $authCode->save();
        return $authCode;
    }

    public function __toPublicArray()
    {
        $array = parent::__toPublicArray();
        unset($array['password'], $array['deleted'], $array['banned']);
        return $array;
    }
}
