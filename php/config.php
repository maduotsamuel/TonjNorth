<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

sendSecurityHeaders();

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'tonj_cms';
const DB_USER = 'root';
const DB_PASS = '';
$adminPassword = getenv('TONJ_ADMIN_PASSWORD');
define('ADMIN_PASSWORD', $adminPassword !== false && $adminPassword !== '' ? $adminPassword : '2026@TNC');
const UPLOAD_DIR = __DIR__ . '/uploads';

function generateCsrfToken(): string
{
    if (empty($_SESSION['tonj_csrf_token'])) {
        $_SESSION['tonj_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['tonj_csrf_token'];
}

function verifyCsrfToken(mixed $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($_SESSION['tonj_csrf_token'] ?? '', $token);
}

function sanitizeTextValue(mixed $value, int $maxLength = 0): string
{
    $value = trim((string) strip_tags((string) $value));
    if ($maxLength > 0 && mb_strlen($value, 'UTF-8') > $maxLength) {
        $value = mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    return $value;
}

function storeUploadedImage(string $fieldName): string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return '';
    }

    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }

    $tempName = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tempName)) {
        return '';
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return '';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tempName);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mimeType, $allowedTypes, true)) {
        return '';
    }

    $extension = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'bin',
    };

    $fileName = bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = UPLOAD_DIR . '/' . $fileName;
    if (!move_uploaded_file($tempName, $targetPath)) {
        return '';
    }

    return 'php/uploads/' . $fileName;
}

function getDbConnection(): ?mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $serverConnection = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($serverConnection && !$serverConnection->connect_error) {
        $serverConnection->query(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', DB_NAME));
        $serverConnection->close();
    }

    $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($connection->connect_error) {
        return null;
    }

    $connection->set_charset('utf8mb4');
    return $connection;
}

