<?php

class Helper extends CApplicationComponent
{

    const JSON_ERROR_NONE_MEANING = 'No error has occurred';
    const JSON_ERROR_DEPTH_MEANING = 'JSON error. The maximum stack depth has been exceeded';
    const JSON_ERROR_STATE_MISMATCH_MEANING = 'Invalid or malformed JSON';
    const JSON_ERROR_CTRL_CHAR_MEANING = 'Control character error, possibly incorrectly encoded in JSON';
    const JSON_ERROR_SYNTAX_MEANING = 'JSON syntax error';
    const JSON_ERROR_UTF8_MEANING = 'JSON error. Malformed UTF-8 characters, possibly incorrectly encoded';
    const JSON_UNKNOWN_ERROR = 'Unknown JSON error';

    public static function getAccessStatusFromRequestByUserRole($parsed_attributes, $default = Constants::ACCESS_STATUS_PUBLISHED)
    {
        $status = $default;

        if (!Yii::app()->user->checkAccess(Users::ROLE_SUPER)) {
            return $default;
        }
        if (
            isset($parsed_attributes['status']) &&
            !empty($parsed_attributes['status']) &&
            in_array($parsed_attributes['status'], array(
                Constants::ACCESS_STATUS_PENDING,
                Constants::ACCESS_STATUS_PUBLISHED,
                Constants::ACCESS_STATUS_REMOVED,
                Constants::ACCESS_STATUS_UNPUBLISHED
            ))
        ) {
            $status = $parsed_attributes['status'];
        }

        return $status;
    }

    /**
     * Using for setting offset
     * @param array $parsed_attributes
     * @return integer
     */
    public static function getOffset($parsed_attributes, $default = 0, $not_more = null)
    {
        $offset = $default;
        if (
            isset($parsed_attributes['offset']) &&
            is_numeric($parsed_attributes['offset']) &&
            !empty($parsed_attributes['offset'])
        )
            $offset = $parsed_attributes['offset'];

        if ($offset < 0)
            $offset = $offset * -1;

        if (!is_null($not_more) && $offset > $not_more)
            $offset = $not_more;


        return $offset;
    }

    /**
     * Using for setting maximum items per request
     * @param array $parsed_attributes
     * @return integer
     */
    public static function getLimit($parsed_attributes, $default = 10, $not_more = null)
    {
        $limit = $default;
        if (
            isset($parsed_attributes['limit']) &&
            is_numeric($parsed_attributes['limit']) &&
            !empty($parsed_attributes['limit'])
        )
            $limit = $parsed_attributes['limit'];

        if ($limit < 0)
            $limit = $limit * -1;

        if (!is_null($not_more) && $limit > $not_more)
            $limit = $not_more;

        return $limit;
    }

    public static function getOrder($parsed_attributes, $prefix = 'order_')
    {
        $order = array();
        foreach ($parsed_attributes as $key => $value) {
            if (preg_match('/^order_(\w+)$/', $key, $matches) && preg_match('/asc|desc/', strtolower($value))) {
                $order[$matches[1]] = $value;
            }
        }
        return $order;
    }

    public static function buildYiiCommandOrder($order_fields, $allowed_fields = array())
    {
        $command_order = '';
        if (empty($order_fields) || !is_array($allowed_fields))
            return $command_order;

        $fields = array();
        foreach ($order_fields as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $fields[] = $key . ' ' . $value;
            }
        }
        if (!empty($fields)) {
            $command_order = implode(',', $fields);
        }

