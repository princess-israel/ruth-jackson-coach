<?php
/** POST /api/auth/logout.php  (Bearer token) -> { ok } */
require __DIR__ . '/../_db.php';
header('Content-Type: application/json');
$tok = bearer_token(read_body());
if ($tok) db()->prepare('DELETE FROM sessions WHERE token = ?')->execute([$tok]);
json_out(200, ['ok' => true]);
