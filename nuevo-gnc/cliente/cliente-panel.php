<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
  header("Location: login.html");
  exit();
}

$conexion = new mysqli("localhost", "root", "", "taller_gnc");
if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    /* ===== Reset básico y sticky layout ===== */
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; }
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background: #f4f4f4;
      color: #333;
      font-family: Arial, sans-serif;
    }

    /* ===== HEADER VERDE (fijo arriba) ===== */
    .header-bar {
      background-color: #016b3b;
      padding: 0.8rem 2rem;
      display: flex;
      align-items: center;
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .header-bar img { height: 45px; }

    /* ===== CONTENIDO ===== */
    main.page {
      flex: 1;
      padding: 7rem 2rem 2rem; /* compensa header fijo */
    }

    .card {
      background: #fff;
      padding: 1.5rem;
      border-radius: 10px;
      margin-bottom: 2rem;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .vencimientos-box {
      background: #fff8dc;
      border-left: 6px solid #f1c40f;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    /* ===== TURNO ESTÉTICO ===== */
    .turnero-estetico {
      background: #faffea;
      border-left: 6px solid #b3d944;
      border-radius: 10px;
      padding: 1.5rem;
      margin-top: 1rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }
    .turnero-estetico h3 { color: #558b2f; margin-bottom: 1rem; }
    .turnero-estetico label {
      font-weight: bold; color: #33691e; display: block;
      margin-top: 1rem; margin-bottom: 0.2rem;
    }
    .turnero-estetico input, .turnero-estetico select {
      width: 100%; padding: 0.5rem; border: 1px solid #cddc39;
      border-radius: 6px; background: #f8fff0;
    }
    .turnero-estetico input[type="date"] { width: 100%; }
    .turnero-estetico button {
      margin-top: 1.5rem; width: 100%; background-color: #8bc34a; color: #fff;
      font-weight: bold; border: none; padding: 0.8rem; border-radius: 6px; cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .turnero-estetico button:hover { background-color: #689f38; }

    /* ===== Sugerencias autocomplete ===== */
    .sugerencias {
      list-style:none; margin:0; padding:0;
      position:absolute; top:100%; left:0; right:0;
      background:#fff; border:1px solid #ddd; border-top:none;
      max-height: 220px; overflow:auto; display:none; z-index: 1000;
    }
    .sugerencias li {
      padding: 8px 10px; cursor: pointer; border-top: 1px solid #eee;
    }
    .sugerencias li:hover { background:#f5f5f5; }

    /* ===== FOOTER ===== */
    .footer-bar {
      background-color: #016b3b;
      color: #fff;
      padding: 0.8rem 0;
      width: 100%;
      text-align: center;
      box-shadow: 0 -2px 5px rgba(0,0,0,0.08);
      margin: 0;
    }
    .footer-content { margin: 0; padding: 0; }
    .footer-content p { margin: 0.25rem 0; font-size: 0.9rem; }
  </style>
</head>
<body>

  <!-- HEADER VERDE CON LOGO -->
  <div class="header-bar">
    <img src="../landing/assets/img/LOGO.png" alt="Logo Taller GNC">
  </div>

  <!-- CONTENIDO -->
  <main class="page">
    <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? ''); ?> 👋</h1>

    <!-- 🔍 SELECTOR DE PATENTE CON AUTOCOMPLETADO -->
    <div class="card">
      <h3>Buscar vehículo por patente</h3>

      <form method="GET" action="">
        <div style="position: relative; max-width: 480px;">
          <input
            type="text"
            name="patente_seleccionada"
            id="patenteInput"
            placeholder="ESCRIBÍ LA PATENTE"
            autocomplete="off"
            required
            style="padding: 0.5rem; width: 100%; text-transform:uppercase;"
          />

          <!-- Lista de sugerencias -->
          <ul id="sugerencias" class="sugerencias"></ul>
        </div>

        <button type="submit" style="padding: 0.5rem; margin-top: 8px;">🔍 Ver Información</button>
      </form>
    </div>

    <?php if (isset($_GET['patente_seleccionada'])): ?>
      <?php
        $patente = strtoupper(trim($_GET['patente_seleccionada']));
        $stmt = $conexion->prepare("SELECT modelo FROM vehiculos WHERE patente = ? AND activo = 1");
        $stmt->bind_param("s", $patente);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
          echo "<p style='color:red;'>❌ No se encontró el vehículo activo.</p>";
        } else {
          $vehiculo = $res->fetch_assoc();
          $vtoStmt = $conexion->prepare("SELECT oblea, prueba_hidraulica FROM vencimientos WHERE vehiculo_patente = ?");
          $vtoStmt->bind_param("s", $patente);
          $vtoStmt->execute();
          $vencimientos = $vtoStmt->get_result()->fetch_assoc();

          $servStmt = $conexion->prepare("SELECT fecha, descripcion FROM servicios WHERE vehiculo_patente = ? AND activo = 1 ORDER BY fecha DESC");
          $servStmt->bind_param("s", $patente);
          $servStmt->execute();
          $servicios = $servStmt->get_result();
      ?>

      <div class="card">
        <h2><?php echo htmlspecialchars($vehiculo['modelo']); ?> (<?php echo htmlspecialchars($patente); ?>)</h2>

        <div class="vencimientos-box">
          <p>📅 <strong>Oblea:</strong> <?php echo htmlspecialchars($vencimientos['oblea'] ?? 'Sin datos'); ?></p>
          <p>🧪 <strong>Prueba hidráulica:</strong> <?php echo htmlspecialchars($vencimientos['prueba_hidraulica'] ?? 'Sin datos'); ?></p>
        </div>

        <div class="turnero-estetico">
          <h3>Solicitar Turno</h3>
          <form method="POST" action="enviar_turno.php">
            <input type="hidden" name="patente" value="<?php echo htmlspecialchars($patente); ?>" />
            <label>Servicio</label>
            <select name="servicio" id="servicio" onchange="mostrarOtroServicio()" required>
              <option value="">Seleccionar...</option>
              <option value="Cambio de aceite">Cambio de aceite</option>
              <option value="Cambio de oblea">Cambio de oblea</option>
              <option value="Prueba hidráulica">Prueba hidráulica</option>
              <option value="Otro">Otro</option>
            </select>

            <div id="otro-servicio" style="display: none;">
              <label>Describí el servicio</label>
              <input type="text" name="otro_servicio" placeholder="Ej: Limpieza de inyectores" />
            </div>

            <label>Fecha deseada</label>
            <input type="date" name="fecha" id="fechaTurno" required />

            <label>Hora deseada</label>
            <select name="hora" required>
              <optgroup label="Mañana (09:00 - 12:00)">
                <?php
                for ($t = strtotime("09:00"); $t <= strtotime("12:00"); $t += 1800) {
                  echo '<option value="' . date("H:i", $t) . '">' . date("H:i", $t) . '</option>';
                }
                ?>
              </optgroup>
              <optgroup label="Tarde (16:30 - 19:30)">
                <?php
                for ($t = strtotime("16:30"); $t <= strtotime("19:30"); $t += 1800) {
                  echo '<option value="' . date("H:i", $t) . '">' . date("H:i", $t) . '</option>';
                }
                ?>
              </optgroup>
            </select>

            <button type="submit">📅 Solicitar Turno</button>
          </form>
        </div>

        <h3>Historial de servicios</h3>
        <div style="display: flex; gap: 4rem;">
          <div>
            <h4>Fecha</h4>
            <?php if ($servicios->num_rows > 0): ?>
              <?php while ($s = $servicios->fetch_assoc()): ?>
                <p><?php echo htmlspecialchars($s['fecha']); ?></p>
              <?php endwhile; ?>
            <?php else: ?>
              <p>Sin registros</p>
            <?php endif; ?>
          </div>
          <?php
          $servStmt->execute();
          $servicios = $servStmt->get_result();
          ?>
          <div>
            <h4>Descripción</h4>
            <?php if ($servicios->num_rows > 0): ?>
              <?php while ($s = $servicios->fetch_assoc()): ?>
                <p><?php echo htmlspecialchars($s['descripcion']); ?></p>
              <?php endwhile; ?>
            <?php else: ?>
              <p>Sin registros</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php
            $stmt->close();
            $vtoStmt->close();
            $servStmt->close();
          }
        ?>
    <?php endif; ?>
  </main>

  <!-- FOOTER (no fijo, sin márgenes, a todo ancho) -->
  <footer class="footer-bar">
    <div class="footer-content">
      <p>&copy; <?php echo date("Y"); ?> Taller GNC Rivadavia. Todos los derechos reservados.</p>
      <p>📍 Comandante Cabot 2024, Oeste, Rivadavia, San Juan | 📞 264 411-6975 | ✉️ GncRivadavia01@gmail.com</p>
    </div>
  </footer>

  <script>
    // ==== Mostrar campo "Otro servicio" ====
    function mostrarOtroServicio() {
      const select = document.getElementById('servicio');
      const otro = document.getElementById('otro-servicio');
      if (otro) otro.style.display = select.value === "Otro" ? "block" : "none";
    }

    // ==== Lista de feriados (completá los tuyos) ====
    // Formato: 'YYYY-MM-DD'
    const HOLIDAYS = new Set([
      // Ejemplos fijos AR:
      '2025-01-01', // Año Nuevo
      '2025-03-24', // Memoria
      '2025-04-02', // Malvinas
      '2025-05-01', // Trabajador
      '2025-05-25', // Revolución de Mayo
      '2025-06-20', // Belgrano
      '2025-07-09', // Independencia
      '2025-12-08', // Inmaculada
      '2025-12-25', // Navidad
      // Agregá inamovibles/trasladables/puentes según tu calendario
    ]);

    // ==== Helpers de fechas ====
    const pad = (n) => String(n).padStart(2, '0');
    const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    const parseYMD = (s) => {
      const [y,m,d] = s.split('-').map(Number);
      return new Date(y, m-1, d);
    };
    const isWeekend = (d) => {
      const day = d.getDay(); // 0 dom, 6 sab
      return day === 0 || day === 6;
    };
    const isHoliday = (d) => HOLIDAYS.has(fmt(d));
    const isBlocked = (d) => isWeekend(d) || isHoliday(d);

    // ==== Mensaje inline debajo del input fecha ====
    const ensureMsgEl = (inputEl) => {
      let msg = document.getElementById('msgFechaTurno');
      if (!msg) {
        msg = document.createElement('div');
        msg.id = 'msgFechaTurno';
        msg.style.marginTop = '6px';
        msg.style.fontSize = '0.9rem';
        msg.style.color = '#b00020';
        inputEl.insertAdjacentElement('afterend', msg);
      }
      return msg;
    };

    // ==== Autocomplete de patentes ====
    (() => {
      const input = document.getElementById('patenteInput');
      const list  = document.getElementById('sugerencias');
      if (!input || !list) return;

      let idxFocus = -1;
      let items = [];
      let timer = null;

      const debounce = (fn, delay=200) => (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
      };

      const renderList = (data) => {
        list.innerHTML = '';
        idxFocus = -1;
        items = data || [];
        if (!items.length) { list.style.display = 'none'; return; }

        items.forEach(pat => {
          const li = document.createElement('li');
          li.textContent = pat;
          li.tabIndex = 0;

          li.addEventListener('mouseenter', () => {
            [...list.children].forEach(el => el.style.background='');
            li.style.background = '#f5f5f5';
          });
          li.addEventListener('mouseleave', () => { li.style.background = ''; });
          li.addEventListener('click', () => {
            input.value = pat;
            list.style.display = 'none';
          });

          list.appendChild(li);
        });

        list.style.display = 'block';
      };

      const buscar = async (q) => {
        q = (q || '').trim().toUpperCase();
        if (q.length < 1) { renderList([]); return; }
        try {
          const res = await fetch('buscar_patentes.php?q=' + encodeURIComponent(q));
          if (!res.ok) throw new Error('Error ' + res.status);
          const data = await res.json();
          renderList(data);
        } catch (e) {
          renderList([]);
          console.error(e);
        }
      };

      input.addEventListener('input', (e)=>{ e.target.value = e.target.value.toUpperCase(); });
      input.addEventListener('input', debounce((e) => buscar(e.target.value), 200));

      document.addEventListener('click', (e) => {
        if (!list.contains(e.target) && e.target !== input) list.style.display = 'none';
      });

      input.addEventListener('keydown', (e) => {
        const visible = list.style.display !== 'none';
        if (!visible) return;
        const total = list.children.length;
        if (!total) return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          idxFocus = (idxFocus + 1) % total;
          focusItem(idxFocus);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          idxFocus = (idxFocus - 1 + total) % total;
          focusItem(idxFocus);
        } else if (e.key === 'Enter') {
          if (idxFocus >= 0 && idxFocus < total) {
            e.preventDefault();
            const li = list.children[idxFocus];
            input.value = li.textContent;
            list.style.display = 'none';
          }
        } else if (e.key === 'Escape') {
          list.style.display = 'none';
        }
      });

      const focusItem = (i) => {
        [...list.children].forEach((el, idx) => { el.style.background = idx === i ? '#e9f5ee' : ''; });
        const el = list.children[i];
        if (el) el.scrollIntoView({ block: 'nearest' });
      };
    })();

    // ==== Fecha: min hoy + bloqueo sábados, domingos y feriados ====
    (() => {
      const f = document.getElementById('fechaTurno');
      if (!f) return;

      // min = hoy
      const hoy = new Date();
      f.min = fmt(hoy);

      const msg = ensureMsgEl(f);

      // Ajusta a próximo hábil si está bloqueado o en el pasado
      const nextBusinessIfNeeded = (d) => {
        const today = new Date();
        today.setHours(0,0,0,0);
        if (d < today) d = new Date(today);

        while (isBlocked(d)) {
          d.setDate(d.getDate() + 1);
        }
        return d;
      };

      const applyValidation = () => {
        if (!f.value) return;
        let d = parseYMD(f.value);
        const original = fmt(d);
        const adjusted = fmt(nextBusinessIfNeeded(d));

        if (adjusted !== original) {
          f.value = adjusted;
          msg.textContent = `La fecha seleccionada no está disponible (sábado, domingo o feriado). Se ajustó a ${adjusted}.`;
        } else {
          msg.textContent = '';
        }
      };

      // Validar al cambiar/blur
      f.addEventListener('change', applyValidation);
      f.addEventListener('blur', applyValidation);

      // Si hubiese un value precargado, validarlo al cargar
      if (f.value) applyValidation();
    })();
  </script>
</body>
</html>
