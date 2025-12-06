<?php
session_start();

// Initialize playlist and current index if not set
if (!isset($_SESSION['playlist'])) {
  $_SESSION['playlist'] = []; // array correct all song
}
if (!isset($_SESSION['currentIndex'])) {
  $_SESSION['currentIndex'] = -1;
}
if (!isset($_SESSION['archives']))
  $_SESSION['archives'] = [];

// Helper: set flash message
function flash($msg)
{
  $_SESSION['flash'] = $msg;
}

// Handle actions
$action = $_POST['action'] ?? null;

// Create Playlist
if ($action === 'create') {
  if (!empty($_SESSION['playlist'])) {
    $_SESSION['archives'][] = $_SESSION['playlist']; // เก็บ playlist ปัจจุบัน
  }
  $_SESSION['playlist'] = [];  // ล้าง playlist ปัจจุบัน
  $_SESSION['currentIndex'] = -1;
  flash('New playlist created. Old playlist archived.');
}

// Insert Song
if ($action === 'insert') {
  $song = trim($_POST['song'] ?? '');
  if ($song !== '') {
    $_SESSION['playlist'][] = $song;
    if ($_SESSION['currentIndex'] === -1)
      $_SESSION['currentIndex'] = 0;
    flash("Inserted song: <strong>" . htmlspecialchars($song) . "</strong>");
  } else
    flash('Please enter a song name.');
}

// Insert Song at Position
if ($action === 'insert_at') {
  $song = trim($_POST['song'] ?? '');
  $pos = max(0, (int) ($_POST['position'] ?? 0) - 1);
  if ($song !== '' && $pos <= count($_SESSION['playlist'])) {
    array_splice($_SESSION['playlist'], $pos, 0, [$song]);
    flash("Inserted <strong>" . htmlspecialchars($song) . "</strong> at position " . ($pos + 1));
    if ($_SESSION['currentIndex'] === -1)
      $_SESSION['currentIndex'] = 0;
  } else
    flash('Invalid position or song name.');
}

// Search Song
if ($action === 'search') {
  $song = trim($_POST['song'] ?? '');
  $idx = -1;
  if ($song !== '') {
    foreach ($_SESSION['playlist'] as $i => $name) {
      if ($name === $song) {
        $idx = $i;
        break;
      }
    }
    if ($idx !== -1) {
      flash("Found <strong>" . htmlspecialchars($song) . ".");
    } else {
      flash("Song not found.");
    }
  } else {
    flash('Please enter a song name to search.');
  }
}

// Delete Song
if ($action === 'delete') {
  $song = trim($_POST['song'] ?? '');
  if ($song !== '') {
    $key = array_search($song, $_SESSION['playlist']);
    if ($key !== false) {
      array_splice($_SESSION['playlist'], $key, 1);
      if ($_SESSION['currentIndex'] > $key)
        $_SESSION['currentIndex']--;
      elseif ($_SESSION['currentIndex'] >= count($_SESSION['playlist']))
        $_SESSION['currentIndex'] = count($_SESSION['playlist']) - 1;
      flash("Deleted song: <strong>" . htmlspecialchars($song) . "</strong>.");
    } else
      flash("Song not found.");
  } else
    flash('Enter song name to delete.');
}

// Delete Playlist
if ($action === 'delete_archive') {
  $idx = (int) ($_POST['archive_index'] ?? -1);
  if (isset($_SESSION['archives'][$idx])) {
    array_splice($_SESSION['archives'], $idx, 1);
    flash("Archived playlist deleted.");
  }
}

// Play Song
if ($action === 'play') {
  if (count($_SESSION['playlist']) === 0) {
    flash('Playlist is empty.');
  } else {
    if ($_SESSION['currentIndex'] === -1) {
      $_SESSION['currentIndex'] = 0;
    }
    $song = $_SESSION['playlist'][$_SESSION['currentIndex']];
    flash("Now playing: <strong>" . htmlspecialchars($song) . "</strong>");
  }
}

// Next Song
if ($action === 'next') {
  $len = count($_SESSION['playlist']);
  if ($len === 0) {
    flash('Playlist is empty.');
  } else {
    if ($_SESSION['currentIndex'] + 1 < $len) {
      $_SESSION['currentIndex']++;
      $song = $_SESSION['playlist'][$_SESSION['currentIndex']];
      flash("Next song: <strong>" . htmlspecialchars($song) . "</strong>");
    } else {
      flash("No next song. You're at the end of the playlist.");
    }
  }
}

