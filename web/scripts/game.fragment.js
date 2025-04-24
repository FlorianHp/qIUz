let currentQuestionIndex = 0;
let selectedAnswer = null;
let points = 0;

const quizData = [
  {
    question: "Was ist die Hauptstadt von Frankreich?",
    answers: { a: "Paris", b: "Berlin", c: "Madrid", d: "Rom" },
    correct: "a"
  },
  {
    question: "Welches Jahr hatte den ersten Mondflug?",
    answers: { a: "1965", b: "1969", c: "1972", d: "1959" },
    correct: "b"
  },
  {
    question: "Was ist 3 + 4?",
    answers: { a: "5", b: "6", c: "7", d: "8" },
    correct: "c"
  }
];

$(".answer").click(function () {
  $(".answer").removeClass("btn-primary");
  $(this).addClass("btn-primary");
  selectedAnswer = this.id.split("_")[1];
});

$("#answer_commit_btn").click(function () {
  if (!selectedAnswer) {
    alert("Bitte eine Antwort auswählen!");
    return;
  }

  const correct = quizData[currentQuestionIndex].correct;
  const correctBtn = $(`#answer_${correct}_btn`);
  const selectedBtn = $(`#answer_${selectedAnswer}_btn`);

  if (selectedAnswer === correct) {
    selectedBtn.addClass("btn-success");
    points++;
  } else {
    selectedBtn.addClass("btn-danger");
    correctBtn.addClass("btn-success");
  }

  $(".answer").prop("disabled", true);
  $("#answer_commit_btn").prop("hidden", true);
  $("#continue_btn").prop("hidden", false);
});

$("#continue_btn").click(function () {
  currentQuestionIndex++;

  if (currentQuestionIndex < quizData.length) {
    showNextQuestion();
  } else {
    endQuiz();
  }
});


function showNextQuestion() {
  const current = quizData[currentQuestionIndex];
  selectedAnswer = null;

  $("#question_text").text(current.question);
  $("#answer_a").text(current.answers.a);
  $("#answer_b").text(current.answers.b);
  $("#answer_c").text(current.answers.c);
  $("#answer_d").text(current.answers.d);

  $(".answer")
    .removeClass("btn-primary btn-success btn-danger")
    .prop("disabled", false);

  $("#continue_btn").prop("hidden", true);
  $("#answer_commit_btn").prop("hidden", false);
}

function endQuiz() {
  $("#question").fadeOut(function () {
    $("#endpoints").text(points);
    $("#possiblepoints").text(quizData.length);
    $("#over").fadeIn();
  });
}

if (selectedAnswer === correct) {
  selectedBtn.addClass("btn-success");
  points++;
}




