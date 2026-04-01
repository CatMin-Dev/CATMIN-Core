// Inline formatting commands that can have a queryable state
const STATEFUL_CMDS = ['bold', 'italic', 'underline', 'strikeThrough'];

// Tooltips mapping
const BUTTON_LABELS = {
	bold: 'Gras',
	italic: 'Italique',
	underline: 'Souligné',
	strikeThrough: 'Barré',
	justifyLeft: 'Aligner à gauche',
	justifyCenter: 'Centrer',
	justifyRight: 'Aligner à droite',
	justifyFull: 'Justifier',
	insertUnorderedList: 'Liste à puces',
	insertOrderedList: 'Liste numérotée',
	removeFormat: 'Effacer la mise en forme',
	undo: 'Annuler',
	redo: 'Refaire',
};

// Color utilities
function hslToHex(h, s, l) {
	l /= 100;
	const a = s * Math.min(l, 1 - l) / 100;
	const f = n => {
		const k = (n + h / 30) % 12;
		const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
		return Math.round(255 * color).toString(16).padStart(2, '0');
	};
	return `#${f(0)}${f(8)}${f(4)}`;
}

function hexToRgb(hex) {
	const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	return result ? {
		r: parseInt(result[1], 16),
		g: parseInt(result[2], 16),
		b: parseInt(result[3], 16)
	} : null;
}

function rgbToHex(r, g, b) {
	return '#' + [r, g, b].map(x => {
		const hex = x.toString(16);
		return hex.length === 1 ? '0' + hex : hex;
	}).join('').toUpperCase();
}

function rgbToHsl(r, g, b) {
	r /= 255;
	g /= 255;
	b /= 255;
	const max = Math.max(r, g, b), min = Math.min(r, g, b);
	let h, s, l = (max + min) / 2;

	if (max === min) {
		h = s = 0;
	} else {
		const d = max - min;
		s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
		switch (max) {
			case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
			case g: h = ((b - r) / d + 2) / 6; break;
			case b: h = ((r - g) / d + 4) / 6; break;
		}
	}
	return [Math.round(h * 360), Math.round(s * 100), Math.round(l * 100)];
}

function exec(command, value = null) {
	document.execCommand(command, false, value);
}

function focusCanvas(canvas) {
	if (!canvas) return;
	canvas.focus();
}

function insertHtmlAtCursor(html) {
	if (!html) return;
	exec('insertHTML', html);
}

function syncCanvasToInput(canvas, input) {
	if (!canvas || !input) return;
	input.value = canvas.innerHTML.trim();
}

function clearToolbarState(root) {
	root.querySelectorAll('[data-editor-cmd]').forEach((el) => {
		if (el.tagName !== 'SELECT') {
			el.classList.remove('active');
		}
	});
}

function updateToolbarState(root) {
	clearToolbarState(root);
	STATEFUL_CMDS.forEach((cmd) => {
		if (document.queryCommandState(cmd)) {
			const btn = root.querySelector(`[data-editor-cmd="${cmd}"]`);
			if (btn) {
				btn.classList.add('active');
			}
		}
	});
}

function showLinkPopup(root, savedRange) {
	const popup = root.querySelector('[data-editor-link-popup]');
	const urlInput = root.querySelector('[data-editor-link-input]');
	if (!popup || !urlInput) return;
	urlInput.value = '';
	popup.hidden = false;
	urlInput.focus();
	popup._savedRange = savedRange;
}

function hideLinkPopup(root) {
	const popup = root.querySelector('[data-editor-link-popup]');
	if (popup) {
		popup.hidden = true;
		popup._savedRange = null;
	}
}

