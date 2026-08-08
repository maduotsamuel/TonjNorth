<?php
$configCandidates = [
    __DIR__ . '/config.php',
    dirname(__DIR__) . '/config.php',
    dirname(__DIR__) . '/php/config.php',
    __DIR__ . '/../config.php'
];

foreach ($configCandidates as $candidate) {
    if (is_file($candidate)) {
        require_once $candidate;
        break;
    }
}

if (!function_exists('ensureSchema')) {
    die('CMS configuration could not be loaded.');
}

ensureSchema();
ensureUploadsDir();

$csrfToken = generateCsrfToken();
$action = sanitizeTextValue($_POST['action'] ?? '');
$message = '';
$messageType = 'success';
$editingId = null;
$editingTable = '';
$editingItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please refresh the page and try again.';
        $messageType = 'error';
    } elseif ($action === 'login') {
        $submittedPassword = (string) ($_POST['password'] ?? '');
        if (hash_equals(ADMIN_PASSWORD, $submittedPassword)) {
            session_regenerate_id(true);
            $_SESSION['tonj_admin'] = true;
            $message = 'Welcome back. You can manage content now.';
        } else {
            $message = 'Incorrect password.';
            $messageType = 'error';
        }
    } elseif (isAuthenticated() && $action === 'logout') {
        session_destroy();
        header('Location: admin.php');
        exit;
    } elseif (isAuthenticated() && $action === 'delete') {
        $table = sanitizeTextValue($_POST['table'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        if (in_array($table, ['news', 'gallery', 'matches', 'standings'], true) && $id > 0) {
            $connection = getDbConnection();
            if ($connection) {
                $query = match ($table) {
                    'news' => 'DELETE FROM news WHERE id = ?',
                    'gallery' => 'DELETE FROM gallery WHERE id = ?',
                    'matches' => 'DELETE FROM matches WHERE id = ?',
                    'standings' => 'DELETE FROM standings WHERE id = ?',
                };
                $stmt = $connection->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $message = 'Entry deleted.';
            }
        }
    } elseif (isAuthenticated() && $action === 'edit') {
        $table = sanitizeTextValue($_POST['table'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        if (in_array($table, ['news', 'gallery', 'matches', 'standings'], true) && $id > 0) {
            $connection = getDbConnection();
            if ($connection) {
                $query = match ($table) {
                    'news' => 'SELECT id, title, excerpt, body, image, published_at, status FROM news WHERE id = ?',
                    'gallery' => 'SELECT id, title, caption, image, published_at FROM gallery WHERE id = ?',
                    'matches' => 'SELECT id, home_team, away_team, match_date, venue, competition, result_home, result_away, status FROM matches WHERE id = ?',
                    'standings' => 'SELECT id, team_name, played, wins, draws, losses, goals_for, goals_against, points, position FROM standings WHERE id = ?',
                };
                $stmt = $connection->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $editingItem = $result->fetch_assoc();
                $stmt->close();
                if ($editingItem) {
                    $editingId = (int) $editingItem['id'];
                    $editingTable = $table;
                    $message = 'Editing existing entry.';
                } else {
                    $message = 'Entry not found.';
                    $messageType = 'error';
                }
            }
        }
    }
}

if (!isAuthenticated()) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Admin Login</title><style>body{font-family:Arial,sans-serif;background:#0a0a0a;color:#fff;padding:40px}form{max-width:420px;margin:80px auto;background:#111;padding:24px;border-radius:16px}input,button{width:100%;padding:12px;border-radius:10px;border:1px solid #333;margin-bottom:12px}button{background:#FFD700;color:#111;font-weight:bold;cursor:pointer}</style></head><body><form method="post"><h2>Club CMS Login</h2><p>Use the admin password to manage news, gallery, matches, and standings.</p><input type="password" name="password" placeholder="Admin password" required><input type="hidden" name="action" value="login"><input type="hidden" name="csrf_token" value="' . escapeValue($csrfToken) . '"><button type="submit">Sign in</button></form></body></html>';
    exit;
}

$section = sanitizeTextValue($_GET['section'] ?? 'news');
$allowedSections = ['news', 'gallery', 'matches', 'standings'];
if (!in_array($section, $allowedSections, true)) {
    $section = 'news';
}
if ($editingTable !== '') {
    $section = $editingTable;
}
$items = getAdminItems($section);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    $connection = getDbConnection();
    if ($connection) {
        $table = sanitizeTextValue($_POST['table'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $section = $table !== '' ? $table : $section;

        if ($table === 'news') {
            $title = sanitizeTextValue($_POST['title'] ?? '', 255);
            $excerpt = sanitizeTextValue($_POST['excerpt'] ?? '', 1000);
            $body = sanitizeTextValue($_POST['body'] ?? '', 10000);
            $publishedAt = normalizeDate(sanitizeTextValue($_POST['published_at'] ?? '', 20));
            $status = in_array($_POST['status'] ?? 'published', ['published', 'draft'], true) ? sanitizeTextValue($_POST['status'] ?? 'published') : 'published';
            $image = '';
            if ($id > 0) {
                $existingStmt = $connection->prepare('SELECT image FROM news WHERE id = ?');
                $existingStmt->bind_param('i', $id);
                $existingStmt->execute();
                $existingResult = $existingStmt->get_result();
                $existingRow = $existingResult->fetch_assoc();
                $existingStmt->close();
                $image = $existingRow['image'] ?? '';
            }
            $uploadedImage = storeUploadedImage('image');
            if ($uploadedImage !== '') {
                $image = $uploadedImage;
            }
            if ($id > 0) {
                $stmt = $connection->prepare('UPDATE news SET title = ?, excerpt = ?, body = ?, image = ?, published_at = ?, status = ? WHERE id = ?');
                $stmt->bind_param('ssssssi', $title, $excerpt, $body, $image, $publishedAt, $status, $id);
            } else {
                $stmt = $connection->prepare('INSERT INTO news (title, excerpt, body, image, published_at, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssss', $title, $excerpt, $body, $image, $publishedAt, $status);
            }
            $stmt->execute();
            $stmt->close();
            $message = $id > 0 ? 'News entry updated.' : 'News entry saved.';
        } elseif ($table === 'gallery') {
            $title = sanitizeTextValue($_POST['title'] ?? '', 255);
            $caption = sanitizeTextValue($_POST['caption'] ?? '', 1000);
            $publishedAt = normalizeDate(sanitizeTextValue($_POST['published_at'] ?? '', 20));
            $image = '';
            if ($id > 0) {
                $existingStmt = $connection->prepare('SELECT image FROM gallery WHERE id = ?');
                $existingStmt->bind_param('i', $id);
                $existingStmt->execute();
                $existingResult = $existingStmt->get_result();
                $existingRow = $existingResult->fetch_assoc();
                $existingStmt->close();
                $image = $existingRow['image'] ?? '';
            }
            $uploadedImage = storeUploadedImage('image');
            if ($uploadedImage !== '') {
                $image = $uploadedImage;
            }
            if ($id > 0) {
                $stmt = $connection->prepare('UPDATE gallery SET title = ?, caption = ?, image = ?, published_at = ? WHERE id = ?');
                $stmt->bind_param('ssssi', $title, $caption, $image, $publishedAt, $id);
            } else {
                $stmt = $connection->prepare('INSERT INTO gallery (title, caption, image, published_at) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $title, $caption, $image, $publishedAt);
            }
            $stmt->execute();
            $stmt->close();
            $message = $id > 0 ? 'Gallery item updated.' : 'Gallery item saved.';
        } elseif ($table === 'matches') {
            $homeTeam = sanitizeTextValue($_POST['home_team'] ?? '', 255);
            $awayTeam = sanitizeTextValue($_POST['away_team'] ?? '', 255);
            $venue = sanitizeTextValue($_POST['venue'] ?? '', 255);
            $competition = sanitizeTextValue($_POST['competition'] ?? '', 255);
            $status = in_array($_POST['status'] ?? 'upcoming', ['upcoming', 'finished'], true) ? sanitizeTextValue($_POST['status'] ?? 'upcoming') : 'upcoming';
            $resultHome = isset($_POST['result_home']) ? max(0, (int) $_POST['result_home']) : null;
            $resultAway = isset($_POST['result_away']) ? max(0, (int) $_POST['result_away']) : null;
            $matchDate = normalizeDate(sanitizeTextValue($_POST['match_date'] ?? '', 20));
            if ($id > 0) {
                $stmt = $connection->prepare('UPDATE matches SET home_team = ?, away_team = ?, match_date = ?, venue = ?, competition = ?, result_home = ?, result_away = ?, status = ? WHERE id = ?');
                $stmt->bind_param('sssssiisi', $homeTeam, $awayTeam, $matchDate, $venue, $competition, $resultHome, $resultAway, $status, $id);
            } else {
                $stmt = $connection->prepare('INSERT INTO matches (home_team, away_team, match_date, venue, competition, result_home, result_away, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssiis', $homeTeam, $awayTeam, $matchDate, $venue, $competition, $resultHome, $resultAway, $status);
            }
            $stmt->execute();
            $stmt->close();
            $message = $id > 0 ? 'Match updated.' : 'Match saved.';
        } elseif ($table === 'standings') {
            $teamName = sanitizeTextValue($_POST['team_name'] ?? '', 255);
            $played = max(0, (int) ($_POST['played'] ?? 0));
            $wins = max(0, (int) ($_POST['wins'] ?? 0));
            $draws = max(0, (int) ($_POST['draws'] ?? 0));
            $losses = max(0, (int) ($_POST['losses'] ?? 0));
            $goalsFor = max(0, (int) ($_POST['goals_for'] ?? 0));
            $goalsAgainst = max(0, (int) ($_POST['goals_against'] ?? 0));
            $points = max(0, (int) ($_POST['points'] ?? 0));
            $position = max(0, (int) ($_POST['position'] ?? 0));
            if ($id > 0) {
                $stmt = $connection->prepare('UPDATE standings SET team_name = ?, played = ?, wins = ?, draws = ?, losses = ?, goals_for = ?, goals_against = ?, points = ?, position = ? WHERE id = ?');
                $stmt->bind_param('siiiiiiiii', $teamName, $played, $wins, $draws, $losses, $goalsFor, $goalsAgainst, $points, $position, $id);
            } else {
                $stmt = $connection->prepare('INSERT INTO standings (team_name, played, wins, draws, losses, goals_for, goals_against, points, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('siiiiiiii', $teamName, $played, $wins, $draws, $losses, $goalsFor, $goalsAgainst, $points, $position);
            }
            $stmt->execute();
            $stmt->close();
            $message = $id > 0 ? 'Standing updated.' : 'Standing saved.';
        }
        $editingId = null;
        $editingTable = '';
        $editingItem = null;
        $items = getAdminItems($section);
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Club CMS Admin</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#0a0a0a;color:#f5f5f5}
header{background:#111;border-bottom:1px solid #2a2a2a;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
nav a{color:#fff;text-decoration:none;margin-right:12px;padding:8px 10px;border-radius:8px}nav a.active{background:#FFD700;color:#111}.wrap{display:grid;grid-template-columns:320px 1fr;gap:24px;padding:24px}.panel{background:#171717;border:1px solid #2a2a2a;border-radius:16px;padding:16px}.form-grid{display:grid;gap:12px}.label{font-size:12px;text-transform:uppercase;color:#999}.input,.textarea,select{width:100%;padding:10px;border-radius:10px;border:1px solid #333;background:#111;color:#fff}.textarea{min-height:90px}.btn{background:#FFD700;color:#111;border:none;padding:10px 14px;border-radius:10px;font-weight:bold;cursor:pointer}.table{width:100%;border-collapse:collapse;margin-top:12px;font-size:14px}.table th,.table td{padding:10px;border-bottom:1px solid #222;text-align:left}.msg{padding:12px;border-radius:10px;margin-bottom:12px}.msg.success{background:#14432a;color:#d7f7dd}.msg.error{background:#4a1616;color:#ffd7d7}@media(max-width:900px){.wrap{grid-template-columns:1fr}}@media(max-width:640px){header{flex-direction:column;align-items:flex-start;gap:8px}}</style>
</head>
<body>
<header>
  <div>
    <h2 style="margin:0">Tonj North CMS</h2>
    <div style="color:#999;font-size:13px">Manage content for the club website</div>
  </div>
  <form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit">Logout</button></form>
</header>
<div class="wrap">
  <div class="panel">
    <h3>Add content</h3>
    <nav>
      <a class="<?= $section === 'news' ? 'active' : '' ?>" href="admin.php?section=news">News</a>
      <a class="<?= $section === 'gallery' ? 'active' : '' ?>" href="admin.php?section=gallery">Gallery</a>
      <a class="<?= $section === 'matches' ? 'active' : '' ?>" href="admin.php?section=matches">Matches</a>
      <a class="<?= $section === 'standings' ? 'active' : '' ?>" href="admin.php?section=standings">Standings</a>
    </nav>
    <?php if ($message !== ''): ?><div class="msg <?= $messageType ?>"><?= escapeValue($message) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form-grid" style="margin-top:14px">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="table" value="<?= escapeValue($section) ?>">
      <input type="hidden" name="id" value="<?= escapeValue($editingId ?? '') ?>">
      <input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>">
      <?php if ($section === 'news'): ?>
        <label class="label">Title<input class="input" type="text" name="title" required value="<?= escapeValue($editingItem['title'] ?? '') ?>"></label>
        <label class="label">Excerpt<textarea class="textarea" name="excerpt"><?= escapeValue($editingItem['excerpt'] ?? '') ?></textarea></label>
        <label class="label">Body<textarea class="textarea" name="body" required><?= escapeValue($editingItem['body'] ?? '') ?></textarea></label>
        <label class="label">Image<input class="input" type="file" name="image" accept="image/*"></label>
        <label class="label">Published At<input class="input" type="datetime-local" name="published_at" value="<?= escapeValue($editingItem['published_at'] ?? '') ?>"></label>
        <label class="label">Status<select name="status"><option value="published" <?= (($editingItem['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published</option><option value="draft" <?= (($editingItem['status'] ?? 'published') === 'draft') ? 'selected' : '' ?>>Draft</option></select></label>
      <?php elseif ($section === 'gallery'): ?>
        <label class="label">Title<input class="input" type="text" name="title" required value="<?= escapeValue($editingItem['title'] ?? '') ?>"></label>
        <label class="label">Caption<textarea class="textarea" name="caption"><?= escapeValue($editingItem['caption'] ?? '') ?></textarea></label>
        <label class="label">Image<input class="input" type="file" name="image" accept="image/*"></label>
        <label class="label">Published At<input class="input" type="datetime-local" name="published_at" value="<?= escapeValue($editingItem['published_at'] ?? '') ?>"></label>
      <?php elseif ($section === 'matches'): ?>
        <label class="label">Home Team<input class="input" type="text" name="home_team" required value="<?= escapeValue($editingItem['home_team'] ?? '') ?>"></label>
        <label class="label">Away Team<input class="input" type="text" name="away_team" required value="<?= escapeValue($editingItem['away_team'] ?? '') ?>"></label>
        <label class="label">Match Date<input class="input" type="datetime-local" name="match_date" required value="<?= escapeValue($editingItem['match_date'] ?? '') ?>"></label>
        <label class="label">Venue<input class="input" type="text" name="venue" value="<?= escapeValue($editingItem['venue'] ?? '') ?>"></label>
        <label class="label">Competition<input class="input" type="text" name="competition" value="<?= escapeValue($editingItem['competition'] ?? '') ?>"></label>
        <label class="label">Home Goals<input class="input" type="number" name="result_home" value="<?= escapeValue($editingItem['result_home'] ?? '') ?>"></label>
        <label class="label">Away Goals<input class="input" type="number" name="result_away" value="<?= escapeValue($editingItem['result_away'] ?? '') ?>"></label>
        <label class="label">Status<select name="status"><option value="upcoming" <?= (($editingItem['status'] ?? 'upcoming') === 'upcoming') ? 'selected' : '' ?>>Upcoming</option><option value="finished" <?= (($editingItem['status'] ?? 'upcoming') === 'finished') ? 'selected' : '' ?>>Finished</option></select></label>
      <?php else: ?>
        <label class="label">Team Name<input class="input" type="text" name="team_name" required value="<?= escapeValue($editingItem['team_name'] ?? '') ?>"></label>
        <label class="label">Played<input class="input" type="number" name="played" value="<?= escapeValue($editingItem['played'] ?? '0') ?>"></label>
        <label class="label">Wins<input class="input" type="number" name="wins" value="<?= escapeValue($editingItem['wins'] ?? '0') ?>"></label>
        <label class="label">Draws<input class="input" type="number" name="draws" value="<?= escapeValue($editingItem['draws'] ?? '0') ?>"></label>
        <label class="label">Losses<input class="input" type="number" name="losses" value="<?= escapeValue($editingItem['losses'] ?? '0') ?>"></label>
        <label class="label">Goals For<input class="input" type="number" name="goals_for" value="<?= escapeValue($editingItem['goals_for'] ?? '0') ?>"></label>
        <label class="label">Goals Against<input class="input" type="number" name="goals_against" value="<?= escapeValue($editingItem['goals_against'] ?? '0') ?>"></label>
        <label class="label">Points<input class="input" type="number" name="points" value="<?= escapeValue($editingItem['points'] ?? '0') ?>"></label>
        <label class="label">Position<input class="input" type="number" name="position" value="<?= escapeValue($editingItem['position'] ?? '0') ?>"></label>
      <?php endif; ?>
      <button class="btn" type="submit"><?= $editingId ? 'Update' : 'Save' ?></button>
      <?php if ($editingId): ?><button class="btn" type="button" onclick="window.location.href='admin.php?section=<?= escapeValue($section) ?>'" style="background:#333;color:#fff">Cancel</button><?php endif; ?>
    </form>
  </div>
  <div class="panel">
    <h3>Existing entries</h3>
    <?php if (empty($items)): ?>
      <p style="color:#999">No entries yet.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <?php if ($section === 'news'): ?><th>Title</th><th>Status</th><th>Published</th><th>Actions</th><?php elseif ($section === 'gallery'): ?><th>Title</th><th>Caption</th><th>Published</th><th>Actions</th><?php elseif ($section === 'matches'): ?><th>Teams</th><th>Status</th><th>Date</th><th>Actions</th><?php else: ?><th>Team</th><th>Points</th><th>Position</th><th>Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <?php if ($section === 'news'): ?>
                <td><?= escapeValue($item['title']) ?></td><td><?= escapeValue($item['status']) ?></td><td><?= escapeValue($item['published_at']) ?></td><td><form method="post" style="display:inline-block;margin-right:6px"><input type="hidden" name="action" value="edit"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px">Edit</button></form><form method="post" style="display:inline-block" onsubmit="return confirm('Delete this entry?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px;background:#7f1d1d;color:#fff">Delete</button></form></td>
              <?php elseif ($section === 'gallery'): ?>
                <td><?= escapeValue($item['title']) ?></td><td><?= escapeValue($item['caption']) ?></td><td><?= escapeValue($item['published_at']) ?></td><td><form method="post" style="display:inline-block;margin-right:6px"><input type="hidden" name="action" value="edit"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px">Edit</button></form><form method="post" style="display:inline-block" onsubmit="return confirm('Delete this entry?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px;background:#7f1d1d;color:#fff">Delete</button></form></td>
              <?php elseif ($section === 'matches'): ?>
                <td><?= escapeValue($item['home_team'] . ' vs ' . $item['away_team']) ?></td><td><?= escapeValue($item['status']) ?></td><td><?= escapeValue($item['match_date']) ?></td><td><form method="post" style="display:inline-block;margin-right:6px"><input type="hidden" name="action" value="edit"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px">Edit</button></form><form method="post" style="display:inline-block" onsubmit="return confirm('Delete this entry?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px;background:#7f1d1d;color:#fff">Delete</button></form></td>
              <?php else: ?>
                <td><?= escapeValue($item['team_name']) ?></td><td><?= escapeValue($item['points']) ?></td><td><?= escapeValue($item['position']) ?></td><td><form method="post" style="display:inline-block;margin-right:6px"><input type="hidden" name="action" value="edit"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px">Edit</button></form><form method="post" style="display:inline-block" onsubmit="return confirm('Delete this entry?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="table" value="<?= escapeValue($section) ?>"><input type="hidden" name="id" value="<?= escapeValue($item['id']) ?>"><input type="hidden" name="csrf_token" value="<?= escapeValue($csrfToken) ?>"><button class="btn" type="submit" style="padding:6px 10px;font-size:12px;background:#7f1d1d;color:#fff">Delete</button></form></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
