<?php
session_start();
include("../database/connection.php");
/** @var mysqli $conn */

$id_number = $_SESSION['id_number'];
echo $id_number;
if (!$id_number) {
  header("Location: index.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  foreach ($_POST as $position => $candidate_id) {
    $position = strtolower(str_replace(' ', '_', $position));
    $stmt = $conn->prepare("UPDATE `candidates` SET `vote_count` =  `vote_count` + 1 WHERE `id_num` = ? AND REPLACE(LOWER(`position`), ' ', '_') = ?");
    $stmt->bind_param("is", $candidate_id, $position);
    $stmt->execute();
  }
}

  $setVoted = $conn->prepare("UPDATE user_information SET `hasVoted` = 1 WHERE `id_number` = ?");
  $setVoted->bind_param("i", $id_number);
  $setVoted->execute();
  $setVoted->close();

  header("Location: vote.php");
  exit();

?>
