document.addEventListener('DOMContentLoaded', function () {
      const menuItems = document.querySelectorAll('.menu-item[data-panel]');
      const panels    = document.querySelectorAll('.panel-konten');

      menuItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const target = this.getAttribute('data-panel');

          menuItems.forEach(m => m.classList.remove('aktif'));
          this.classList.add('aktif');

          panels.forEach(p => {
            if (p.id === target) p.classList.add('aktif');
            else p.classList.remove('aktif');
          });
        });
      });

      const mapDiv = document.getElementById('mapTambah');
      if (mapDiv) {
        const map = L.map('mapTambah').setView([-6.2, 106.8], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = null;
        map.on('click', function(e) {
          const lat = e.latlng.lat.toFixed(6);
          const lng = e.latlng.lng.toFixed(6);

          document.getElementById('latitude').value  = lat;
          document.getElementById('longitude').value = lng;

          if (marker) {
            map.removeLayer(marker);
          }
          marker = L.marker(e.latlng).addTo(map);
        });
      }
    });