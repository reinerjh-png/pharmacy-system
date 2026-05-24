<?php
// modules/ajustes/branding.php
// Panel de configuración de identidad de marca — Solo administrador
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/branding.php';

verificar_sesion();

// Solo administradores
if ((int)$_SESSION['rol_id'] !== 1) {
    header('Location: /sys-farmacia/index.php');
    exit;
}

$b = branding();
$mensaje       = '';
$tipo_mensaje  = '';

$pagina_titulo = 'Configuración de Branding';
include __DIR__ . '/../../views/layout/header.php';
?>

<style>
/* ── Panel Branding ── */
.branding-wrap {
    max-width: 840px;
    margin: 0 auto;
}

.branding-section {
    background: var(--bg-card);
    border: 1px solid var(--color-borde);
    border-radius: var(--radio-lg);
    margin-bottom: 24px;
    overflow: hidden;
}

.branding-section-header {
    padding: 20px 28px;
    border-bottom: 1px solid var(--color-borde);
    display: flex;
    align-items: center;
    gap: 12px;
}

.branding-section-header svg {
    width: 20px; height: 20px;
    color: var(--color-primario);
    flex-shrink: 0;
}

.branding-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--texto-principal);
    margin: 0;
}

.branding-section-desc {
    font-size: 0.8rem;
    color: var(--texto-secundario);
    margin: 2px 0 0 0;
}

.branding-body {
    padding: 28px;
}

/* Grid de campos */
.branding-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 640px) {
    .branding-grid-2 { grid-template-columns: 1fr; }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-field label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--texto-principal);
    letter-spacing: 0.01em;
}

.form-field .field-hint {
    font-size: 0.75rem;
    color: var(--texto-secundario);
    margin-top: 4px;
}

.form-input {
    height: 44px;
    padding: 0 14px;
    border: 1.5px solid var(--color-borde);
    border-radius: var(--radio-md);
    font-size: 0.9rem;
    color: var(--texto-principal);
    background: var(--bg-fondo);
    font-family: inherit;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
    width: 100%;
}

.form-input:focus {
    border-color: var(--color-primario);
    background: var(--bg-card);
    box-shadow: 0 0 0 3px var(--color-primario-claro);
}

/* ── Color Picker ── */
.color-picker-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    height: 44px;
    padding: 0 10px;
    border: 1.5px solid var(--color-borde);
    border-radius: var(--radio-md);
    background: var(--bg-fondo);
    cursor: pointer;
    transition: border-color 0.15s;
}

.color-picker-wrap:focus-within {
    border-color: var(--color-primario);
    box-shadow: 0 0 0 3px var(--color-primario-claro);
}

.color-swatch {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 2px solid rgba(0,0,0,0.1);
    flex-shrink: 0;
    transition: transform 0.15s;
}

.color-picker-input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}

.color-hex-value {
    font-size: 0.85rem;
    font-family: 'SF Mono', 'Fira Code', monospace;
    color: var(--texto-principal);
    font-weight: 500;
    flex: 1;
}

/* ── Logo Preview ── */
.logo-preview-area {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: var(--bg-fondo);
    border: 1.5px dashed var(--color-borde);
    border-radius: var(--radio-md);
    margin-bottom: 16px;
    transition: border-color 0.2s;
}

.logo-preview-area:hover {
    border-color: var(--color-primario);
}

.logo-preview-img {
    width: 80px; height: 80px;
    border-radius: 12px;
    object-fit: contain;
    background: var(--bg-card);
    border: 1px solid var(--color-borde);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.logo-preview-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.logo-preview-img svg {
    width: 40px; height: 40px;
    color: var(--texto-secundario);
    opacity: 0.4;
}

.logo-upload-actions { flex: 1; }
.logo-upload-actions p {
    font-size: 0.82rem;
    color: var(--texto-secundario);
    margin: 6px 0 0;
}

/* ── Preview Barra ── */
.preview-strip {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-radius: var(--radio-md);
    background: var(--bg-fondo);
    border: 1px solid var(--color-borde);
    margin-top: 16px;
}

.preview-strip-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.preview-strip-label {
    font-size: 0.78rem;
    color: var(--texto-secundario);
}

.preview-btn-demo {
    height: 36px;
    padding: 0 16px;
    border-radius: var(--radio-md);
    border: none;
    font-size: 0.82rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: filter 0.15s;
}

.preview-btn-demo:hover { filter: brightness(1.1); }

/* ── Toast ── */
.toast-branding {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-radius: var(--radio-md);
    font-size: 0.9rem;
    font-weight: 500;
    color: #fff;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    transform: translateY(80px);
    opacity: 0;
    transition: transform 0.3s cubic-bezier(.22,1,.36,1), opacity 0.3s;
    pointer-events: none;
}

.toast-branding.show {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
}

.toast-branding svg { width: 18px; height: 18px; flex-shrink: 0; }

/* ── Botones ── */
.branding-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 28px;
    border-top: 1px solid var(--color-borde);
    background: var(--bg-fondo);
}

