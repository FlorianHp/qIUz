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

document.addEventListener('bq:fetched', (event) => {
    if (event.detail === 'setup') {

      const select = document.querySelector('#module-select');
      if (select) {
        const selected = select.dataset.selected;
        if (selected) {
          select.value = selected;
        }
      }
    }
  }
);