// Professional Color Picker
function initColorPicker(root, cmd) {
	const button = root.querySelector(`[data-editor-action="color-picker"][data-editor-color-cmd="${cmd}"]`);
	const pickerContainer = root.querySelector(`[data-editor-color-picker="${cmd}"]`);
	const canvas = root.querySelector('[data-editor-canvas]');
	const input = root.querySelector('[data-editor-source]');

	if (!button || !pickerContainer) return;

	pickerContainer.innerHTML = `
		<div class="d-flex flex-column gap-3 p-3">
			<!-- Gradient selector -->
			<div>
				<label class="small text-muted d-block mb-2">Teinte & Saturation</label>
				<div class="position-relative" style="height: 180px; background: linear-gradient(to right, white 0%, hsl(0, 100%, 50%) 100%); border-radius: 0.25rem; border: 1px solid #dee3eb; cursor: crosshair;" data-color-gradient>
					<div class="position-absolute" style="width: 12px; height: 12px; background: white; border: 2px solid #333; border-radius: 50%; transform: translate(-50%, -50%); pointer-events: none;" data-color-selector></div>
				</div>
			</div>

			<!-- Hue slider -->
			<div>
				<label class="small text-muted d-block mb-2">Teinte</label>
				<input type="range" min="0" max="360" value="0" class="form-range" data-color-hue>
			</div>

			<!-- Luminance slider -->
			<div>
				<label class="small text-muted d-block mb-2">Luminosité</label>
				<input type="range" min="0" max="100" value="50" class="form-range" data-color-luminance>
			</div>

			<!-- Color inputs -->
			<div class="d-grid grid-cols-3 gap-2">
				<div>
					<label class="small text-muted">HEX</label>
					<input type="text" class="form-control form-control-sm" placeholder="#000000" maxlength="7" data-color-hex>
				</div>
				<div>
					<label class="small text-muted">RGB</label>
					<input type="text" class="form-control form-control-sm" placeholder="0,0,0" data-color-rgb>
				</div>
				<div>
					<label class="small text-muted">HSL</label>
					<input type="text" class="form-control form-control-sm" placeholder="0,0,0" data-color-hsl>
				</div>
			</div>

			<!-- Color swatches -->
			<div class="d-flex flex-wrap gap-2">
				<div class="bg-primary" style="width: 30px; height: 30px; border-radius: 0.25rem; cursor: pointer; border: 1px solid #dee3eb;" tabindex="0" data-swatch="#0d6efd"></div>
				<div class="bg-success" style="width: 30px; height: 30px; border-radius: 0.25rem; cursor: pointer; border: 1px solid #dee3eb;" tabindex="0" data-swatch="#198754"></div>
				<div class="bg-warning" style="width: 30px; height: 30px; border-radius: 0.25rem; cursor: pointer; border: 1px solid #dee3eb;" tabindex="0" data-swatch="#ffc107"></div>
				<div class="bg-danger" style="width: 30px; height: 30px; border-radius: 0.25rem; cursor: pointer; border: 1px solid #dee3eb;" tabindex="0" data-swatch="#dc3545"></div>
				<div class="bg-info" style="width: 30px; height: 30px; border-radius: 0.25rem; cursor: pointer; border: 1px solid #dee3eb;" tabindex="0" data-swatch="#0dcaf0"></div>
				<div class="bg-dark" style="width: 30px; height: 30px; border-radius: 0.25rem; cursor: pointer; border: 1px solid #dee3eb;" tabindex="0" data-swatch="#212529"></div>
			</div>

			<!-- Preview -->
			<div class="d-flex gap-2 align-items-center">
				<div style="width: 40px; height: 40px; background: #000; border-radius: 0.25rem; border: 2px solid #dee3eb;" data-color-preview></div>
				<button type="button" class="btn btn-sm btn-primary flex-grow-1" data-color-apply>Appliquer</button>
			</div>
		</div>
	`;

	const gradient = pickerContainer.querySelector('[data-color-gradient]');
	const selector = pickerContainer.querySelector('[data-color-selector]');
	const hueSlider = pickerContainer.querySelector('[data-color-hue]');
	const lumSlider = pickerContainer.querySelector('[data-color-luminance]');
	const hexInput = pickerContainer.querySelector('[data-color-hex]');
	const rgbInput = pickerContainer.querySelector('[data-color-rgb]');
	const hslInput = pickerContainer.querySelector('[data-color-hsl]');
	const preview = pickerContainer.querySelector('[data-color-preview]');
	const applyBtn = pickerContainer.querySelector('[data-color-apply]');
	const swatches = pickerContainer.querySelectorAll('[data-swatch]');

	let currentHue = 0;
	let currentSat = 100;
	let currentLum = 50;

	const updatePreview = () => {
		const hex = hslToHex(currentHue, currentSat, currentLum);
		preview.style.background = hex;
		hexInput.value = hex;
		const rgb = hexToRgb(hex);
		rgbInput.value = `${rgb.r},${rgb.g},${rgb.b}`;
		const [h, s, l] = rgbToHsl(rgb.r, rgb.g, rgb.b);
		hslInput.value = `${h},${s},${l}`;
	};

	gradient.addEventListener('click', (e) => {
		const rect = gradient.getBoundingClientRect();
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;
		currentSat = Math.max(0, Math.min(100, (x / rect.width) * 100));
		currentLum = Math.max(0, Math.min(100, 100 - (y / rect.height) * 100));
		selector.style.left = x + 'px';
		selector.style.top = y + 'px';
		updatePreview();
	});

	hueSlider.addEventListener('input', (e) => {
		currentHue = parseInt(e.target.value);
		gradient.style.background = `linear-gradient(to right, white 0%, hsl(${currentHue}, 100%, 50%) 100%)`;
		updatePreview();
	});

	lumSlider.addEventListener('input', (e) => {
		currentLum = parseInt(e.target.value);
		updatePreview();
	});

	hexInput.addEventListener('input', (e) => {
		if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
			const rgb = hexToRgb(e.target.value);
			const [h, s, l] = rgbToHsl(rgb.r, rgb.g, rgb.b);
			currentHue = h;
			currentSat = s;
			currentLum = l;
			hueSlider.value = currentHue;
			lumSlider.value = currentLum;
			updatePreview();
		}
	});

	swatches.forEach(swatch => {
		swatch.addEventListener('click', () => {
			hexInput.value = swatch.dataset.swatch;
			hexInput.dispatchEvent(new Event('input'));
		});
	});

	applyBtn.addEventListener('click', () => {
		const color = hexInput.value;
		focusCanvas(canvas);
		exec(cmd, color);
		syncCanvasToInput(canvas, input);
		pickerContainer.hidden = true;
	});

	button.addEventListener('click', (e) => {
		e.preventDefault();
		pickerContainer.hidden = !pickerContainer.hidden;
		if (!pickerContainer.hidden) {
			updatePreview();
		}
	});
}

