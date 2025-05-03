<?php

function faq() {

  $path = __DIR__ . '/../data/faq.json';
  if (!file_exists($path)) {
      return [];
  }
  
  $content = file_get_contents($path);
  $data = json_decode($content, true);

  $result = [];

  foreach ($data ?: [] as $entry) {
      $key = array_key_first($entry);
      $result[] = [
          'major' => $key,
          'items' => $entry[$key]
      ];
  }

  return $result;
}