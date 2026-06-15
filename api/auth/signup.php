<?php
/** POST /api/auth/signup.php { name, email, password } -> { user, token } */
require __DIR__ . '/../_db.php';
require __DIR__ . '/../_orders.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

$b = read_body();
$name  = isset($b['name']) ? trim($b['name']) : '';
$email = isset($b['email']) ? strtolower(trim($b['email'])) : '';
$pass  = isset($b['password']) ? (string)$b['password'] : '';

if ($name === '' || $email === '' || strlen($pass) < 5)
  json_out(400, ['error' => 'Name, email, and a password of at least 5 characters are required.']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
  json_out(400, ['error' => 'Please enter a valid email address.']);

$exists = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$exists->execute([$email]);
if ($exists->fetch()) json_out(409, ['error' => 'An account with that email already exists. Try logging in.']);

$id = uuid();
$ins = db()->prepare('INSERT INTO users (id, name, email, password_hash, role) VALUES (?, ?, ?, ?, "customer")');
$ins->execute([$id, $name, $email, password_hash($pass, PASSWORD_DEFAULT)]);

// Fulfill any guest order already paid under this email (enroll them now).
$paid = db()->prepare('SELECT * FROM orders WHERE email = ? AND status = "COMPLETED"');
$paid->execute([$email]);
foreach ($paid->fetchAll() as $o) order_fulfill($o, $o['confirmation_code']);

$token = session_create($id);
json_out(200, ['user' => public_user(['id'=>$id,'name'=>$name,'email'=>$email,'role'=>'customer']), 'token' => $token]);