// Previos Song
if ($action === 'previous') {
  $len = count($_SESSION['playlist']);
  if ($len === 0) {
    flash('Playlist is empty.');
  } else {
    if ($_SESSION['currentIndex'] > 0) {
      $_SESSION['currentIndex']--;
      $song = $_SESSION['playlist'][$_SESSION['currentIndex']];
      flash("Previous song: <strong>" . htmlspecialchars($song) . "</strong>");
    } else {
      flash("You're at the beginning of the playlist.");
    }
  }
}

// Load Playlist
if ($action === 'load_archive') {
  $idx = (int) ($_POST['archive_index'] ?? -1);
  if (isset($_SESSION['archives'][$idx])) {
    $_SESSION['playlist'] = $_SESSION['archives'][$idx];
    $_SESSION['currentIndex'] = count($_SESSION['playlist']) > 0 ? 0 : -1;
    flash("Loaded playlist #" . ($idx + 1));
  }
}

// Consume flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!-- === HTML === -->
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Playlist Manager</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="header">
    <header>
      <h1>🎵 Music Playlist Manager</h1>
    </header>
    <!-- <?php if ($flash): ?>
      <div class="alert" id="flash-alert"><?php $flash; ?></div>
    <?php endif; ?> -->

  </div>
  <!-- end header -->


  <div class="container">

    <div class="box">

      <section class="panel">
        <form method="post" class="grid">
          <div class="row buttons">
            <button name="action" value="create" type="submit" class="btn secondary">+ Create new playlist</button>
          </div>
          <div class="row">
            <input type="text" name="song" placeholder="Song name…" />
            <input type="number" name="position" placeholder="Position (for insert/edit)" min="1" />
          </div>
          <div class="row buttons">
            <button name="action" value="play" type="submit" class="btn green">▶</button>
            <button name="action" value="insert" type="submit" class="btn">Insert song</button>
            <button name="action" value="insert_at" class="btn">Insert at Position</button>
            <button name="action" value="search" type="submit" class="btn">Search song</button>
            <button name="action" value="delete" type="submit" class="btn danger">Delete song</button>
          </div>

        </form>

      </section>
      <!-- end panel -->

      <aside class="archives">
        <h3>Your Playlist</h3>
        <?php if (empty($_SESSION['archives'])): ?>
          <p class="muted">No archived playlists.</p>
        <?php else: ?>
          <?php foreach ($_SESSION['archives'] as $idx => $pl): ?>
            <form method="post" class="playlist-archive-form">
              <input type="hidden" name="archive_index" value="<?php echo $idx; ?>">
              <button type="submit" name="action" value="load_archive" class="playlist-archive">
                <strong>Playlist #<?php echo $idx + 1; ?> (<?php echo count($pl); ?> songs)</strong>
              </button>
              <button name="action" value="delete_archive" class="btn danger">X</button>
            </form>
          <?php endforeach; ?>
        <?php endif; ?>

      </aside>
      <!-- end aside -->

    </div>
    <!-- end box -->

    <div class="box">

    <?php if ($flash): ?>
      <div class="alert" id="flash-alert"><?php echo $flash; ?></div>
    <?php endif; ?>
      <section class="panel">
          <h2># Title</h2>
          <?php if (count($_SESSION['playlist']) === 0): ?>
            <p class="muted">Playlist is empty.</p>
          <?php else: ?>
            <ol class="list">
              <?php foreach ($_SESSION['playlist'] as $i => $name): ?>
                <li class="<?php echo ($i === $_SESSION['currentIndex']) ? 'active' : ''; ?>">
                  <span class="index"><?php echo $i + 1; ?>.</span>
                  <span class="title"><?php echo htmlspecialchars($name); ?></span>
                  <?php if ($i === $_SESSION['currentIndex']): ?>
                    <span class="badge">Now Playing</span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>

      </section>
      <!-- end panel -->

      <section class="paneltwo">
        <form method="post" class="grid">
          <div class="player-controls">
            <button name="action" value="previous" type="submit" class="btn secondary">⏮</button>
            <button name="action" value="play" type="submit" class="btn green">▶</button>
            <button name="action" value="next" type="submit" class="btn secondary">⏭</button>
          </div>

        </form>

      </section>
      <!-- end panel -->

    </div>
    <!-- end box -->

  </div>
  <!-- end container -->

</body>

</html>