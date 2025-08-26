<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

// PLACEHOLDER - ANALYTICS DISABLED
// Original functionality moved to extraFeatures/

if (session_status() === PHP_SESSION_NONE) { session_start(); }
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics Dashboard — ReplyPilot-AI</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .disabled-notice{
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 40px 30px;
      text-align: center;
      margin: 40px 0;
    }
    .disabled-notice h3{
      color: #6c757d;
      margin-bottom: 15px;
    }
    .disabled-notice p{
      color: #868e96;
      line-height: 1.6;
    }
    .feature-icon{
      font-size: 48px;
      margin-bottom: 20px;
      opacity: 0.5;
    }
  </style>
</head>
<body>
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h1>📊 Analytics Dashboard</h1>
      <a class="btn" href="index.php">← Back to Dashboard</a>
    </div>

    <div class="disabled-notice">
      <div class="feature-icon">📈</div>
      <h3>Analytics Feature Temporarily Disabled</h3>
      <p>
        The analytics and reporting features are currently disabled.<br>
        All core functionality including AI response generation remains fully operational.
      </p>
      <div style="margin-top: 30px">
        <a class="btn" href="index.php">Return to Dashboard</a>
      </div>
    </div>
  </div>
</body>
</html>
