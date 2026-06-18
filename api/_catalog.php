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
  // Merge committed defaults (base) with the admin-edited runtime catalog.
  // Defaults provide any program not yet in the runtime file (e.g. newly added
  // courses); runtime entries override default fields (e.g. admin price edits).
  $read = function ($f) {
    if (file_exists($f)) { $j = json_decode(file_get_contents($f), true); if (is_array($j)) return $j; }
    return [];
  };
  $defaults = $read(catalog_default_file());
  $runtime  = $read(catalog_file());

  $byId = [];
  foreach ($defaults as $p) { if (isset($p['id'])) $byId[$p['id']] = $p; }
  foreach ($runtime as $p) {
    if (!isset($p['id'])) continue;
    $byId[$p['id']] = isset($byId[$p['id']]) ? array_merge($byId[$p['id']], $p) : $p;
  }
  return array_values($byId);
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
