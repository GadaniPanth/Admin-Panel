<?php

class Common_model extends CI_Model
{
    public function checkjson(&$json) {
        $json = json_decode($json);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}