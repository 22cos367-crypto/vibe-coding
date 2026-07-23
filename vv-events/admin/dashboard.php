<?php
// ============================================================
// VV EVENTS — Admin Dashboard
// ============================================================

session_start();
require_once __DIR__ . '/../api/db.php';

// Auth Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$flashMsg = '';

// Handle Status Updates / Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $action = $_POST['action'] ?? '';

    // Update Booking Status
    if ($action === 'update_booking_status') {
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        if ($bookingId > 0 && in_array($newStatus, ['pending', 'confirmed', 'cancelled'])) {
            $stmt = $db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $bookingId]);
            $flashMsg = "Booking #$bookingId status updated to " . ucfirst($newStatus) . ".";
        }
    }
    
    // Delete Booking
    if ($action === 'delete_booking') {
        $bookingId = intval($_POST['booking_id'] ?? 0);
        if ($bookingId > 0) {
            $stmt = $db->prepare("DELETE FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);
            $flashMsg = "Booking #$bookingId has been removed.";
        }
    }

    // Update Message Status
    if ($action === 'update_message_status') {
        $msgId = intval($_POST['msg_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        if ($msgId > 0 && in_array($newStatus, ['unread', 'read', 'replied'])) {
            $stmt = $db->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $msgId]);
            $flashMsg = "Message #$msgId status updated to " . ucfirst($newStatus) . ".";
        }
    }

    // Delete Message
    if ($action === 'delete_message') {
        $msgId = intval($_POST['msg_id'] ?? 0);
        if ($msgId > 0) {
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
            $stmt->execute([':id' => $msgId]);
            $flashMsg = "Inquiry message #$msgId deleted.";
        }
    }
}

// Fetch Metrics & Data
$metrics = [
    'total_bookings'   => 0,
    'pending_bookings' => 0,
    'confirmed_events' => 0,
    'unread_messages'  => 0,
    'total_revenue'    => 0.00
];

$bookingsList = [];
$messagesList = [];

