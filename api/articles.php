<?php
/**
 * GET  /api/articles.php           -> { articles: [...] }   (newest first)
 * GET  /api/articles.php?slug=...  -> { article: {...} }
 * POST /api/articles.php           -> add/edit/delete (requires admin token)
 *   body: { token, action:"save"|"delete", article?:{...}, slug?:"..." }
 */
require __DIR__ . '/_articles.php';
require __DIR__ . '/_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-LiteSpeed-Cache-Control: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
  if ($slug !== '') {
    $a = articles_find($slug);
    if (!$a) { http_response_code(404); echo json_encode(['error' => 'Article not found']); exit; }
    echo json_encode(['article' => $a]); exit;
  }
  echo json_encode(['articles' => articles_sorted()]); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = $_POST;

// --- auth (single admin secret) ---
if (admin_secret() === '') {
  http_response_code(403);
  echo json_encode(['error' => "No admin secret configured. Add  'admin_token' => 'your-secret'  to api/pesapal/config.php."]);
  exit;
}
if (!admin_token_valid(isset($body['token']) ? $body['token'] : '')) {
  http_response_code(401); echo json_encode(['error' => 'Not authorised. Please log in to the admin again.']); exit;
}

$articles = articles_load();
$action = isset($body['action']) ? $body['action'] : 'save';

if ($action === 'delete') {
  $slug = isset($body['slug']) ? $body['slug'] : '';
  $articles = array_values(array_filter($articles, function ($a) use ($slug) {
    return !isset($a['slug']) || $a['slug'] !== $slug;
  }));
} else {
  $a = isset($body['article']) ? $body['article'] : null;
  if (!$a || empty($a['title'])) { http_response_code(400); echo json_encode(['error' => 'A title is required.']); exit; }
  $slugSource = !empty($a['slug']) ? $a['slug'] : $a['title'];
  $a['slug'] = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', strtolower($slugSource))), '-');
  if ($a['slug'] === '') $a['slug'] = 'article-' . time();
  if (empty($a['date'])) $a['date'] = date('Y-m-d');
  if (empty($a['author'])) $a['author'] = 'Ruth Jackson';
  $a['keywords'] = (isset($a['keywords']) && is_array($a['keywords'])) ? array_values($a['keywords']) : [];

  $found = false;
  foreach ($articles as $i => $ex) {
    if (isset($ex['slug']) && $ex['slug'] === $a['slug']) { $articles[$i] = array_merge($ex, $a); $found = true; break; }
  }
  if (!$found) $articles[] = $a;
}

if (!articles_save($articles)) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not write articles file. Make the data/ folder writable (0755).']);
  exit;
}
echo json_encode(['ok' => true, 'articles' => $articles]);
