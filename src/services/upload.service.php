<?php 

function modules() {

  $rows = query('
    SELECT DISTINCT
      modul_name AS module
    FROM
      content
  ');

  foreach ($rows as $row) {
    if (isset($row['module']) && is_string($row['module'])) {
      $modules[] = trim($row['module']);
    }
  }

  return $modules;
}
function upload($data) {

  query('
    INSERT INTO 
      content (
        id, 
        modul_name, 
        lektion, 
        question, 
        answers, 
        description, 
        rating)
    VALUES (
      :id, 
      :modul_name, 
      :lektion, 
      :question, 
      :answers, 
      :description, 
      :rating
    )
  ', $data);
}

function handleUpload($user) {
  function clean($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

  $module   = clean($_POST['module']);
  $lection  = (int) $_POST['lection'];
  $question = clean($_POST['question']);
  $correct  = clean($_POST['correct']);
  $false1   = clean($_POST['incorrect_1']);
  $false2   = clean($_POST['incorrect_2']);
  $false3   = clean($_POST['incorrect_3']);
  $descr    = clean($_POST['description']);

  $id = substr(md5($question), 0, 8);

  $answers = json_encode([
    ['text' => $correct, 'correct' => true],
    ['text' => $false1,  'correct' => false],
    ['text' => $false2,  'correct' => false],
    ['text' => $false3,  'correct' => false]
  ], JSON_UNESCAPED_UNICODE);

  $data = [
    'id'          => $id,
    'modul_name'  => $module,
    'lektion'     => $lection,
    'question'    => $question,
    'answers'     => $answers,
    'description' => $descr,
    'rating'      => 0,
    'creator'     => $user
  ];

  try {
    upload($data);
  } catch (\Throwable $th) {
    file_put_contents('upload.log', "Error: " . $th, FILE_APPEND);
    header("Location: /upload?success=0");
    exit;
  }

  header("Location: /upload?success=1");
  exit;
}