if ($db) {
    // Metrics Queries
    $metrics['total_bookings']   = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $metrics['pending_bookings'] = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    $metrics['confirmed_events'] = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
    $metrics['unread_messages']  = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
    $metrics['total_revenue']    = (float)$db->query("SELECT SUM(estimated_price) FROM bookings WHERE status = 'confirmed'")->fetchColumn();

    // Fetch Bookings
    $bookingStmt = $db->query("SELECT * FROM bookings ORDER BY created_at DESC");
    $bookingsList = $bookingStmt->fetchAll();

    // Fetch Messages
    $msgStmt = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    $messagesList = $msgStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | VV Events</title>
<link rel="icon" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
  :root {
    --bg-dashboard: #0c0709;
    --panel-bg: #190f13;
    --panel-border: rgba(212,175,55,0.2);
  }
  body {
    background: var(--bg-dashboard);
    color: var(--text);
    margin: 0;
    font-family: var(--font-body);
  }
  .admin-header {
    background: var(--panel-bg);
    border-bottom: 1px solid var(--panel-border);
    padding: 18px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .admin-header .brand {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .admin-header img {
    height: 42px;
  }
  .admin-header h1 {
    font-size: 1.3rem;
    margin: 0;
    color: var(--gold-soft);
  }
  .admin-user-nav {
    display: flex;
    align-items: center;
    gap: 20px;
  }
  .admin-user-nav span {
    font-size: 0.9rem;
    color: var(--text-soft);
  }
  
  .dashboard-container {
    max-width: 1280px;
    margin: 36px auto;
    padding: 0 24px;
  }

  .flash-alert {
    background: rgba(63, 145, 66, 0.2);
    border: 1px solid #3f9142;
    color: #8ce990;
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 28px;
    font-size: 0.92rem;
  }

  /* Metric Cards Grid */
  .metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 36px;
  }
  .metric-card {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
  }
  .metric-card .title {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-soft);
    margin-bottom: 8px;
  }
  .metric-card .val {
    font-size: 1.8rem;
    font-weight: 700;
    font-family: var(--font-head);
    color: var(--gold-soft);
  }

  /* Tables Section */
  .section-card {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: 18px;
    padding: 28px;
    margin-bottom: 40px;
  }
  .section-card h2 {
    font-size: 1.3rem;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .data-table-wrap {
    overflow-x: auto;
  }
  table.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    text-align: left;
  }
  table.data-table th {
    background: rgba(255,255,255,0.03);
    color: var(--gold-soft);
    padding: 14px 16px;
    border-bottom: 1px solid var(--panel-border);
    font-weight: 600;
    white-space: nowrap;
  }
  table.data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(212,175,55,0.08);
    vertical-align: top;
  }
  table.data-table tr:hover td {
    background: rgba(255,255,255,0.02);
  }
  
  .badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }
  .badge-pending { background: rgba(230, 162, 60, 0.2); color: #e6a23c; border: 1px solid #e6a23c; }
  .badge-confirmed { background: rgba(63, 145, 66, 0.2); color: #4cd964; border: 1px solid #4cd964; }
  .badge-cancelled { background: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid #ff6b6b; }
  .badge-unread { background: rgba(212, 175, 55, 0.2); color: var(--gold); border: 1px solid var(--gold); }
  .badge-read { background: rgba(255, 255, 255, 0.1); color: #aaa; }

  .action-form {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .select-sm {
    background: var(--surface-2);
    color: var(--text);
    border: 1px solid var(--panel-border);
    padding: 5px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
  }
  .btn-icon {
    background: rgba(220,53,69,0.2);
    color: #ff6b6b;
    border: 1px solid rgba(220,53,69,0.4);
    padding: 5px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.78rem;
  }
  .btn-icon:hover {
    background: rgba(220,53,69,0.4);
  }
</style>
</head>
<body>

<header class="admin-header">
  <div class="brand">
    <img src="../assets/logo.png" alt="VV Events Logo">
    <h1>Management Control Panel</h1>
  </div>
  <div class="admin-user-nav">
    <span>Logged in as: <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
    <a href="logout.php" class="btn btn-outline" style="padding:7px 18px; font-size:0.82rem">Logout</a>
  </div>
</header>

<div class="dashboard-container">

  <?php if (!empty($flashMsg)): ?>
    <div class="flash-alert">✓ <?= htmlspecialchars($flashMsg) ?></div>
  <?php endif; ?>

  <!-- Metrics Overview -->
  <div class="metrics-grid">
    <div class="metric-card">
      <div class="title">Total Bookings</div>
      <div class="val"><?= $metrics['total_bookings'] ?></div>
    </div>
    <div class="metric-card">
      <div class="title">Pending Approvals</div>
      <div class="val" style="color:#e6a23c"><?= $metrics['pending_bookings'] ?></div>
    </div>
    <div class="metric-card">
      <div class="title">Confirmed Events</div>
      <div class="val" style="color:#4cd964"><?= $metrics['confirmed_events'] ?></div>
    </div>
    <div class="metric-card">
      <div class="title">Unread Inquiries</div>
      <div class="val" style="color:var(--gold)"><?= $metrics['unread_messages'] ?></div>
    </div>
    <div class="metric-card">
      <div class="title">Confirmed Volume</div>
      <div class="val" style="font-size:1.4rem">₹<?= number_format($metrics['total_revenue'], 2) ?></div>
    </div>
  </div>

  <!-- Bookings List -->
  <div class="section-card">
    <h2>
      <span>📅 Event Booking Requests</span>
      <span style="font-size:0.85rem; font-weight:400; color:var(--text-soft)"><?= count($bookingsList) ?> Total</span>
    </h2>

    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Ref &amp; Date</th>
            <th>Customer Info</th>
            <th>Event Package</th>
            <th>Location &amp; Guests</th>
            <th>Estimate</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bookingsList)): ?>
            <tr><td colspan="7" style="text-align:center; padding: 30px; color: var(--text-soft)">No booking requests found in database.</td></tr>
          <?php else: ?>
            <?php foreach ($bookingsList as $b): ?>
              <?php 
                $addonsArr = json_decode($b['addons'], true) ?: [];
                $formattedDate = date('d M Y', strtotime($b['event_date']));
              ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($b['booking_ref']) ?></strong><br>
                  <span style="color:var(--gold-soft); font-weight:600"><?= $formattedDate ?></span><br>
                  <small style="color:rgba(242,232,218,0.5)"><?= htmlspecialchars($b['preferred_time'] ?? 'Flex') ?></small>
                </td>
                <td>
                  <strong><?= htmlspecialchars($b['full_name']) ?></strong><br>
                  📞 <a href="tel:<?= htmlspecialchars($b['phone']) ?>" style="color:var(--text-soft)"><?= htmlspecialchars($b['phone']) ?></a><br>
                  💬 <a href="https://wa.me/91<?= preg_replace('/\D/', '', $b['whatsapp']) ?>" target="_blank" style="color:#4cd964">WhatsApp</a><br>
                  ✉️ <?= htmlspecialchars($b['email']) ?>
                </td>
                <td>
                  <strong style="text-transform:capitalize"><?= htmlspecialchars($b['event_type']) ?></strong><br>
                  <small>Decor: <?= htmlspecialchars($b['decoration']) ?> | Entry: <?= htmlspecialchars($b['entry']) ?></small><br>
                  <?php if (!empty($addonsArr)): ?>
                    <small style="color:var(--gold)">Addons: <?= implode(', ', $addonsArr) ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?= htmlspecialchars($b['event_location']) ?><br>
                  <small style="color:var(--text-soft)">Guests: <?= htmlspecialchars($b['guest_count']) ?></small>
                </td>
                <td>
                  <strong style="color:var(--gold-soft)">₹<?= number_format($b['estimated_price'], 2) ?></strong>
                </td>
                <td>
                  <span class="badge badge-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span>
                </td>
                <td>
                  <form method="POST" action="dashboard.php" class="action-form">
                    <input type="hidden" name="action" value="update_booking_status">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <select name="status" class="select-sm" onchange="this.form.submit()">
                      <option value="pending" <?= $b['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                      <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                  </form>
                  <form method="POST" action="dashboard.php" class="action-form" style="margin-left:4px" onsubmit="return confirm('Delete this booking permanently?')">
                    <input type="hidden" name="action" value="delete_booking">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn-icon" title="Delete Booking">🗑️</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Contact Messages -->
  <div class="section-card">
    <h2>
      <span>💬 Customer Inquiries</span>
      <span style="font-size:0.85rem; font-weight:400; color:var(--text-soft)"><?= count($messagesList) ?> Total</span>
    </h2>

    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date &amp; Time</th>
            <th>Sender Name</th>
            <th>Contact Details</th>
            <th>Message</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($messagesList)): ?>
            <tr><td colspan="6" style="text-align:center; padding: 30px; color: var(--text-soft)">No customer inquiries found.</td></tr>
          <?php else: ?>
            <?php foreach ($messagesList as $m): ?>
              <tr>
                <td><small style="color:rgba(242,232,218,0.6)"><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></small></td>
                <td><strong><?= htmlspecialchars($m['full_name']) ?></strong></td>
                <td>
                  📞 <?= htmlspecialchars($m['phone']) ?><br>
                  ✉️ <?= htmlspecialchars($m['email']) ?>
                </td>
                <td style="max-width:300px"><?= nl2br(htmlspecialchars($m['message'])) ?></td>
                <td>
                  <span class="badge badge-<?= $m['status'] === 'unread' ? 'unread' : 'read' ?>"><?= htmlspecialchars($m['status']) ?></span>
                </td>
                <td>
                  <form method="POST" action="dashboard.php" class="action-form">
                    <input type="hidden" name="action" value="update_message_status">
                    <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
                    <select name="status" class="select-sm" onchange="this.form.submit()">
                      <option value="unread" <?= $m['status'] === 'unread' ? 'selected' : '' ?>>Unread</option>
                      <option value="read" <?= $m['status'] === 'read' ? 'selected' : '' ?>>Read</option>
                      <option value="replied" <?= $m['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
                    </select>
                  </form>
                  <form method="POST" action="dashboard.php" class="action-form" style="margin-left:4px" onsubmit="return confirm('Delete message?')">
                    <input type="hidden" name="action" value="delete_message">
                    <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn-icon" title="Delete Inquiry">🗑️</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

</body>
</html>
