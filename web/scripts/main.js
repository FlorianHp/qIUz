document.querySelectorAll('.stamina-ring').forEach(el => {
  const progress = parseFloat(el.dataset.progress);
  const total    = parseFloat(el.dataset.total);

  if (!isNaN(progress) && !isNaN(total) && total > 0) {
    const angle = (progress / total) * 360;
    const fill  = el.querySelector('.stamina-fill');

    if (fill) {
      fill.style.setProperty('--progress', `${angle}deg`);
    }
  }
});
