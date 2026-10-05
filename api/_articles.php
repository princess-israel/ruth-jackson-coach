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

/** ---- Translations (French, Spanish, Hindi) ----
 * Translated articles live in data/i18n/articles.<lang>.json, keyed by slug, and are shown
 * ONLY when the visitor has chosen that language in the site language switcher
 * (cookie googtrans=/en/<lang>) or opens the page with ?lang=<lang>. English stays the default.
 */
function articles_langs() { return ['fr', 'es', 'hi']; }
function articles_lang() {
  $l = isset($_GET['lang']) ? strtolower(substr((string)$_GET['lang'], 0, 5)) : '';
  if (!$l && !empty($_COOKIE['googtrans']) && preg_match('#^/[a-z-]+/([a-z-]+)$#i', $_COOKIE['googtrans'], $m)) $l = strtolower($m[1]);
  return in_array($l, articles_langs(), true) ? $l : '';
}
function articles_i18n_load($lang) {
  static $cache = [];
  if (!isset($cache[$lang])) {
    $f = articles_dir() . '/i18n/articles.' . $lang . '.json';
    $j = file_exists($f) ? json_decode(file_get_contents($f), true) : [];
    $cache[$lang] = is_array($j) ? $j : [];
  }
  return $cache[$lang];
}
function articles_localize($a, $lang) {
  if (!$lang || !is_array($a) || empty($a['slug'])) return $a;
  $t = articles_i18n_load($lang);
  if (empty($t[$a['slug']])) return $a;
  foreach (['title', 'excerpt', 'metaTitle', 'metaDescription', 'keywords', 'body', 'faq'] as $k) {
    if (isset($t[$a['slug']][$k]) && $t[$a['slug']][$k] !== '' && $t[$a['slug']][$k] !== []) $a[$k] = $t[$a['slug']][$k];
  }
  $a['_lang'] = $lang;
  return $a;
}
function articles_has_lang($slug, $lang) {
  $t = articles_i18n_load($lang);
  return !empty($t[$slug]);
}