.btn-branding-save {
    height: 42px;
    padding: 0 24px;
    background: var(--color-primario);
    color: #fff;
    border: none;
    border-radius: var(--radio-md);
    font-size: 0.88rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.btn-branding-save:hover {
    background: var(--color-primario-hover, var(--color-primario));
    transform: translateY(-1px);
}

.btn-branding-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-branding-save svg, .btn-branding-reset svg { width: 16px; height: 16px; }

.btn-branding-reset {
    height: 42px;
    padding: 0 20px;
    background: transparent;
    color: var(--texto-secundario);
    border: 1.5px solid var(--color-borde);
    border-radius: var(--radio-md);
    font-size: 0.88rem;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s;
}

.btn-branding-reset:hover {
    border-color: #ef4444;
    color: #ef4444;
}

/* Spinner */
.spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: none;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Identidad de Marca</h1>
            <p class="page-subtitle">Personaliza cómo se ve el sistema para tu farmacia</p>
        </div>
    </div>

    <div class="branding-wrap">
        <form id="form-branding" enctype="multipart/form-data">

            <!-- ── SECCIÓN 1: Identidad Básica ── -->
            <div class="branding-section">
                <div class="branding-section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.375.375 0 01.375.375v1.875c0 .207.168.375.375.375H9a.375.375 0 00.375-.375V15a.375.375 0 01.375-.375h3.75m-4.5 0H9" />
                    </svg>
                    <div>
                        <h2 class="branding-section-title">Identidad Básica</h2>
                        <p class="branding-section-desc">Nombre, slogan y logo de tu farmacia</p>
                    </div>
                </div>
                <div class="branding-body">
                    <div class="branding-grid-2" style="margin-bottom: 20px;">
                        <div class="form-field" style="grid-column: 1 / -1;">
                            <label for="br-nombre">Nombre de la Farmacia *</label>
                            <input type="text" id="br-nombre" name="farmacia_nombre" class="form-input"
                                   value="<?= htmlspecialchars($b['farmacia_nombre']) ?>"
                                   placeholder="Ej: Farmacia San Miguel" required maxlength="150">
                        </div>
                        <div class="form-field" style="grid-column: 1 / -1;">
                            <label for="br-slogan">Slogan / Descripción corta</label>
                            <input type="text" id="br-slogan" name="farmacia_slogan" class="form-input"
                                   value="<?= htmlspecialchars($b['farmacia_slogan'] ?? '') ?>"
                                   placeholder="Ej: Sistema de Gestión Profesional" maxlength="255">
                            <span class="field-hint">Aparece debajo del nombre en el login</span>
                        </div>
                    </div>

                    <!-- Logo -->
                    <div class="form-field">
                        <label>Logo de la Farmacia</label>
                        <div class="logo-preview-area" id="logo-preview-area">
                            <div class="logo-preview-img" id="logo-preview-box">
                                <?php if ($b['farmacia_logo_url']): ?>
                                    <img id="logo-preview-img-el" src="<?= htmlspecialchars($b['farmacia_logo_url']) ?>" alt="Logo actual">
                                <?php else: ?>
                                    <svg id="logo-preview-placeholder" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="logo-upload-actions">
                                <label for="br-logo-file" class="btn btn-secundario" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px; height:36px; padding:0 14px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                    Subir archivo
                                </label>
                                <input type="file" id="br-logo-file" name="logo_file" accept=".jpg,.jpeg,.png,.svg"
                                       style="display:none;" aria-label="Subir logo">
                                <p>JPG, PNG o SVG · Máximo 2 MB</p>
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="br-logo-url">O pegar URL externa del logo</label>
                            <input type="url" id="br-logo-url" name="farmacia_logo_url" class="form-input"
                                   value="<?= htmlspecialchars($b['farmacia_logo_url'] ?? '') ?>"
                                   placeholder="https://ejemplo.com/logo.png">
                            <span class="field-hint">Útil si estás en InfinityFree u hosting con restricciones de subida</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 2: Colores ── -->
            <div class="branding-section">
                <div class="branding-section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z" />
                    </svg>
                    <div>
                        <h2 class="branding-section-title">Colores Corporativos</h2>
                        <p class="branding-section-desc">El sistema genera automáticamente las variantes (hover, claro, oscuro)</p>
                    </div>
                </div>
                <div class="branding-body">
                    <div class="branding-grid-2">
                        <div class="form-field">
                            <label for="br-color-picker">Color Primario</label>
                            <label class="color-picker-wrap" for="br-color-picker" id="wrap-primario">
                                <span class="color-swatch" id="swatch-primario"
                                      style="background:<?= htmlspecialchars($b['farmacia_color_primario']) ?>"></span>
                                <input type="color" id="br-color-picker" name="farmacia_color_primario"
                                       class="color-picker-input" value="<?= htmlspecialchars($b['farmacia_color_primario']) ?>">
                                <span class="color-hex-value" id="hex-primario"><?= htmlspecialchars($b['farmacia_color_primario']) ?></span>
                            </label>
                            <span class="field-hint">Botones, ítem activo del sidebar, badges, links</span>
                        </div>
                        <div class="form-field">
                            <label for="br-color-sec-picker">Color Secundario / Acento</label>
                            <label class="color-picker-wrap" for="br-color-sec-picker" id="wrap-secundario">
                                <span class="color-swatch" id="swatch-secundario"
                                      style="background:<?= htmlspecialchars($b['farmacia_color_secundario']) ?>"></span>
                                <input type="color" id="br-color-sec-picker" name="farmacia_color_secundario"
                                       class="color-picker-input" value="<?= htmlspecialchars($b['farmacia_color_secundario']) ?>">
                                <span class="color-hex-value" id="hex-secundario"><?= htmlspecialchars($b['farmacia_color_secundario']) ?></span>
                            </label>
                            <span class="field-hint">Detalles y elementos de acento</span>
                        </div>
                    </div>

                    <!-- Vista previa en vivo -->
                    <div class="preview-strip" id="preview-strip">
                        <div class="preview-strip-dot" id="preview-dot"
                             style="background:<?= htmlspecialchars($b['farmacia_color_primario']) ?>"></div>
                        <span class="preview-strip-label">Vista previa del color primario →</span>
                        <button type="button" class="preview-btn-demo" id="preview-btn-demo"
                                style="background:<?= htmlspecialchars($b['farmacia_color_primario']) ?>; color:#fff;">
                            Botón de ejemplo
                        </button>
                        <span style="font-size:0.78rem; color:var(--texto-secundario);">Los cambios se aplican al guardar.</span>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 3: Datos del Negocio ── -->
            <div class="branding-section">
                <div class="branding-section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <div>
                        <h2 class="branding-section-title">Datos del Negocio</h2>
                        <p class="branding-section-desc">Aparecen en comprobantes de venta y reportes impresos</p>
                    </div>
                </div>
                <div class="branding-body">
                    <div class="branding-grid-2">
                        <div class="form-field" style="grid-column: 1 / -1;">
                            <label for="br-direccion">Dirección</label>
                            <input type="text" id="br-direccion" name="farmacia_direccion" class="form-input"
                                   value="<?= htmlspecialchars($b['farmacia_direccion'] ?? '') ?>"
                                   placeholder="Ej: Av. Principal 123, Lima" maxlength="300">
                        </div>
                        <div class="form-field">
                            <label for="br-telefono">Teléfono de Contacto</label>
                            <input type="tel" id="br-telefono" name="farmacia_telefono" class="form-input"
                                   value="<?= htmlspecialchars($b['farmacia_telefono'] ?? '') ?>"
                                   placeholder="Ej: 01-4441234" maxlength="30">
                        </div>
                        <div class="form-field">
                            <label for="br-ruc">RUC / N° de Registro</label>
                            <input type="text" id="br-ruc" name="farmacia_ruc" class="form-input"
                                   value="<?= htmlspecialchars($b['farmacia_ruc'] ?? '') ?>"
                                   placeholder="Ej: 20123456789" maxlength="20">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Acciones ── -->
            <div class="branding-section" style="margin-bottom: 40px;">
                <div class="branding-actions">
                    <button type="submit" class="btn-branding-save" id="btn-guardar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <div class="spinner" id="spinner-guardar"></div>
                        <span id="btn-guardar-texto">Guardar Cambios</span>
                    </button>

                    <button type="button" class="btn-branding-reset" id="btn-reset">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Restablecer valores por defecto
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<!-- Toast de notificación -->
<div class="toast-branding" id="toast-branding" role="alert" aria-live="polite">
    <svg id="toast-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
    </svg>
    <span id="toast-msg">Cambios guardados correctamente</span>
</div>

<script>
// ── Color Pickers ──
function initColorPicker(pickerId, swatchId, hexId) {
    const picker = document.getElementById(pickerId);
    const swatch = document.getElementById(swatchId);
    const hex    = document.getElementById(hexId);
    if (!picker) return;

    picker.addEventListener('input', () => {
        const val = picker.value;
        swatch.style.background = val;
        hex.textContent = val;

        // Actualiza la vista previa de color primario en tiempo real
        if (pickerId === 'br-color-picker') {
            document.getElementById('preview-dot').style.background = val;
            document.getElementById('preview-btn-demo').style.background = val;
        }
    });
}

initColorPicker('br-color-picker',     'swatch-primario',   'hex-primario');
initColorPicker('br-color-sec-picker', 'swatch-secundario', 'hex-secundario');

// ── Vista previa del logo ──
const logoFile = document.getElementById('br-logo-file');
const logoUrl  = document.getElementById('br-logo-url');

function updateLogoPreview(src) {
    const box = document.getElementById('logo-preview-box');
    let img = document.getElementById('logo-preview-img-el');
    const placeholder = document.getElementById('logo-preview-placeholder');
    if (!img) {
        img = document.createElement('img');
        img.id = 'logo-preview-img-el';
        img.style.cssText = 'width:100%;height:100%;object-fit:contain;';
        box.innerHTML = '';
        box.appendChild(img);
    }
    if (placeholder) placeholder.style.display = 'none';
    img.src = src;
    img.style.display = 'block';
}

if (logoFile) {
    logoFile.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            showToast('El archivo supera los 2 MB. Elige uno más pequeño.', 'error');
            logoFile.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => updateLogoPreview(ev.target.result);
        reader.readAsDataURL(file);
    });
}

