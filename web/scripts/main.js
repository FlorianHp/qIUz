$(function() {
});


// Start-Button klick
$(".start").click(function() {
  $(".quiz_start").fadeOut(function() {
    startQuiz();
  });
});

// Quiz starten
function startQuiz() {
  showNextQuestion();
  $("#question").removeAttr("hidden").fadeIn();
}

