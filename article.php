<?php
require __DIR__ . '/api/_articles.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$a = $slug !== '' ? articles_find($slug) : null;

$base = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'coachruthjackson.com');
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$ARTICLE_IMG = [
  'ai-for-small-business'       => 'assets/img/article-ai.jpg',
  'customer-service-that-sells' => 'assets/img/article-customer-service.jpg',
  'women-digital-economy'       => 'assets/img/article-women.jpg',
];
$artImg = $a ? (!empty($a['image']) ? $a['image'] : ($ARTICLE_IMG[$slug] ?? '')) : '';

if (!$a) {
  http_response_code(404);
  $title = 'Article not found';
  $desc  = '';
} else {
  $title = !empty($a['metaTitle']) ? $a['metaTitle'] : $a['title'] . ' | Ruth Jackson';
  $desc  = !empty($a['metaDescription']) ? $a['metaDescription'] : ($a['excerpt'] ?? '');
}
$canonical = $base . '/article.php?slug=' . urlencode($slug);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if ($a): ?>
<meta name="description" content="<?= e($desc) ?>">
<meta name="keywords" content="<?= e(implode(', ', $a['keywords'] ?? [])) ?>">
<meta name="author" content="<?= e($a['author'] ?? 'Ruth Jackson') ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="article">
<meta property="og:title" content="<?= e($a['title']) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($base) ?>/<?= e($artImg ?: 'assets/img/og-default.jpg') ?>">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Article",
 "headline":<?= json_encode($a['title']) ?>,
 "description":<?= json_encode($desc) ?>,
 "datePublished":<?= json_encode($a['date'] ?? '') ?>,
 "author":{"@type":"Person","name":<?= json_encode($a['author'] ?? 'Ruth Jackson') ?>},
 "publisher":{"@type":"Person","name":"Ruth Jackson"},
 "mainEntityOfPage":<?= json_encode($canonical) ?>}
</script>
<?php else: ?>
<meta name="robots" content="noindex">
<?php endif; ?>
<meta name="theme-color" content="#070e29">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<header class="nav">
  <div class="container nav-inner">
    <a class="brand" href="index.html"><span class="logo">RJ</span><span>Ruth Jackson<small>AI Coach</small></span></a>
    <nav><ul class="nav-links">
      <li><a href="programs.html">Programs</a></li>
      <li><a href="customer-service.html">Customer Service</a></li>
      <li><a href="about.html">About</a></li>
      <li><a href="blog.html">Articles</a></li>
    </ul></nav>
    <div class="nav-cta"><a class="btn btn-gold btn-sm" href="programs.html">Enroll now</a>
    <button class="hamburger" aria-label="Menu"><span></span><span></span><span></span></button></div>
  </div>
</header>

<main class="page-pad section">
  <div class="container">
<?php if (!$a): ?>
    <div class="center">
      <h1>Article not found</h1>
      <p class="muted">It may have been moved or unpublished.</p>
      <a class="btn btn-gold" href="blog.html">Back to all articles</a>
    </div>
<?php else: ?>
    <article class="article">
      <a href="blog.html" class="muted" style="font-size:.9rem">← All articles</a>
      <div class="cat" style="color:var(--azure);text-transform:uppercase;letter-spacing:.08em;font-weight:600;font-size:.78rem;margin:18px 0 8px"><?= e($a['category'] ?? 'Article') ?></div>
      <h1><?= e($a['title']) ?></h1>
      <div class="meta">
        <span>By <?= e($a['author'] ?? 'Ruth Jackson') ?></span>
        <?php if (!empty($a['date'])): ?><span>· <?= e(date('M j, Y', strtotime($a['date']))) ?></span><?php endif; ?>
        <?php if (!empty($a['readMins'])): ?><span>· <?= e($a['readMins']) ?> min read</span><?php endif; ?>
      </div>
      <?php if ($artImg): ?><img class="article-hero-img" src="<?= e($artImg) ?>" alt="<?= e($a['title']) ?>"><?php endif; ?>
      <div class="article-body"><?= $a['body'] ?? '' ?></div>

      <div class="cta-band reveal" style="margin-top:50px">
        <h2 style="font-size:1.6rem">Ready to turn this into real skills?</h2>
        <p class="muted" style="margin-bottom:20px">Self-paced certificate courses, or custom training with Ruth.</p>
        <a class="btn btn-gold" href="programs.html">Browse programs →</a>
      </div>
    </article>
<?php endif; ?>
  </div>
</main>

<footer class="footer"><div class="container"><div class="footer-bottom" style="border:0">
  <span>© <span data-year></span> Ruth Jackson.</span><span><a href="about.html">About</a> · <a href="tel:+254729384374">+254 729 384374</a></span>
</div></div></footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="assets/js/data.js"></script>
<script src="assets/js/store.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/chat.js"></script>
</body>
</html>
