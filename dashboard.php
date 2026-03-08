<?php
session_start();
if (empty($_SESSION['username'])) {
  header('Location: StudentLogin.php');
  exit;
}
$user = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard</title>
  <link rel="stylesheet" href="design.css">
  <style>
    .dash{min-height:70vh; display:flex; align-items:center; justify-content:center}
    .card{width:100%;
      max-width:420px;
      /* frosted glass look */
      background: linear-gradient(180deg, rgba(255,255,255,0.26), rgba(255,255,255,0.12));
      border-radius: var(--radius);
      padding:32px 28px;
      box-shadow: 0 8px 30px rgba(2,6,23,0.22);
      text-align:center;
      display:flex;
      flex-direction:column;
      gap:18px;
      border: 1px solid rgba(255,255,255,0.45);
      position: relative;
      z-index: 1;
      -webkit-backdrop-filter: blur(10px) saturate(1.10);
      backdrop-filter: blur(10px) saturate(1.10);
      background-clip: padding-box;}
    .card h1{margin-bottom:8px; color:#fff}
    .card p{color:var(--muted)}
    .logout{margin-top:18px}
  </style>
</head>
<body>
  <main class="page">
    <div class="dash">
      <div class="container" style="width:520px;">
        <h1>Welcome, <?php echo $user; ?>!</h1>
        <p class="lead">This is your simple student dashboard. From here you can access your resources.</p>
        <div style="display:flex; gap:10px; justify-content:center; margin-top:14px">
          <button class="btn" onclick="location.href='index.html'">Portal Home</button>
          <button class="btn btn-outline logout" onclick="location.href='logout.php'">Log out</button>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