function initEditorInstance(root) {
	const canvas = root.querySelector('[data-editor-canvas]');
	const input = root.querySelector('[data-editor-source]');
	const panel = root.querySelector('[data-editor-panel]');
	const previewPane = root.querySelector('[data-editor-preview-pane]');
	const previewCanvas = root.querySelector('[data-editor-preview-canvas]');

	if (!canvas || !input) return;

	const form = root.closest('form');
	let mediaModal = null;
	let wantsMediaInsert = false;

	// Add tooltips to toolbar buttons
	root.querySelectorAll('[data-editor-cmd], [data-editor-action]').forEach((btn) => {
		const cmd = btn.dataset.editorCmd || btn.dataset.editorAction;
		const label = BUTTON_LABELS[cmd];
		if (label) {
			btn.title = label;
			if (!btn.getAttribute('data-bs-toggle')) {
				btn.setAttribute('data-bs-toggle', 'tooltip');
				btn.setAttribute('data-bs-placement', 'bottom');
			}
		}
	});

	// Initialize Bootstrap tooltips
	const tooltipTriggerList = [].slice.call(root.querySelectorAll('[data-bs-toggle="tooltip"]'));
	tooltipTriggerList.map(tooltipTriggerEl => {
		if (window.bootstrap?.Tooltip) {
			return new window.bootstrap.Tooltip(tooltipTriggerEl);
		}
	});

	// Toolbar commands
	root.querySelectorAll('[data-editor-cmd]').forEach((button) => {
		if (button.tagName === 'SELECT') {
			button.addEventListener('change', () => {
				focusCanvas(canvas);
				exec(button.dataset.editorCmd, `<${button.value}>`);
				syncCanvasToInput(canvas, input);
				button.selectedIndex = 0;
				clearToolbarState(root);
			});
			return;
		}

		button.addEventListener('click', () => {
			focusCanvas(canvas);
			const cmd = button.dataset.editorCmd;
			const val = button.dataset.editorValue;
			if (cmd === 'formatBlock' && val) {
				exec(cmd, `<${val}>`);
			} else {
				exec(cmd, val || null);
			}
			syncCanvasToInput(canvas, input);
			updateToolbarState(root);
		});
	});

	// Link action
	root.querySelectorAll('[data-editor-action="link"]').forEach((button) => {
		button.addEventListener('click', () => {
			const sel = window.getSelection();
			let savedRange = null;
			if (sel && sel.rangeCount > 0) {
				savedRange = sel.getRangeAt(0).cloneRange();
			}
			showLinkPopup(root, savedRange);
		});
	});

	const linkApplyBtn = root.querySelector('[data-editor-link-apply]');
	const linkCancelBtn = root.querySelector('[data-editor-link-cancel]');
	const linkInput = root.querySelector('[data-editor-link-input]');

	if (linkApplyBtn && linkInput) {
		const applyLink = () => {
			const popup = root.querySelector('[data-editor-link-popup]');
			const url = linkInput.value.trim();
			if (!url) {
				hideLinkPopup(root);
				return;
			}
			const savedRange = popup?._savedRange;
			if (savedRange) {
				const sel = window.getSelection();
				sel.removeAllRanges();
				sel.addRange(savedRange);
			}
			focusCanvas(canvas);
			exec('createLink', url);
			syncCanvasToInput(canvas, input);
			hideLinkPopup(root);
		};

		linkApplyBtn.addEventListener('click', applyLink);
		linkInput.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') {
				e.preventDefault();
				applyLink();
			}
			if (e.key === 'Escape') {
				hideLinkPopup(root);
			}
		});
	}

	if (linkCancelBtn) {
		linkCancelBtn.addEventListener('click', () => hideLinkPopup(root));
	}

	// Panel toggle
	root.querySelectorAll('[data-editor-action="toggle-panel"]').forEach((button) => {
		button.addEventListener('click', () => {
			if (!panel) return;
			panel.hidden = !panel.hidden;
			root.classList.toggle('catmin-editor--panel-open', !panel.hidden);
		});
	});

	// Panel tabs (Snippets / Blocs)
	const tabButtons = root.querySelectorAll('[data-editor-tab]');
	const tabPanes = root.querySelectorAll('[data-editor-pane]');
	if (tabButtons.length && tabPanes.length) {
		tabButtons.forEach((button) => {
			button.addEventListener('click', () => {
				const target = button.dataset.editorTab;
				if (!target) return;

				tabButtons.forEach((btn) => btn.classList.remove('active'));
				button.classList.add('active');

				tabPanes.forEach((pane) => {
					pane.hidden = pane.dataset.editorPane !== target;
				});
			});
		});
	}

	// Color pickers (both foreColor and hiliteColor)
	initColorPicker(root, 'foreColor');
	initColorPicker(root, 'hiliteColor');

	// Live preview - show in direct, real-time rendering
	const updateLivePreview = () => {
		if (previewCanvas) {
			previewCanvas.innerHTML = canvas.innerHTML || '<p style="color:#ccc; text-align: center; padding: 2rem;">Rendu en direct</p>';
		}
	};

	canvas.addEventListener('input', updateLivePreview);
	canvas.addEventListener('blur', updateLivePreview);
	form?.addEventListener('submit', () => syncCanvasToInput(canvas, input));

	// Toggle preview button - show live preview side-by-side
	root.querySelectorAll('[data-editor-action="toggle-preview"]').forEach((button) => {
		button.addEventListener('click', () => {
			if (previewPane) {
				previewPane.hidden = !previewPane.hidden;
				if (!previewPane.hidden) {
					updateLivePreview();
				}
			}
		});
	});

	// Insert HTML
	root.querySelectorAll('[data-editor-action="insert-html"]').forEach((button) => {
		button.addEventListener('click', () => {
			focusCanvas(canvas);
			insertHtmlAtCursor(button.dataset.editorHtml || '');
			syncCanvasToInput(canvas, input);
			updateLivePreview();
		});
	});

	// Media picker
	root.querySelectorAll('[data-editor-action="media-picker"]').forEach((button) => {
		button.addEventListener('click', () => {
			const modalElement = document.getElementById('catmin-media-picker-modal');
			if (!modalElement || !window.bootstrap?.Modal) {
				window.alert('Le picker media est indisponible sur cette page.');
				return;
			}

			wantsMediaInsert = true;
			mediaModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
			mediaModal.show();
		});
	});

	// Toolbar state update on selection change
	document.addEventListener('selectionchange', () => {
		if (canvas.contains(document.activeElement) || document.activeElement === canvas) {
			updateToolbarState(root);
		}
	});

	canvas.addEventListener('keyup', () => updateToolbarState(root));
	canvas.addEventListener('mouseup', () => updateToolbarState(root));

	// Sync on input/blur
	canvas.addEventListener('input', () => syncCanvasToInput(canvas, input));
	canvas.addEventListener('blur', () => syncCanvasToInput(canvas, input));

	// Media selected event
	window.addEventListener('catmin:media-selected', (event) => {
		if (!wantsMediaInsert) return;

		const media = event.detail || {};
		const previewUrl = media.preview_url || '';
		const fallbackLabel = media.original_name || 'media';

		focusCanvas(canvas);
		if (previewUrl) {
			insertHtmlAtCursor(`<img src="${previewUrl}" alt="${fallbackLabel}">`);
		} else {
			insertHtmlAtCursor(`<a href="#">${fallbackLabel}</a>`);
		}
		syncCanvasToInput(canvas, input);
		updateLivePreview();

		wantsMediaInsert = false;
		mediaModal?.hide();
	});

	syncCanvasToInput(canvas, input);
	updateLivePreview();
}

export function initCatminEditor() {
	const fields = document.querySelectorAll('[data-catmin-editor-field][data-enabled="1"]');
	if (!fields.length) return;

	fields.forEach((field) => initEditorInstance(field));
}
