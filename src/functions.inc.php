<?php

define ("SCOPES_DIR", "/usr/share/nethvoice/tancredi/data/scopes/");

function storageRead($id) {
    $inifile = SCOPES_DIR . $id . '.ini';
    return _readIniFile($inifile);
}

function storageWrite($id,$data) {
    $inifile = SCOPES_DIR . $id . '.ini';
    return _writeIniFile($inifile,$data);
}

function _readIniFile($inifile) {
    // Read vars from file
    if (file_exists($inifile)){
        return parse_ini_file($inifile, $process_sections = TRUE);
    } else {
        return array();
    }
}

/**
 * Write an ini configuration file
 * 
 * @param string $file
 * @param array  $array
 * @return bool
 */
function _writeIniFile($file, $array = []) {
    // check first argument is string
    if (!is_string($file)) {
        throw new \InvalidArgumentException('Function argument 1 must be a string.');
    }

    // check second argument is array
    if (!is_array($array)) {
        throw new \InvalidArgumentException('Function argument 2 must be an array.');
    }

    // process array
    $data = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $data[] = "[$key]";
            foreach ($val as $skey => $sval) {
                if (is_array($sval)) {
                    foreach ($sval as $_skey => $_sval) {
                        if (is_numeric($_skey)) {
                            $data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                        } else {
                            $data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                        }
                    }
                } else {
                    $data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
                }
            }
        } else {
            $data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
        }
        // empty line
        $data[] = null;
    }

    // open file pointer, init flock options
    $fp = fopen($file, 'w');
    $retries = 0;
    $max_retries = 100;

    if (!$fp) {
        return false;
    }

    // loop until get lock, or reach max retries
    do {
        if ($retries > 0) {
            usleep(rand(1, 5000));
        }
        $retries += 1;
    } while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

    // couldn't get the lock
    if ($retries == $max_retries) {
        return false;
    }

    // got lock, write data
    fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);

    // release lock
    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
} 

function _getScopeType($scope){
    $vars = _readIniFile(SCOPES_DIR . $scope . '.ini');
    if (array_key_exists('metadata', $vars) and is_array($vars['metadata']) and array_key_exists('scopeType',$vars['metadata'])) {
        return $vars['metadata']['scopeType'];
    }
    return null;
}

function listScopes($typeFilter = null){
    $scopes = array();
    foreach (scandir(SCOPES_DIR) as $filename) {
        if ($filename === '.' or $filename === '..' or preg_match('/\.ini$/',$filename) === FALSE) continue;
        $scope = preg_replace('/\.ini$/','',$filename);
        if (is_null($typeFilter) or _getScopeType($scope) === $typeFilter) {
            $scopes[] = $scope;
        }
    }
    return $scopes;
}



