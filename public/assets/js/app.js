/**
 * REMTYSAPP - JavaScript Principal
 * Vanilla JS, Bootstrap 5
 */

document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const btnToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const topbar = document.querySelector('.topbar');

    function toggleSidebar(forceOpen) {
        if (!sidebar) return;
        const shouldOpen = typeof forceOpen === 'boolean'
            ? forceOpen
            : !sidebar.classList.contains('open');

        sidebar.classList.toggle('open', shouldOpen);
        if (overlay) overlay.classList.toggle('show', shouldOpen);
        body.classList.toggle('sidebar-open', shouldOpen);
    }

    if (btnToggle && sidebar) {
        btnToggle.addEventListener('click', function () {
            toggleSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            toggleSidebar(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            toggleSidebar(false);
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            toggleSidebar(false);
        }
    });

    function syncTopbarState() {
        if (!topbar) return;
        topbar.classList.toggle('is-scrolled', window.scrollY > 12);
    }

    syncTopbarState();
    window.addEventListener('scroll', syncTopbarState, { passive: true });

    const currentUrl = window.location.search;
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && currentUrl.includes(href.split('?')[1] || '')) {
            const params = new URLSearchParams(currentUrl);
            const linkParams = new URLSearchParams(href.split('?')[1] || '');
            const modMatch = params.get('modulo') === linkParams.get('modulo');
            const actMatch = params.get('accion') === linkParams.get('accion');
            if (modMatch && actMatch) {
                link.classList.add('active');
            }
        }
    });

    setTimeout(function () {
        document.querySelectorAll('.alert.auto-dismiss').forEach(function (el) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 5000);

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm') || '¿Esta seguro de continuar?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[data-confirm-submit]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const msg = form.getAttribute('data-confirm-submit') || '¿Desea continuar?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-print]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });

    document.querySelectorAll('[data-submit-on-change]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (el.form) el.form.submit();
        });
    });

    document.querySelectorAll('.upload-zone').forEach(function (zone) {
        var input = zone.querySelector('input[type="file"]');
        var emptyState = zone.querySelector('.upload-zone-empty');
        var fileState = zone.querySelector('.upload-zone-file');
        var changeBtn = zone.querySelector('.upload-zone-change');

        // Max file size in bytes (20 MB)
        var MAX_FILE_SIZE = 20 * 1024 * 1024;
        // Allowed extensions
        var ALLOWED_EXTS = ['pdf', 'jpg', 'jpeg', 'png'];

        // --- Drag events ---
        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('upload-zone--dragover');
        });

        zone.addEventListener('dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('upload-zone--dragover');
        });

        zone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('upload-zone--dragover');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('upload-zone--dragover');
            if (input && e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                actualizarEstadoUpload(zone, input);
            }
        });

        // --- Click to select (only when in empty state or on the zone background) ---
        zone.addEventListener('click', function (e) {
            // Prevent triggering file dialog when clicking "Cambiar archivo" link
            if (e.target.closest('.upload-zone-change')) return;
            if (input) input.click();
        });

        // --- File input change ---
        if (input) {
            input.addEventListener('change', function () {
                actualizarEstadoUpload(zone, input);
            });
        }

        // --- "Cambiar archivo" button ---
        if (changeBtn) {
            changeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (input) input.click();
            });
        }
    });

    /**
     * Format file size to human-readable string
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        if (bytes < 1024) return bytes + ' Bytes';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    /**
     * Get file extension from filename
     */
    function getFileExtension(filename) {
        var parts = filename.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    /**
     * Update the upload zone visual state based on file selection
     */
    function mostrarErrorUpload(zone, mensaje) {
        var emptyState = zone.querySelector('.upload-zone-empty');
        var fileState = zone.querySelector('.upload-zone-file');
        var errorState = zone.querySelector('.upload-zone-error');
        var errorDetail = zone.querySelector('.upload-zone-error-detail');

        zone.classList.add('upload-zone--error');
        if (emptyState) emptyState.classList.add('d-none');
        if (fileState) fileState.classList.add('d-none');
        if (errorState) errorState.classList.remove('d-none');
        if (errorDetail) errorDetail.textContent = mensaje;
    }

    function actualizarEstadoUpload(zone, input) {
        var emptyState = zone.querySelector('.upload-zone-empty');
        var fileState = zone.querySelector('.upload-zone-file');
        var errorState = zone.querySelector('.upload-zone-error');

        // Reset all states
        zone.classList.remove('upload-zone--has-file', 'upload-zone--error', 'upload-zone--dragover');
        if (emptyState) emptyState.classList.remove('d-none');
        if (fileState) fileState.classList.add('d-none');
        if (errorState) errorState.classList.add('d-none');

        if (!input.files || input.files.length === 0) {
            return;
        }

        var file = input.files[0];
        var ext = getFileExtension(file.name);
        var maxSize = 20 * 1024 * 1024;

        // Try to read max size from docInfo if available
        var docMaxEl = document.getElementById('docMax');
        if (docMaxEl && docMaxEl.textContent) {
            var parsedMax = parseInt(docMaxEl.textContent, 10);
            if (!isNaN(parsedMax) && parsedMax > 0) {
                maxSize = parsedMax * 1024 * 1024;
            }
        }

        // Try to read allowed exts from docInfo if available
        var allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        var docExtsEl = document.getElementById('docExts');
        if (docExtsEl && docExtsEl.textContent && docExtsEl.textContent.trim()) {
            allowedExts = docExtsEl.textContent.toLowerCase().split(',').map(function(e) {
                return e.trim().replace('.', '');
            });
            if (allowedExts.indexOf('jpg') !== -1 && allowedExts.indexOf('jpeg') === -1) {
                allowedExts.push('jpeg');
            }
        }

        // Validate file extension
        if (allowedExts.length > 0 && allowedExts.indexOf(ext) === -1) {
            var formatosLegibles = allowedExts.filter(function(e) { return e !== 'jpeg'; })
                .map(function(e) { return e.toUpperCase(); }).join(', ');
            input.value = '';
            mostrarErrorUpload(zone, 'El archivo "' + file.name + '" no es un formato permitido. Debe seleccionar un archivo en formato: ' + formatosLegibles + '.');
            return;
        }

        // Validate file size
        if (file.size > maxSize) {
            input.value = '';
            mostrarErrorUpload(zone, 'El archivo "' + file.name + '" pesa ' + formatFileSize(file.size) + ' y excede el limite de ' + formatFileSize(maxSize) + '. Seleccione un archivo mas pequeno.');
            return;
        }

        // Valid file: show file state
        if (emptyState) emptyState.classList.add('d-none');
        zone.classList.add('upload-zone--has-file');
        if (fileState) fileState.classList.remove('d-none');

        var nameEl = fileState.querySelector('.upload-zone-file-name');
        var sizeEl = fileState.querySelector('.upload-zone-file-size');
        var iconEl = fileState.querySelector('.upload-zone-file-icon');

        if (nameEl) nameEl.textContent = file.name;
        if (sizeEl) sizeEl.textContent = formatFileSize(file.size);

        if (iconEl) {
            iconEl.className = 'upload-zone-file-icon bi';
            if (ext === 'pdf') {
                iconEl.classList.add('bi-file-earmark-pdf-fill');
            } else if (ext === 'jpg' || ext === 'jpeg' || ext === 'png') {
                iconEl.classList.add('bi-file-earmark-image-fill');
            } else {
                iconEl.classList.add('bi-file-earmark-check-fill');
            }
        }
    }

    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    document.querySelectorAll('input[data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const target = document.getElementById(input.getAttribute('data-preview'));
            if (!target || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                target.src = e.target.result;
                target.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        });
    });

    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-toggle-password');
            const input = document.getElementById(targetId);
            if (!input) return;
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });

    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const passwordStrengthFill = document.querySelector('[data-password-strength-fill]');
    const passwordStrengthLabel = document.querySelector('[data-password-strength-label]');
    const passwordRuleItems = document.querySelectorAll('[data-password-rule]');

    function obtenerEstadoPassword(password) {
        return {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[^A-Za-z\d]/.test(password),
        };
    }

    function actualizarConfirmacionPassword() {
        if (!passwordInput || !passwordConfirmInput) return;

        if (passwordConfirmInput.value !== '' && passwordConfirmInput.value !== passwordInput.value) {
            passwordConfirmInput.setCustomValidity('Las contrasenas no coinciden.');
        } else {
            passwordConfirmInput.setCustomValidity('');
        }
    }

    function actualizarIndicadoresPassword() {
        if (!passwordInput || !passwordRuleItems.length) return;

        const estado = obtenerEstadoPassword(passwordInput.value);
        const aprobadas = Object.values(estado).filter(Boolean).length;
        const porcentaje = Math.round((aprobadas / 5) * 100);
        let nivel = 'empty';
        let etiqueta = 'Use una contrasena robusta para proteger su cuenta.';

        if (passwordInput.value.length > 0 && aprobadas <= 2) {
            nivel = 'weak';
            etiqueta = 'La contrasena aun no cumple la politica de seguridad.';
        } else if (aprobadas === 3 || aprobadas === 4) {
            nivel = 'medium';
            etiqueta = 'Va bien, pero aun puede reforzar la contrasena.';
        } else if (aprobadas === 5) {
            nivel = 'strong';
            etiqueta = 'Contrasena segura para continuar con el registro.';
        }

        passwordRuleItems.forEach(function (item) {
            const regla = item.getAttribute('data-password-rule');
            const cumplida = Boolean(estado[regla]);
            item.classList.toggle('is-valid', cumplida);
            item.classList.toggle('is-invalid', passwordInput.value.length > 0 && !cumplida);
        });

        if (passwordStrengthFill) {
            passwordStrengthFill.style.width = porcentaje + '%';
            passwordStrengthFill.setAttribute('data-strength', nivel);
        }

        if (passwordStrengthLabel) {
            passwordStrengthLabel.textContent = etiqueta;
        }

        actualizarConfirmacionPassword();
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', actualizarIndicadoresPassword);
        passwordInput.addEventListener('blur', actualizarIndicadoresPassword);
        actualizarIndicadoresPassword();
    }

    if (passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', actualizarConfirmacionPassword);
        passwordConfirmInput.addEventListener('blur', actualizarConfirmacionPassword);
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        const tableId = input.getAttribute('data-table-search');
        const table = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('keyup', function () {
            const term = input.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });

    const tipoPersonaSelect = document.getElementById('tipo_persona');
    if (tipoPersonaSelect) {
        function toggleCamposPersona() {
            const tipo = tipoPersonaSelect.value;
            const camposFisica = document.querySelectorAll('.campo-fisica');
            const camposMoral = document.querySelectorAll('.campo-moral');
            camposFisica.forEach(function (el) {
                el.style.display = tipo === 'fisica' ? '' : 'none';
                el.querySelectorAll('input,select').forEach(function (i) {
                    i.required = (tipo === 'fisica' && i.dataset.requiredFisica === 'true');
                });
            });
            camposMoral.forEach(function (el) {
                el.style.display = tipo === 'moral' ? '' : 'none';
                el.querySelectorAll('input,select').forEach(function (i) {
                    i.required = (tipo === 'moral' && i.dataset.requiredMoral === 'true');
                });
            });
        }
        tipoPersonaSelect.addEventListener('change', toggleCamposPersona);
        toggleCamposPersona();
    }

    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        let seconds = parseInt(countdownEl.textContent, 10);
        const redirectUrl = countdownEl.getAttribute('data-redirect');
        const timer = setInterval(function () {
            seconds -= 1;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                if (redirectUrl) window.location.href = redirectUrl;
            }
        }, 1000);
    }

    document.querySelectorAll('input[data-format="currency"]').forEach(function (input) {
        input.addEventListener('blur', function () {
            const val = parseFloat(input.value.replace(/[^0-9.]/g, ''));
            if (!isNaN(val)) {
                input.value = val.toFixed(2);
            }
        });
    });

    body.classList.add('app-ready');
});
