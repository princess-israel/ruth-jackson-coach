<?php
/**
 * Shared catalog loader. Reads the runtime catalog (data/programs.json, admin-edited)
 * and falls back to the committed defaults (data/programs.default.json).
 * Used by both api/programs.php (admin CRUD) and api/pesapal/pay.php (price authority).
 */

function catalog_dir() { return __DIR__ . '/../data'; }
function catalog_file() { return catalog_dir() . '/programs.json'; }
function catalog_default_file() { return catalog_dir() . '/programs.default.json'; }

function catalog_load() {
  foreach ([catalog_file(), catalog_default_file()] as $f) {
    if (file_exists($f)) {
      $j = json_decode(file_get_contents($f), true);
      if (is_array($j)) return $j;
    }
  }
  return [];
}

function catalog_find($id) {
  foreach (catalog_load() as $p) {
    if (isset($p['id']) && $p['id'] === $id) return $p;
  }
  return null;
}

function catalog_save($programs) {
  $dir = catalog_dir();
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents(catalog_file(), json_encode(array_values($programs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
}
