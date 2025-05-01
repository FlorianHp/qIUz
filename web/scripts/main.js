document.querySelectorAll('.stamina-ring').forEach(el => {
  const progress = el.dataset.progress;
  if (progress) {
    el.style.setProperty('--progress', `${progress}%`);
  }
});