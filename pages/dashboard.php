<?php
session_start();
include("../database/connection.php");
/** @var mysqli $conn */

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];
$admin_username = $_SESSION['admin_username'];

// Get total voters, votes cast, and votes remaining
$sql = "SELECT
  COUNT(*) AS total_voters,
  COUNT(CASE WHEN hasVoted = 1 THEN 1 END) AS votes_cast,
  COUNT(CASE WHEN hasVoted = 0 THEN 1 END) AS votes_remaining
FROM user_information";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_voters = $row['total_voters'];
$votes_cast = $row['votes_cast'];
$votes_remaining = $row['votes_remaining'];

// Get ordered list of positions
$sql = "SELECT position
        FROM candidates
        GROUP BY position
        ORDER BY
          CASE position
            WHEN 'President' THEN 1
            WHEN 'Vice President' THEN 2
            WHEN 'Secretary' THEN 3
            WHEN 'Treasurer' THEN 4
            WHEN 'Auditor' THEN 5
            WHEN 'PRO' THEN 6
            ELSE 99
          END";
$position_result = mysqli_query($conn, $sql);

// Fetch candidates for each position
$results_sections = [];

while ($position_row = mysqli_fetch_assoc($position_result)) {
  $position = $position_row['position'];
  $candidates_sql = "SELECT * FROM candidates WHERE position = '$position'";
  $candidates_result = mysqli_query($conn, $candidates_sql);

  $total_votes = 0;
  $candidates = [];

  while ($candidate = mysqli_fetch_assoc($candidates_result)) {
    $vote = isset($candidate['vote_count']) ? (int)$candidate['vote_count'] : 0;
    $total_votes += $vote;
    $candidate['vote_count'] = $vote;
    $candidates[] = $candidate;
  }

  $results_sections[] = [
    'position' => $position,
    'candidates' => $candidates,
    'total_votes' => $total_votes
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Voting Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="../styles/dashboard-style.css" />
  <link rel="stylesheet" href="../styles/results-style.css" />
  <link rel="shortcut icon" href="../img/ToreVote.png" type="image/x-icon">
</head>

<body>
  <div class="dashboard">
    <!-- sidebar -->
    <aside class="sidebar">
      <img src="../img/logo2.png" alt="VotingSys Logo" style="width: 80px; height: auto; display: block; margin: 0 auto;" />
      <h5 class="admin" style="margin-top: 20px; text-align: center;">Administrator</h5>
      <nav>
        <a href="#" class="active">Dashboard</a>
        <a href="./partylist_maintenance.php">Partylist Maintenance</a>
        <a href="./position_maintenance.php">Position Maintenance</a>
        <a href="./candidates_maintenance.php">Candidate Maintenance</a>
        <a href="./voters_maintenance.php">Voters Maintenance</a>
        <a href="./admin-logout.php" class="logout-button" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a>
        <a href="./clear-db.php">Clear Database</a>
      </nav>
    </aside>

    <!-- main -->
    <main class="main-content">


      <header class="topbar">
        <h1>Welcome, <?php echo $admin_username ?></h1>
      </header>

      <section class="cards">
        <div class="card">
          <h3>Total Voters</h3>
          <p><?php echo $total_voters ?></p>
        </div>
        <div class="card">
          <h3>Votes Cast</h3>
          <p><?php echo $votes_cast ?></p>
        </div>
        <div class="card">
          <h3>Remaining</h3>
          <p><?php echo $votes_remaining ?></p>
        </div>
      </section>

      <main class="result-content">
        <header class="topbar">
          <h1>Voting Results</h1>
          <p>See who’s leading for each position.</p>
        </header>

        <?php foreach ($results_sections as $section): ?>
          <section class="results-section">
            <h2 class="position-title"><?= htmlspecialchars($section['position']) ?></h2>
            <div class="results-list">
              <?php foreach ($section['candidates'] as $c):
                $percentage = $section['total_votes'] > 0 ? round(($c['vote_count'] / $section['total_votes']) * 100) : 0;
              ?>
                <div class="result-card">
                  <img src="../img/<?= htmlspecialchars($c['photo']) ?>" alt="<?= htmlspecialchars($c['candidate_name']) ?>">
                  <div class="info">
                    <h3><?= htmlspecialchars($c['candidate_name']) ?></h3>
                    <p>Votes: <?= $c['vote_count'] ?></p>
                    <div class="progress-bar">
                      <div class="fill" style="width: <?= $percentage ?>%;"><?= $percentage ?>%</div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      </main>

      <!-- Modal -->
      <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Alert</h1>
            </div>
            <div class="modal-body">
              You have already casted your vote
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
            </div>
            <div class="modal-body">
              Are you sure you want to logout?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <a href="./admin-logout.php" class="btn btn-primary">Yes, Logout</a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>

</html>