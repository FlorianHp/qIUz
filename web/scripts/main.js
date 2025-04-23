console.log("✅ main.js wurde geladen");
console.log(document.getElementById("info-dialog"));


document.addEventListener("DOMContentLoaded", () => {
    const dialog = document.getElementById("info-dialog");
    const content = document.getElementById("dialog-content");
    const closeBtn = document.getElementById("close-dialog");
  
    // Öffnen + Inhalt laden
    document.querySelectorAll(".open-dialog").forEach(link => {
      link.addEventListener("click", async e => {
        e.preventDefault();
        const url = link.dataset.url;
  
        try {
          const response = await fetch(url);
          const html = await response.text();
          content.innerHTML = html;
          dialog.showModal();
        } catch (err) {
          content.innerHTML = "<p>Fehler beim Laden der Inhalte.</p>";
          dialog.showModal();
        }
      });
    });
  
    // Schließen
    closeBtn.addEventListener("click", () => {
      dialog.close();
    });
  });
  
  