        return $command_order;
    }

    /**
     * Translate access status from usual words onto system constants of access
     * @param type $string
     * @return type
     */
    public static function translateAccessStatus($string)
    {
        $statuses = array(
            'published' => Constants::ACCESS_STATUS_PUBLISHED,
            'removed' => Constants::ACCESS_STATUS_REMOVED,
            'pending' => Constants::ACCESS_STATUS_PENDING,
            'unpublished' => Constants::ACCESS_STATUS_UNPUBLISHED,
        );
        return isset($statuses[$string]) ? $statuses[$string] : false;
    }

    /**
     * It's layout objects,array,string in handy view
     * @param integer, string, array, object, etc. $var
     * @param boolean $print
     * @return string|output to screen
     */
    public static function p($var, $print = true)
    {
        if ($print === true) {
            echo '<pre>';
            print_r($var);
            echo '<pre>';
        } elseif ($print === false) {
            ob_start();
            echo '<pre>';
            print_r($var);
            echo '</pre>';
            $out = ob_get_contents();
            ob_clean();
            return $out;
        }
    }

    /**
     * It returns parameter from Yii configuration, or default value if parameter was not founds
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function yiiparam($name, $default = null)
    {

        if (
            Yii::app()->hasComponent('config') &&
            ($config_param = Yii::app()->config->getValue($name)) &&
            $config_param !== ''
        )
            return $config_param;

        if (isset(Yii::app()->params[$name]))
            return Yii::app()->params[$name];
        else
            return $default;
    }

    /**
     *
     * @param type $var_name
     * @return type
     */
    public static function cutHttpX($var_name)
    {
        if (preg_match('/' . Constants::SERVER_VARIABLE_PREFIX . '/', $var_name)) {
            return strtolower(preg_replace('/' . Constants::SERVER_VARIABLE_PREFIX . '/', '', $var_name));
        }
        return $var_name;
    }

    /**
     *
     * @param array $arr
     * @param SimpleXMLElement $xml
     * @return \SimpleXMLElement
     */
    public function array_to_xml(array $arr, SimpleXMLElement $xml)
    {
        foreach ($arr as $k => $v) {
            $key = $k;
            if (is_numeric($k))
                $key = 'item';

            is_array($v) ? self::array_to_xml($v, $xml->addChild($key)) : $xml->addChild($key, htmlspecialchars($v, ENT_QUOTES));
        }
        return $xml;
    }

    /**
     *
     * @param type $model
     * @return boolean false if such model doesn't exists or capitalized model name if such model exists
     */
    public static function getModelExists($model)
    {
        /* model begins from upper latter */
        $model = ucwords($model);

        /* check if model class exists */
        if (@class_exists($model)) {
            return $model;
        }
        return false;
    }

    /**
     * This static method initializing REST Client extension, that using CURL,
     * and returns it's RESTClient object
     * @param string $server
     * @return \RESTClient
     */
    public static function curlInit($server, $ssl_verifypeer = false)
    {
        $rest = new RESTClient();
        $rest->initialize(array('server' => $server));
        $rest->option('SSL_VERIFYPEER', $ssl_verifypeer);
        return $rest;
    }

    /**
     *
     * @param type $data
     * @return type
     */
    public static function jsonDecode($data)
    {
        if ($encoded = CJSON::decode($data))
            return $encoded;
        return $data;
    }

    /**
     * Returns last error for json
     * @return string
     */
    public static function getJsonLastError()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return self::JSON_ERROR_NONE_MEANING;
            case JSON_ERROR_DEPTH:
                return self::JSON_ERROR_DEPTH_MEANING;
            case JSON_ERROR_STATE_MISMATCH:
                return self::JSON_ERROR_STATE_MISMATCH_MEANING;
            case JSON_ERROR_CTRL_CHAR:
                return self::JSON_ERROR_CTRL_CHAR_MEANING;
            case JSON_ERROR_SYNTAX:
                return self::JSON_ERROR_SYNTAX_MEANING;
            case JSON_ERROR_UTF8:
                return self::JSON_ERROR_UTF8_MEANING;
            default:
                return self::JSON_UNKNOWN_ERROR;
        }
    }

    public static function getFieldsList($array, $fieldname)
    {
        $list = array();
        foreach ($array as $el) {
            if (isset($el[$fieldname])) {
                $list[] = $el[$fieldname];
            }
        }
        return $list;
    }

    public static function getMealsPhotosDir()
    {
        return self::unixSlashes(realpath(Yii::app()->basePath . '/../uploads')) . '/' . Photos::MEALS_UPLOAD_DIRECTORY;
    }

    public static function getMealsPhotosWebPath()
    {
        return Yii::app()->createAbsoluteUrl(ImagesManager::$uploads_folder . Photos::MEALS_UPLOAD_DIRECTORY . '/');
    }

    public static function getAvatarsDir()
    {
        return realpath(self::unixSlashes(Yii::app()->basePath) . '/../uploads') . '/' . Users::AVATARS_UPLOAD_DIRECTORY;
    }

    public static function getAvatarsWebPath()
    {
        return Yii::app()->createAbsoluteUrl(ImagesManager::$uploads_folder . Users::AVATARS_UPLOAD_DIRECTORY . '/');
    }

    public static function unixSlashes($string)
    {
        return preg_replace('/' . preg_quote('\\') . '/', '/', $string);
    }

    public static function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
    {
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $lng1 *= $pi80;
        $lat2 *= $pi80;
        $lng2 *= $pi80;

        $r = 6372.797; // mean radius of Earth in km
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km = $r * $c;

        return ($miles ? ($km * 0.621371192) : $km);
    }

    public static function translitRuToEn($st)
    {

        $st = iconv(mb_detect_encoding($st, mb_detect_order(), true), "UTF-8", $st);
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => "'", 'ы' => 'y', 'ъ' => "'",
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => "'", 'Ы' => 'Y', 'Ъ' => "'",
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );
        return strtr($st, $converter);
    }

    public static function generateToken($len = 32)
    {
        mt_srand((double)microtime() * 1000000 + time());
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZqwertyuiopasdfghjklzxcvbnm_';
        $numChars = strlen($chars) - 1;
        $token = '';
        for ($i = 0; $i < $len; $i++) {
            $token .= $chars[mt_rand(0, $numChars)];
        }
        return $token;
    }

    public static function getParameter($params, $param_name, $equal = null, $default = null)
    {
        if (isset($params[$param_name])) {
            if (is_null($equal) && !empty($params[$param_name])) {
                return $params[$param_name];
            } elseif ($params[$param_name] === $equal) {
                return $params[$param_name];
            }
        }
        return $default;
    }

    public static function getUrlScheme()
    {
        return (Yii::app()->request->isSecureConnection ? "https://" : "http://");
    }

    public static function getStringCheckSum($str)
    {
        return sprintf("%u\n", crc32(strtolower($str))) * 1; //multiply by one to convert string into a number
    }

    public static function filterArray($array, array $fields)
    {
        $filteredArray = array();
        $i = 0;
        foreach ($array as $item) {
            foreach ($item as $key => $val) {
                if (in_array($key, $fields)) {
                    $filteredArray[$i][$key] = $val;
                }
            }
            $i++;
        }
        return $filteredArray;
    }

}
