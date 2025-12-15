document.addEventListener("DOMContentLoaded", function () {
  const menuItems = document.querySelectorAll(".menu-item[data-panel]");
  const panelKonten = document.querySelectorAll(".panel-konten");

  menuItems.forEach(function (item) {
    item.addEventListener("click", function (event) {
      event.preventDefault();

      const targetId = item.getAttribute("data-panel");

      menuItems.forEach(function (m) {
        m.classList.remove("aktif");
      });
      item.classList.add("aktif");

      panelKonten.forEach(function (panel) {
        if (panel.id === targetId) {
          panel.classList.add("aktif");
        } else {
          panel.classList.remove("aktif");
        }
      });
    });
  });
});