<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - MÓDULO DE CIERRE (FOOTER)
 * ============================================================================
 * Archivo: footer.php
 * Propósito: Renderizar el pie de página global del panel de administración,
 * proveer los enlaces institucionales requeridos (Mapa del Sitio, Privacidad)
 * y cerrar de forma segura las etiquetas de jerarquía estructural del DOM.
 * * * Normativa de Accesibilidad (W3C):
 * - Implementación de role="contentinfo" para el bloque final.
 * - Atributos aria-label para navegación secundaria e íconos decorativos.
 * * * Dependencias de Interfaz:
 * - Requiere Bootstrap 5.3+ JS Bundle (popper.js incluido) para 
 * garantizar la interactividad de alertas, modales y menúes colapsables.
 * * @author Equipo de Desarrollo Check-Line
 * @version 1.0.0
 * ============================================================================
 */
?>
    </main> </div> </div> <footer class="text-white py-3 mt-4" style="background-color:#0A2342;" role="contentinfo" aria-label="Pie de página del sistema Check-Line">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2">
    
    <span class="small" aria-label="Derechos de autor">
      <i class="bi bi-airplane-fill me-1" aria-hidden="true"></i>
      Check-Line &copy; <?= date('Y') ?>
      <span class="visually-hidden">Todos los derechos reservados.</span>
    </span>
    
    <nav class="d-flex gap-3" aria-label="Navegación legal y de soporte del sistema">
      <a href="../mapa-sitio.php" class="text-white-50 text-decoration-none small" aria-label="Consultar el mapa del sitio web">
        Mapa del Sitio
      </a>
      <a href="../privacidad.php" class="text-white-50 text-decoration-none small" aria-label="Leer la política de privacidad de la plataforma">
        Política de Privacidad
      </a>
    </nav>
    
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>

</body>
</html>