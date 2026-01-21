<?php
require_once __DIR__ . "/config.php";

function require_auth(): int {
  if (!isset($_SESSION["user_id"])) {
    json_response(["ok" => false, "error" => "Unauthorized"], 401);
  }
  return (int)$_SESSION["user_id"];
}
