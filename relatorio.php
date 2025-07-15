<?php
$data = json_decode(file_get_contents('data.json'), true) ?? [];
$api_key = "API GOOGLE";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>GEOakim - Relatório de Coletas</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <style>
    :root {
      --bg: #f0f2f5;
      --card: #ffffff;
      --accent: #0078d7;
      --text: #333;
      --subtext: #666;
      --border: #ddd;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--bg);
      margin: 0;
      padding: 20px;
      color: var(--text);
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
    }

    #map {
      width: 100%;
      height: 450px;
      margin-bottom: 30px;
      border-radius: 8px;
      border: 1px solid var(--border);
    }

    table.dataTable {
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      background-color: var(--card);
    }

    table.dataTable th {
      background-color: var(--accent);
      color: white;
      font-weight: 500;
      text-align: left;
    }

    table.dataTable tbody tr:hover {
      background-color: #e6f0ff;
      cursor: pointer;
    }

    .geo-icon {
      font-size: 18px;
      font-weight: bold;
    }

    small {
      color: var(--subtext);
      font-size: 11px;
    }

    #exportCsv {
      padding: 8px 16px;
      font-size: 14px;
      background: #0078d7;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      float: right;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

  <h1>GEOakim - Relatório de Coletas</h1>
  <button id="exportCsv">📥 Exportar CSV</button>
  <div style="clear: both;"></div>

  <div id="map"></div>

  <table id="coletaTable" class="display" style="width:100%">
    <thead>
      <tr>
        <th>Data/Hora</th>
        <th>IP</th>
        <th>Geo</th>
        <th>GPU</th>
        <th>SO/Navegador</th>
        <th>Resolução</th>
        <th>Idioma</th>
        <th>Fuso</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $markers = [];
        foreach(array_reverse($data) as $i => $entry):
          $geo = $entry['geo'] ?? false;
          $lat = $entry['latitude'] ?? null;
          $lon = $entry['longitude'] ?? null;
          $hasGeo = $geo && is_numeric($lat) && is_numeric($lon);
          $markerId = $hasGeo ? "m" . $i : null;

          if ($hasGeo) {
            $markers[] = [
              'id' => $markerId,
              'lat' => floatval($lat),
              'lng' => floatval($lon),
              'info' => "IP: {$entry['ip']}<br>Data: {$entry['timestamp']}"
            ];
          }
      ?>
      <tr data-marker="<?= $markerId ?? '' ?>">
        <td><?= $entry['timestamp'] ?></td>
        <td><?= $entry['ip'] ?></td>
        <td class="geo-icon"><?= $hasGeo ? '🟢' : '🔴' ?></td>
        <td><?= $entry['gpu_vendor'] ?> - <?= $entry['gpu_renderer'] ?></td>
        <td><?= $entry['platform'] ?><br><small><?= $entry['user_agent'] ?></small></td>
        <td><?= $entry['screen'] ?></td>
        <td><?= $entry['lang'] ?></td>
        <td><?= $entry['timezone'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- JS libs -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <script>
    let map, markers = {};

    $(document).ready(function () {
      const table = $('#coletaTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 20
      });

      $('#coletaTable tbody').on('click', 'tr', function () {
        const markerId = $(this).data('marker');
        if (markerId) {
          focusMarker(markerId);
        }
      });

      $('#exportCsv').on('click', function () {
        const data = table.rows({ search: 'applied' }).data().toArray();
        let csv = 'Data/Hora,IP,Geo,GPU,SO/Navegador,Resolução,Idioma,Fuso\n';

        data.forEach(row => {
          const cleanRow = row.map(cell => {
            const tmp = document.createElement("div");
            tmp.innerHTML = cell;
            return '"' + tmp.textContent.trim().replace(/\n/g, ' ') + '"';
          });
          csv += cleanRow.join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'relatorio_coletas.csv';
        a.click();
        URL.revokeObjectURL(url);
      });
    });

    function initMap() {
      map = new google.maps.Map(document.getElementById("map"), {
        zoom: 2,
        center: { lat: 0, lng: 0 },
        mapTypeControl: false,
        streetViewControl: false
      });

      const markerData = <?= json_encode($markers) ?>;

      markerData.forEach(m => {
        const marker = new google.maps.Marker({
          position: { lat: m.lat, lng: m.lng },
          map: map,
          title: m.info
        });

        const infoWindow = new google.maps.InfoWindow({ content: m.info });

        marker.addListener("click", () => infoWindow.open(map, marker));

        markers[m.id] = {
          marker: marker,
          infoWindow: infoWindow
        };
      });
    }

    function focusMarker(id) {
      const m = markers[id];
      if (m) {
        map.setZoom(14);
        map.panTo(m.marker.getPosition());
        m.infoWindow.open(map, m.marker);
      }
    }
  </script>

  <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= $api_key ?>&callback=initMap"></script>
</body>
</html>