if (logoUrl) {
    let debounceTimer;
    logoUrl.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const url = logoUrl.value.trim();
            if (url) updateLogoPreview(url);
        }, 600);
    });
}

// ── Envío del formulario ──
const form      = document.getElementById('form-branding');
const btnGuardar = document.getElementById('btn-guardar');
const spinner   = document.getElementById('spinner-guardar');
const btnTexto  = document.getElementById('btn-guardar-texto');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    btnGuardar.disabled = true;
    spinner.style.display = 'block';
    btnTexto.textContent = 'Guardando...';

    const formData = new FormData(form);

    try {
        const res = await fetch('/sys-farmacia/api/branding_save.php', {
            method: 'POST',
            body: formData,
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message || 'Cambios guardados correctamente.', 'success');
            // Recargar para aplicar nuevos colores
            setTimeout(() => location.reload(), 1400);
        } else {
            showToast(data.message || 'Ocurrió un error al guardar.', 'error');
            btnGuardar.disabled = false;
            spinner.style.display = 'none';
            btnTexto.textContent = 'Guardar Cambios';
        }
    } catch (err) {
        showToast('Error de conexión. Intenta de nuevo.', 'error');
        btnGuardar.disabled = false;
        spinner.style.display = 'none';
        btnTexto.textContent = 'Guardar Cambios';
    }
});

// ── Restablecer ──
document.getElementById('btn-reset').addEventListener('click', async () => {
    if (!confirm('¿Seguro que quieres restablecer todos los valores de branding a los valores por defecto del sistema? Esto no se puede deshacer.')) return;

    try {
        const res = await fetch('/sys-farmacia/api/branding_reset.php', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            showToast('Branding restablecido.', 'success');
            setTimeout(() => location.reload(), 1400);
        } else {
            showToast(data.message || 'Error al restablecer.', 'error');
        }
    } catch {
        showToast('Error de conexión.', 'error');
    }
});

// ── Toast ──
function showToast(msg, type = 'success') {
    const toast   = document.getElementById('toast-branding');
    const toastMsg = document.getElementById('toast-msg');
    const toastIcon = document.getElementById('toast-icon');

    toastMsg.textContent = msg;
    toast.style.background = type === 'success' ? '#059669' : '#dc2626';

    const successPath = 'M4.5 12.75l6 6 9-13.5';
    const errorPath   = 'M6 18L18 6M6 6l12 12';
    toastIcon.querySelector('path').setAttribute('d', type === 'success' ? successPath : errorPath);

    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}
</script>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
