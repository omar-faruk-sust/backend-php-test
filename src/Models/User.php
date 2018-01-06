<?php
/**
 * Created by PhpStorm.
 * User: ofaruk
 * Date: 2018-01-06
 * Time: 1:00 AM
 */

namespace Omar\Models;

use \RedBeanPHP\R as R;
class User
{
    public function login($username, $pass){

        //R::debug(true);
        $result = R::getRow("select * from users WHERE username =? and password=?",
            [$username,md5(SALT.$pass)]);
        if(count($result)>0){
            return $result;
        }

        return array();
    }


    }