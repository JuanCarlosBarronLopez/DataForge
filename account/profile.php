<?php
/**
 * Profile Page — User Account Management
 *
 * @package DataForge
 * @module  Account
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/auth_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin();

// Auto-parche local en caso de que la sesión viniera viva desde la v3.0
installSystemDb();

$user   = getUserById((int) getCurrentUser()['id']); // Fresh from DB
if (!$user) {
    $user = getCurrentUser(); // Fallback de seguridad al caché de sesión si falla DB
}
$themes = AVAILABLE_THEMES;

$pageTitle = 'Mi Identidad';
$baseUrl   = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Formulario Maestro -->
<form method="POST" action="update_profile.php" enctype="multipart/form-data" id="profileForm">
    <?php csrfField(); ?>

    <!-- Profile Hero -->
    <section class="glass-card" style="margin-bottom:1.5rem">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap">
            <div style="position:relative; width: 80px; height: 80px;">
                <div class="profile-avatar" style="width:100%; height:100%; <?php if(!empty($user['profile_pic'])) echo 'background-image: url(\'../uploads/avatars/'.$user['profile_pic'].'\');'; ?>">
                    <?php if(empty($user['profile_pic'])) echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <label for="profilePic" style="cursor:pointer;position:absolute;bottom:-4px;right:-4px;background:var(--bg-primary);border-radius:50%;padding:4px;border:1px solid var(--border-color)">
                    <span class="badge badge-accent" style="border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;padding:0">📷</span>
                </label>
                <input type="file" id="profilePic" name="profile_pic" accept="image/*" style="display:none;" onchange="previewImage(this)">
            </div>
            <div style="flex:1; min-width:200px;">
                <h2 style="font-size:1.5rem;font-weight:800;letter-spacing:-0.025em;margin-bottom:0.4rem">
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-control" style="background:transparent;border:none;font-weight:inherit;font-size:inherit;color:var(--text-primary);padding:0;letter-spacing:inherit;border-bottom:1px dashed var(--border-color)">
                </h2>
                <div style="color:var(--text-secondary);font-size:0.95rem;display:flex;align-items:center;margin-bottom:0.5rem">
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-control" style="background:transparent;border:none;color:inherit;padding:0;width:100%;max-width:300px;border-bottom:1px dashed var(--border-color)">
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
                    <span class="badge badge-info">Miembro desde <?= date('M Y', strtotime($user['created_at'])) ?></span>
                    <span class="badge badge-accent" style="opacity:0.8">Último acceso: <?= $user['last_login'] ? date('d/m H:i', strtotime($user['last_login'])) : 'Hoy' ?></span>
                </div>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; border-radius: 100px;">
                    <span class="btn-icon">💾</span> Guardar Cambios
                </button>
            </div>
        </div>
    </section>

    <!-- Theme & Mode Switcher -->
    <section class="glass-card" id="themes" style="margin-bottom:1.5rem">
        <div class="section-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap: 1rem;">
            <div>
                <h2><span class="gradient-text">Aspecto Visual</span></h2>
                <p class="hint-text">Estiliza tu herramienta. Personaliza tu industria y la iluminación.</p>
            </div>
            
            <!-- Light / Dark Toggle -->
            <div class="mode-toggle-wrapper">
                <input type="hidden" name="color_mode" id="colorModeInput" value="<?= htmlspecialchars($user['color_mode'] ?? 'dark') ?>">
                <button type="button" class="mode-toggle-btn <?= ($user['color_mode'] ?? 'dark') === 'light' ? 'active' : '' ?>" onclick="switchMode('light')">☀️ Claro</button>
                <button type="button" class="mode-toggle-btn <?= (!isset($user['color_mode']) || $user['color_mode'] === 'dark') ? 'active' : '' ?>" onclick="switchMode('dark')">🌙 Oscuro</button>
            </div>
        </div>

        <p style="font-size:0.85rem;font-weight:600;margin-bottom:1rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;">Paleta de Industria</p>
        
        <input type="hidden" name="theme" id="themeInput" value="<?= htmlspecialchars($user['theme']) ?>">

        <div class="theme-grid">
            <?php foreach ($themes as $themeId => $theme): ?>
                <div class="theme-card preview-<?= $themeId ?> <?= $themeId === ($user['theme'] ?? 'neutral') ? 'selected' : '' ?>"
                     id="switcher-<?= $themeId ?>"
                     data-theme="<?= $themeId ?>"
                     onclick="switchTheme('<?= $themeId ?>')"
                     onmouseenter="previewThemeOnHover('<?= $themeId ?>')"
                     onmouseleave="restoreThemeOnMouseOut()"
                     role="button"
                     tabindex="0"
                     aria-pressed="<?= $themeId === ($user['theme'] ?? 'neutral') ? 'true' : 'false' ?>">
                    
                    <div class="theme-preview">
                        <div class="theme-preview-bar">
                            <div class="theme-preview-dot"></div>
                            <div class="theme-preview-dot"></div>
                            <div class="theme-preview-dot"></div>
                        </div>
                        <div class="theme-preview-content">
                            <div class="theme-preview-line"></div>
                            <div class="theme-preview-line"></div>
                        </div>
                    </div>

                    <span class="theme-icon"><?= $theme['icon'] ?></span>
                    <div class="theme-name"><?= htmlspecialchars($theme['name']) ?></div>
                    <div class="theme-industry"><?= htmlspecialchars($theme['industry']) ?></div>
                    <p class="theme-desc"><?= htmlspecialchars($theme['desc'] ?? '') ?></p>
                    
                    <div class="theme-check" style="<?= $themeId === ($user['theme'] ?? 'neutral') ? 'opacity:1; transform:scale(1);' : 'opacity:0; transform:scale(0.5);' ?>">✓</div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</form>

<!-- Danger Zone -->
<section class="glass-card" style="border-color:rgba(239,68,68,0.25)">
    <h3 style="font-size:.875rem;font-weight:800;color:var(--color-danger);margin-bottom:1rem;
               text-transform:uppercase;letter-spacing:.05em">Zona de peligro</h3>
    
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px dashed var(--border-color)">
        <div>
            <p style="font-size:.95rem;font-weight:600;color:var(--text-primary)">Cerrar sesión</p>
            <p style="font-size:.85rem;color:var(--text-muted)">Cierra tu sesión en este dispositivo de forma segura.</p>
        </div>
        <a href="../auth/logout.php" onclick="return confirm('¿Seguro que deseas cerrar la sesión?')" class="btn btn-ghost" style="color:var(--color-warning);border-color:rgba(245, 158, 11, 0.2)">Cerrar sesión</a>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem">
        <div>
            <p style="font-size:.95rem;font-weight:600;color:var(--text-primary)">Eliminar Cuenta Definitivamente</p>
            <p style="font-size:.85rem;color:var(--text-muted)">Se borrará tu perfil, configuración, foto y registro de actividad eternamente. (Las bases de datos MySQL en tu servidor NO se borrarán).</p>
        </div>
        <form method="POST" action="delete_account.php" style="margin:0">
            <?php csrfField(); ?>
            <button type="submit" onclick="return confirm('¡CUIDADO! Esta acción no se puede deshacer. ¿Deseas destruir permanentemente tu perfil?')" class="btn btn-danger">Eliminar Mi Cuenta</button>
        </form>
    </div>
</section>

<script>
let selectedTheme = '<?= htmlspecialchars($user['theme'] ?? 'neutral') ?>';
let currentMode = '<?= htmlspecialchars($user['color_mode'] ?? 'dark') ?>';

// Live Image Preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            const avatar = document.querySelector('.profile-avatar');
            avatar.style.backgroundImage = "url('" + e.target.result + "')";
            avatar.innerHTML = ''; // Elimina la letra inicial
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function switchTheme(themeId) {
    // Deselect previous
    const prev = document.getElementById('switcher-' + selectedTheme);
    if (prev) {
        prev.classList.remove('selected');
        prev.setAttribute('aria-pressed', 'false');
        const check = prev.querySelector('.theme-check');
        if (check) {
            check.style.opacity = '0';
            check.style.transform = 'scale(0.5)';
        }
    }

    // Select new
    selectedTheme = themeId;
    const card = document.getElementById('switcher-' + themeId);
    if (card) {
        card.classList.add('selected');
        card.setAttribute('aria-pressed', 'true');
        const check = card.querySelector('.theme-check');
        if (check) {
            check.style.opacity = '1';
            check.style.transform = 'scale(1)';
        }
    }

    document.getElementById('themeInput').value = themeId;
    updateBodyClass();
}

function switchMode(mode) {
    currentMode = mode;
    document.getElementById('colorModeInput').value = mode;
    
    // Toggle Button UI
    const btns = document.querySelectorAll('.mode-toggle-btn');
    btns.forEach(b => b.classList.remove('active'));
    event.currentTarget.classList.add('active');

    updateBodyClass();
}

function updateBodyClass() {
    let newClass = 'theme-' + selectedTheme;
    if (currentMode === 'light') {
        newClass += ' mode-light';
    }
    document.body.className = newClass;
}

function previewThemeOnHover(themeId) {
    let newClass = 'theme-' + themeId;
    if (currentMode === 'light') {
        newClass += ' mode-light';
    }
    document.body.className = newClass;
}

function restoreThemeOnMouseOut() {
    updateBodyClass(); // Restaura al tema guardado en selectedTheme
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
