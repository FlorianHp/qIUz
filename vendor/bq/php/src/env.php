<?php

$envFiles = ['.env', '.env.local'];

function addEnvFile($envFile) {

  if (is_file($envFile)) {
    $handle = fopen($envFile, 'r') or die('Cannot open: ' + $envFile);

    while(!feof($handle)) {
      $line = trim(explode('#', fgets($handle))[0]);

      if (!empty($line)) {
        $i = strpos($line, '=');
        $k = trim(substr($line, 0, $i));
        $v = trim(substr($line, $i + 1));

        $_ENV[$k] = $v;

        if ($k == 'ENVIRONMENT') {
          addEnvFile('.env.' . strtolower($v));
          addEnvFile('.env.' . strtolower($v) . 'local');
        }
      }
    }
    
    fclose($handle);
  }
}

addEnvFile('.env');
addEnvFile('.env.local');