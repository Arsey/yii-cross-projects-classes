<?php

/**
 * It's layout objects,array,string in handy view
 * @param integer, string, array, object, etc. $var
 * @param boolean $print
 * @return string|output to screen
 */
class helper extends CApplicationComponent {

    public static function p($var, $print = true) {
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

}