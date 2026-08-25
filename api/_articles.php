<?php
/** Shared articles loader. Runtime file (data/articles.json) overrides committed defaults. */
function articles_dir() { return __DIR__ . '/../data'; }
function articles_file() { return articles_dir() . '/articles.json'; }
function articles_default_file() { return articles_dir() . '/articles.default.json'; }

function articles_load() {
  // Merge committed defaults (data/articles.default.json, version-controlled and
  // deployed from git) with the runtime file (data/articles.json, written by the
  // admin panel). Defaults are the base; runtime entries with the same slug win,
  // so admin edits still override. This lets articles published via git appear
  // live even when a runtime file exists, instead of being hidden by it.
  $bySlug = [];
  $order  = [];
  foreach ([articles_default_file(), articles_file()] as $f) {
    if (!file_exists($f)) continue;
    $j = json_decode(file_get_contents($f), true);
    if (!is_array($j)) continue;
    foreach ($j as $a) {
      $key = isset($a['slug']) && $a['slug'] !== '' ? $a['slug'] : ('#' . count($order));
      if (!array_key_exists($key, $bySlug)) $order[] = $key;
      $bySlug[$key] = $a;
    }
  }
  $out = [];
  foreach ($order as $k) $out[] = $bySlug[$k];
  return $out;
}
function articles_sorted() {
  $list = articles_load();
  usort($list, function ($a, $b) { return strcmp($b['date'] ?? '', $a['date'] ?? ''); });
  return $list;
}
function articles_find($slug) {
  foreach (articles_load() as $a) {
    if (isset($a['slug']) && $a['slug'] === $slug) return $a;
  }
  return null;
}
function articles_save($arr) {
  $d = articles_dir();
  if (!is_dir($d)) @mkdir($d, 0755, true);
  return file_put_contents(articles_file(), json_encode(array_values($arr), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
}
