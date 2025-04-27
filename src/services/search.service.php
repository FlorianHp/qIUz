<?php

function flat($e) {
  return is_array($e) ? implode(' ', array_map('flat', array_values($e))) : str_replace(['-', '_'], '', (string) $e);
};

function search(array $list, string $q, callable $useId, string $name = 'search') {
  $q = str_replace(['-', '_'], '', strtolower($q));

  return array_filter($list, fn($e) => str_contains(strtolower(flat($e)), $q));
}