function ensureSchema(): void
{
    $connection = getDbConnection();
    if (!$connection) {
        return;
    }

    $schema = <<<SQL
    CREATE TABLE IF NOT EXISTS news (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        excerpt TEXT NULL,
        body TEXT NULL,
        image VARCHAR(255) NULL,
        published_at DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS gallery (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        caption TEXT NULL,
        image VARCHAR(255) NOT NULL,
        published_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS matches (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        home_team VARCHAR(255) NOT NULL,
        away_team VARCHAR(255) NOT NULL,
        match_date DATETIME NOT NULL,
        venue VARCHAR(255) NULL,
        competition VARCHAR(255) NULL,
        result_home INT NULL,
        result_away INT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS standings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        team_name VARCHAR(255) NOT NULL,
        played INT NOT NULL DEFAULT 0,
        wins INT NOT NULL DEFAULT 0,
        draws INT NOT NULL DEFAULT 0,
        losses INT NOT NULL DEFAULT 0,
        goals_for INT NOT NULL DEFAULT 0,
        goals_against INT NOT NULL DEFAULT 0,
        points INT NOT NULL DEFAULT 0,
        position INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

    if (!$connection->multi_query($schema)) {
        return;
    }

    do {
        if ($result = $connection->store_result()) {
            $result->free();
        }
    } while ($connection->more_results() && $connection->next_result());

    $connection->query("INSERT IGNORE INTO news (id, title, excerpt, body, image, published_at, status) VALUES
        (1, 'Season preparations underway', 'Training intensity is rising as the squad sharpens its rhythm ahead of the next tournament.', 'The squad has begun a focused preparation phase with extra training sessions, stronger tactical work, and renewed discipline ahead of the next competition.', 'img/hero.jpeg', '2026-08-06 10:30:00', 'published'),
        (2, 'Community outreach expands across Tonj North', 'The club continues to connect with supporters through youth engagement and local football programs.', 'Community outreach is growing as the club spends more time with local youth groups, schools, and football lovers across Tonj North County.', 'img/celebration.jpeg', '2026-08-03 08:15:00', 'published'),
        (3, 'Fans rally behind the mighty side', 'Supporters gathered to celebrate the team’s latest performances and share messages of unity.', 'A wave of encouragement from supporters has boosted morale across the club and strengthened the bond with the community.', 'img/intense.jpeg', '2026-07-29 15:45:00', 'published')");

    $connection->query("INSERT IGNORE INTO gallery (id, title, caption, image, published_at) VALUES
        (1, 'Match action', 'Intense match action in Tonj North', 'img/intense.jpeg', '2026-08-06 10:30:00'),
        (2, 'Victory celebration', 'Celebrating victory with fans', 'img/celebration.jpeg', '2026-08-05 09:00:00'),
        (3, 'Team lineup', 'Starting lineup ready for battle', 'img/squad.jpeg', '2026-08-04 11:20:00')");

    $connection->query("INSERT IGNORE INTO matches (id, home_team, away_team, match_date, venue, competition, result_home, result_away, status) VALUES
        (1, 'Tonj North Football Team', 'Gogrial East Football Team', '2026-08-09 15:00:00', 'Buluk', 'Semi Final', NULL, NULL, 'upcoming'),
        (2, 'Tonj North Football Team', 'Tonj South Football Team', '2026-08-02 15:00:00', 'Buluk', 'League', 1, 0, 'finished'),
        (3, 'Tonj North Football Team', 'Twic Mayardit Football Team', '2026-08-01 15:00:00', 'Buluk', 'League', 1, 3, 'finished')");

    $connection->query("INSERT IGNORE INTO standings (id, team_name, played, wins, draws, losses, goals_for, goals_against, points, position) VALUES
        (1, 'Twic Mayardit Football Team', 2, 2, 0, 0, 5, 2, 6, 1),
        (2, 'Tonj North Football Team', 2, 1, 0, 1, 2, 3, 3, 2),
        (3, 'Tonj South Football Team', 2, 0, 0, 2, 1, 4, 0, 3)");
}

function ensureUploadsDir(): void
{
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
}

function isAuthenticated(): bool
{
    return !empty($_SESSION['tonj_admin']);
}

function requireAdmin(): void
{
    if (!isAuthenticated()) {
        header('Location: admin.php');
        exit;
    }
}

function escapeValue(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeDate(string $value): string
{
    return $value !== '' ? $value : date('Y-m-d H:i:s');
}

function readFallbackNews(int $limit = 6): array
{
    return array_slice([
        [
            'id' => 1,
            'title' => 'Season preparations underway',
            'excerpt' => 'Training intensity is rising as the squad sharpens its rhythm ahead of the next tournament.',
            'body' => 'The squad has begun a focused preparation phase with extra training sessions, stronger tactical work, and renewed discipline ahead of the next competition.',
            'image' => 'img/hero.jpeg',
            'publishedAt' => '2026-08-06T10:30:00Z'
        ],
        [
            'id' => 2,
            'title' => 'Community outreach expands across Tonj North',
            'excerpt' => 'The club continues to connect with supporters through youth engagement and local football programs.',
            'body' => 'Community outreach is growing as the club spends more time with local youth groups, schools, and football lovers across Tonj North County.',
            'image' => 'img/celebration.jpeg',
            'publishedAt' => '2026-08-03T08:15:00Z'
        ],
        [
            'id' => 3,
            'title' => 'Fans rally behind the mighty side',
            'excerpt' => 'Supporters gathered to celebrate the team’s latest performances and share messages of unity.',
            'body' => 'A wave of encouragement from supporters has boosted morale across the club and strengthened the bond with the community.',
            'image' => 'img/intense.jpeg',
            'publishedAt' => '2026-07-29T15:45:00Z'
        ]
    ], 0, $limit);
}

function readFallbackGallery(int $limit = 6): array
{
    return array_slice([
        ['id' => 1, 'title' => 'Match action', 'caption' => 'Intense match action in Tonj North', 'image' => 'img/intense.jpeg', 'publishedAt' => '2026-08-06T10:30:00Z'],
        ['id' => 2, 'title' => 'Victory celebration', 'caption' => 'Celebrating victory with fans', 'image' => 'img/celebration.jpeg', 'publishedAt' => '2026-08-05T09:00:00Z'],
        ['id' => 3, 'title' => 'Team lineup', 'caption' => 'Starting lineup ready for battle', 'image' => 'img/squad.jpeg', 'publishedAt' => '2026-08-04T11:20:00Z']
    ], 0, $limit);
}

function readFallbackMatches(int $limit = 6): array
{
    return array_slice([
        ['id' => 1, 'homeTeam' => 'Tonj North Football Team', 'awayTeam' => 'Gogrial East Football Team', 'matchDate' => '2026-08-09T15:00:00', 'venue' => 'Buluk', 'competition' => 'Semi Final', 'resultHome' => null, 'resultAway' => null, 'status' => 'upcoming'],
        ['id' => 2, 'homeTeam' => 'Tonj North Football Team', 'awayTeam' => 'Tonj South Football Team', 'matchDate' => '2026-08-02T15:00:00', 'venue' => 'Buluk', 'competition' => 'League', 'resultHome' => 1, 'resultAway' => 0, 'status' => 'finished'],
        ['id' => 3, 'homeTeam' => 'Tonj North Football Team', 'awayTeam' => 'Twic Mayardit Football Team', 'matchDate' => '2026-08-01T15:00:00', 'venue' => 'Buluk', 'competition' => 'League', 'resultHome' => 1, 'resultAway' => 3, 'status' => 'finished']
    ], 0, $limit);
}

function readFallbackStandings(int $limit = 10): array
{
    return array_slice([
        ['id' => 1, 'teamName' => 'Twic Mayardit Football Team', 'played' => 2, 'wins' => 2, 'draws' => 0, 'losses' => 0, 'goalsFor' => 5, 'goalsAgainst' => 2, 'points' => 6, 'position' => 1],
        ['id' => 2, 'teamName' => 'Tonj North Football Team', 'played' => 2, 'wins' => 1, 'draws' => 0, 'losses' => 1, 'goalsFor' => 2, 'goalsAgainst' => 3, 'points' => 3, 'position' => 2],
        ['id' => 3, 'teamName' => 'Tonj South Football Team', 'played' => 2, 'wins' => 0, 'draws' => 0, 'losses' => 2, 'goalsFor' => 1, 'goalsAgainst' => 4, 'points' => 0, 'position' => 3]
    ], 0, $limit);
}

function fetchNews(int $limit = 6): array
{
    ensureSchema();
    $connection = getDbConnection();
    if (!$connection) {
        return readFallbackNews($limit);
    }

    $stmt = $connection->prepare('SELECT id, title, excerpt, body, image, published_at FROM news WHERE status = ? ORDER BY published_at DESC, id DESC LIMIT ?');
    if (!$stmt) {
        return readFallbackNews($limit);
    }

    $status = 'published';
    $stmt->bind_param('si', $status, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'excerpt' => $row['excerpt'],
            'body' => $row['body'],
            'image' => $row['image'],
            'publishedAt' => date('c', strtotime($row['published_at']))
        ];
    }
    $stmt->close();

    return $items ?: readFallbackNews($limit);
}

function fetchGallery(int $limit = 6): array
{
    ensureSchema();
    $connection = getDbConnection();
    if (!$connection) {
        return readFallbackGallery($limit);
    }

    $stmt = $connection->prepare('SELECT id, title, caption, image, published_at FROM gallery ORDER BY published_at DESC, id DESC LIMIT ?');
    if (!$stmt) {
        return readFallbackGallery($limit);
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'caption' => $row['caption'],
            'image' => $row['image'],
            'publishedAt' => date('c', strtotime($row['published_at']))
        ];
    }
    $stmt->close();

    return $items ?: readFallbackGallery($limit);
}

function fetchMatches(int $limit = 6): array
{
    ensureSchema();
    $connection = getDbConnection();
    if (!$connection) {
        return readFallbackMatches($limit);
    }

    $stmt = $connection->prepare('SELECT id, home_team, away_team, match_date, venue, competition, result_home, result_away, status FROM matches ORDER BY match_date DESC, id DESC LIMIT ?');
    if (!$stmt) {
        return readFallbackMatches($limit);
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int) $row['id'],
            'homeTeam' => $row['home_team'],
            'awayTeam' => $row['away_team'],
            'matchDate' => date('c', strtotime($row['match_date'])),
            'venue' => $row['venue'],
            'competition' => $row['competition'],
            'resultHome' => $row['result_home'] !== null ? (int) $row['result_home'] : null,
            'resultAway' => $row['result_away'] !== null ? (int) $row['result_away'] : null,
            'status' => $row['status']
        ];
    }
    $stmt->close();

    return $items ?: readFallbackMatches($limit);
}

function fetchStandings(int $limit = 10): array
{
    ensureSchema();
    $connection = getDbConnection();
    if (!$connection) {
        return readFallbackStandings($limit);
    }

    $stmt = $connection->prepare('SELECT id, team_name, played, wins, draws, losses, goals_for, goals_against, points, position FROM standings ORDER BY position ASC, points DESC, goals_for DESC LIMIT ?');
    if (!$stmt) {
        return readFallbackStandings($limit);
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int) $row['id'],
            'teamName' => $row['team_name'],
            'played' => (int) $row['played'],
            'wins' => (int) $row['wins'],
            'draws' => (int) $row['draws'],
            'losses' => (int) $row['losses'],
            'goalsFor' => (int) $row['goals_for'],
            'goalsAgainst' => (int) $row['goals_against'],
            'points' => (int) $row['points'],
            'position' => (int) $row['position']
        ];
    }
    $stmt->close();

    return $items ?: readFallbackStandings($limit);
}

function getAdminItems(string $table): array
{
    $connection = getDbConnection();
    if (!$connection) {
        return [];
    }

    $allowed = ['news', 'gallery', 'matches', 'standings'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }

    $query = match ($table) {
        'news' => 'SELECT id, title, excerpt, body, image, published_at, status FROM news ORDER BY published_at DESC, id DESC',
        'gallery' => 'SELECT id, title, caption, image, published_at FROM gallery ORDER BY published_at DESC, id DESC',
        'matches' => 'SELECT id, home_team, away_team, match_date, venue, competition, result_home, result_away, status FROM matches ORDER BY match_date DESC, id DESC',
        'standings' => 'SELECT id, team_name, played, wins, draws, losses, goals_for, goals_against, points, position FROM standings ORDER BY position ASC, points DESC, id DESC',
    };

    $result = $connection->query($query);
    if (!$result) {
        return [];
    }

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}
