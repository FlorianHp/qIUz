<?php 
function getLeaderboard($amount = 5) {

  $sql = "
    SELECT 
      * 
    FROM 
      score 
    ORDER BY
      wins DESC,
      score DESC
    LIMIT :amount
  ";

  $rows = query($sql, [
    'amount'  => (int) $amount
  ]);

  foreach ($rows as &$row) {
    $row['review'] = json_decode($row['review'], true);
  }

  return $rows;
}