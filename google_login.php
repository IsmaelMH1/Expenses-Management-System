<?php
require_once __DIR__ . "/db.php";

/**
 * Verify Firebase ID token (JWT) without Composer.
 * Uses Google's public certs for securetoken.
 */

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$id_token = (string)($input["id_token"] ?? "");

if ($id_token === "") {
  json_response(["ok" => false, "error" => "id_token is required"], 400);
}

// ✅ MUST match Firebase projectId
$FIREBASE_PROJECT_ID = "expenses-33694";

function b64url_decode($data) {
  $remainder = strlen($data) % 4;
  if ($remainder) $data .= str_repeat('=', 4 - $remainder);
  $data = strtr($data, '-_', '+/');
  return base64_decode($data);
}

function fetch_certs_cached($cacheFile, $maxAgeSeconds = 3600) {
  if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $maxAgeSeconds)) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    if (is_array($cached)) return $cached;
  }

  $url = "https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com";
  $ctx = stream_context_create([
    "http" => ["timeout" => 10]
  ]);

  $json = @file_get_contents($url, false, $ctx);
  if ($json === false) return null;

  @file_put_contents($cacheFile, $json);
  $certs = json_decode($json, true);
  return is_array($certs) ? $certs : null;
}

function verify_firebase_jwt($jwt, $projectId) {
  $parts = explode('.', $jwt);
  if (count($parts) !== 3) return ["ok" => false, "error" => "Invalid JWT format"];

  [$h64, $p64, $s64] = $parts;

  $header = json_decode(b64url_decode($h64), true);
  $payload = json_decode(b64url_decode($p64), true);
  $sig = b64url_decode($s64);

  if (!is_array($header) || !is_array($payload) || $sig === false) {
    return ["ok" => false, "error" => "Invalid JWT encoding"];
  }

  if (($header["alg"] ?? "") !== "RS256") {
    return ["ok" => false, "error" => "Unsupported alg"];
  }

  $kid = $header["kid"] ?? "";
  if ($kid === "") return ["ok" => false, "error" => "Missing kid"];

  // Fetch certs (cached)
  $cacheFile = __DIR__ . "/.firebase_certs_cache.json";
  $certs = fetch_certs_cached($cacheFile, 3600);
  if (!$certs || !isset($certs[$kid])) {
    // Try once more without cache (in case of rotation)
    @unlink($cacheFile);
    $certs = fetch_certs_cached($cacheFile, 3600);
    if (!$certs || !isset($certs[$kid])) {
      return ["ok" => false, "error" => "Cert not found for kid (key rotated?)"];
    }
  }

  $pem = $certs[$kid];

  // Verify signature
  $dataToVerify = $h64 . "." . $p64;
  $pubKey = openssl_pkey_get_public($pem);
  if (!$pubKey) return ["ok" => false, "error" => "Invalid public key"];

  $ok = openssl_verify($dataToVerify, $sig, $pubKey, OPENSSL_ALGO_SHA256);
  openssl_free_key($pubKey);

  if ($ok !== 1) return ["ok" => false, "error" => "Invalid token signature"];

  // Validate claims
  $now = time();
  $aud = $payload["aud"] ?? "";
  $iss = $payload["iss"] ?? "";
  $exp = (int)($payload["exp"] ?? 0);
  $sub = (string)($payload["sub"] ?? "");

  if ($aud !== $projectId) return ["ok" => false, "error" => "aud mismatch"];
  if ($iss !== "https://securetoken.google.com/" . $projectId) return ["ok" => false, "error" => "iss mismatch"];
  if ($sub === "" || strlen($sub) > 128) return ["ok" => false, "error" => "Invalid sub"];
  if ($exp <= 0 || $exp < $now) return ["ok" => false, "error" => "Token expired"];

  return ["ok" => true, "payload" => $payload];
}

// Verify token
$ver = verify_firebase_jwt($id_token, $FIREBASE_PROJECT_ID);
if (!$ver["ok"]) {
  json_response(["ok" => false, "error" => $ver["error"]], 401);
}

$payload = $ver["payload"];

$email = strtolower(trim($payload["email"] ?? ""));
$name  = trim($payload["name"] ?? "");
$uid   = trim($payload["user_id"] ?? $payload["sub"] ?? "");

if ($email === "" || $uid === "") {
  json_response(["ok" => false, "error" => "Token missing email/user id"], 401);
}

if ($name === "") {
  $name = explode("@", $email)[0];
}

// Find by email
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
  $randomSecret = bin2hex(random_bytes(32));
  $password_hash = password_hash($randomSecret, PASSWORD_DEFAULT);

  $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
  $ins->execute([$name, $email, $password_hash]);

  $userId = (int)$pdo->lastInsertId();
  $userName = $name;
} else {
  $userId = (int)$user["id"];
  $userName = (string)$user["name"];
  if ($userName === "" && $name !== "") {
    $upd = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
    $upd->execute([$name, $userId]);
    $userName = $name;
  }
}

// Create session like normal login
$_SESSION["user_id"] = $userId;
$_SESSION["name"] = $userName;

json_response([
  "ok" => true,
  "name" => $userName,
  "email" => $email
]);
