<?php
namespace Longitude\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class Location
 * @package Longitude\Models
 * @var $location_id INTEGER
 * @var $user_id INTEGER
 * @var $lat DOUBLE(12,6)
 * @var $lng DOUBLE(12,6)
 * @var $device STRING
 * @var $created DATETIME
 */
class Location extends ActiveRecord
{
    protected $_table = "locations";
    public $location_id;
    public $user_id;
    public $lat;
    public $lng;
    public $device;
    public $created;

    public function save($automatic_reload = true)
    {
        if (!$this->created) {
            $this->created = date("Y-m-d H:i:s");
        }
        parent::save($automatic_reload);
    }
}
