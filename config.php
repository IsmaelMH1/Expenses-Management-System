<?php
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_start();

function json_response(array $data, int $code = 200): void {
  http_response_code($code);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
define("OPENAI_API_KEY", "");
define("GROQ_API_KEY", "");
