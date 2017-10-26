<?php
//
//  FPDI - Version 1.02beta
//
//    Copyright 2004 Setasign - Jan Slabon
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//


/**
 * ensure that strspn works correct if php-version < 4.3
 */
function _strspn($str1, $str2, $start=null, $length=null) {
    static $phpvar;

    if (!isset($phpver))
        $phpver = (float) phpversion();

    $numargs = func_num_args();

    if ($phpver < 4.3) {
        if (isset($length)) {
            $str1 = substr($str1, $start, $length);
        } else {
            $str1 = substr($str1, $start);
        }
    }

    if ($numargs == 2 || $phpver < 4.3) {
        return strspn($str1, $str2);
    } else if ($numargs == 3) {
        return strspn($str1, $str2, $start);
    } else {
        return strspn($str1, $str2, $start, $length);
    }
}

/**
 * ensure that strcspn works correct if php-version < 4.3
 */
function _strcspn($str1, $str2, $start=null, $length=null) {
    static $phpvar;

    if (!isset($phpver))
        $phpver = (float) phpversion();

    $numargs = func_num_args();

    if ($phpver < 4.3) {
        if (isset($length)) {
            $str1 = substr($str1, $start, $length);
        } else {
            $str1 = substr($str1, $start);
        }
    }

    if ($numargs == 2 || $phpver < 4.3) {
        return strcspn($str1, $str2);
    } else if ($numargs == 3) {
        return strcspn($str1, $str2, $start);
    } else {
        return strcspn($str1, $str2, $start, $length);
    }
}


?>