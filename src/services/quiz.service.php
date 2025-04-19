<?php 
function getQuiz($amount = 3, $settings = null) {

  $module  = !empty($settings->module) ? $settings->module : null;
  $lection = !empty($settings->lection) ? $settings->lection : null;

  $sql = "
    SELECT 
      * 
    FROM 
      content 
    WHERE 
      (:module IS NULL OR modul_name = :module)
    AND 
      (:lection IS NULL OR lektion = :lection)
    ORDER BY
      RANDOM()
    LIMIT :amount
  ";

  $rows = query($sql, [
    'module'  => $module,
    'lection' => $lection,
    'amount'  => (int) $amount
  ]);

  foreach ($rows as &$row) {
    $row['answers'] = json_decode($row['answers'], true);
    shuffle($row['answers']);
  }

  return $rows;
}