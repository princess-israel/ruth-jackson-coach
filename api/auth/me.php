<?php
/** GET /api/auth/me.php  (Bearer token) -> { user }  */
require __DIR__ . '/../_db.php';
header('Content-Type: application/json');
$u = user_from_token(bearer_token(read_body()));
if (!$u) json_out(401, ['error' => 'Not signed in.']);
json_out(200, ['user' => public_user($u)]);
