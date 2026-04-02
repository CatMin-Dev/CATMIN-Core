// Inline formatting commands that can have a queryable state
const STATEFUL_CMDS = ['bold', 'italic', 'underline', 'strikeThrough'];
const CONTEXT_MENU_ENABLED = false;

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
	link: 'Insérer un lien',
	'modal-link': 'Lier à une modal',
	'insert-bookmarks': 'Insérer signets flottants',
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

function insertHtmlWithBreak(html) {
	if (!html) return;
	const markerId = 'catmin-cur-' + Date.now();
	insertHtmlAtCursor(`${html}<span id="${markerId}"></span>`);
	const marker = canvas.querySelector('#' + markerId);
	if (marker) {
		const sel = window.getSelection();
		if (sel) {
			const range = document.createRange();
			range.setStartAfter(marker);
			range.collapse(true);
			sel.removeAllRanges();
			sel.addRange(range);
		}
		marker.remove();
	}
}

function escapeAttribute(value) {
	return String(value || '')
		.replace(/&/g, '&amp;')
		.replace(/"/g, '&quot;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

function wrapHtmlWithCss(html, css) {
	const cssValue = String(css || '').trim();
	if (!cssValue) {
		return html;
	}

	return `<div style="${escapeAttribute(cssValue)}">${html}</div>`;
}

function syncCanvasToInput(canvas, input) {
	if (!canvas || !input) return;
	input.value = canvas.innerHTML.trim();
}

function decodeHtmlEntities(value) {
	const el = document.createElement('textarea');
	el.innerHTML = value;
	return el.value;
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
		<div class="p-2" style="width: 310px;">
			<div class="position-relative mb-2" style="height: 160px; border-radius: 12px; overflow: hidden; border: 1px solid #d1d5db; background: #f59e0b; cursor: crosshair;" data-color-gradient>
				<div style="position:absolute; inset:0; background: linear-gradient(to right, #fff 0%, rgba(255,255,255,0) 100%);"></div>
				<div style="position:absolute; inset:0; background: linear-gradient(to top, #000 0%, rgba(0,0,0,0) 100%);"></div>
				<div data-color-selector style="position:absolute; width:14px; height:14px; border-radius:50%; border:2px solid #111; background:#fff; transform:translate(-50%,-50%); pointer-events:none;"></div>
			</div>

			<div class="mb-2 position-relative">
				<input type="range" min="0" max="360" value="38" class="form-range m-0" data-color-hue style="background: linear-gradient(to right, #f00, #ff0, #0f0, #0ff, #00f, #f0f, #f00); height: 14px; border-radius: 999px; appearance: none;">
			</div>

			<div class="mb-2 position-relative" style="background: repeating-conic-gradient(#d1d5db 0% 25%, #fff 0% 50%) 50%/12px 12px; border-radius: 999px;">
				<input type="range" min="0" max="100" value="100" class="form-range m-0" data-color-alpha style="height: 14px; border-radius: 999px; appearance: none;">
			</div>

			<div class="d-flex gap-2 mb-2">
				<button type="button" class="btn btn-sm btn-light active" data-color-mode="hex">HEX</button>
				<button type="button" class="btn btn-sm btn-light" data-color-mode="rgb">RGB</button>
				<button type="button" class="btn btn-sm btn-light" data-color-mode="hsv">HSV</button>
				<button type="button" class="btn btn-sm btn-light" data-color-mode="hsl">HSL</button>
			</div>

			<div class="input-group input-group-sm mb-2">
				<div class="input-group-text" style="width: 48px;" data-color-preview></div>
				<input type="text" class="form-control" data-color-value>
				<button type="button" class="btn btn-outline-secondary" data-color-close><i class="bi bi-x-lg"></i></button>
				<button type="button" class="btn btn-success" data-color-apply><i class="bi bi-check-lg"></i></button>
			</div>

			<div class="d-flex gap-2">
				<button type="button" class="btn btn-sm p-0 border" data-swatch="#d97706" style="width:30px;height:30px;background:#d97706;"></button>
				<button type="button" class="btn btn-sm p-0 border" data-swatch="#ca8a04" style="width:30px;height:30px;background:#ca8a04;"></button>
				<button type="button" class="btn btn-sm p-0 border" data-swatch="#eab308" style="width:30px;height:30px;background:#eab308;"></button>
				<button type="button" class="btn btn-sm p-0 border" data-swatch="#84cc16" style="width:30px;height:30px;background:#84cc16;"></button>
				<button type="button" class="btn btn-sm p-0 border" data-swatch="#7dd3fc" style="width:30px;height:30px;background:#7dd3fc;"></button>
				<button type="button" class="btn btn-sm p-0 border" data-swatch="#c084fc" style="width:30px;height:30px;background:#c084fc;"></button>
			</div>
		</div>
	`;

	const gradient = pickerContainer.querySelector('[data-color-gradient]');
	const selector = pickerContainer.querySelector('[data-color-selector]');
	const hueSlider = pickerContainer.querySelector('[data-color-hue]');
	const alphaSlider = pickerContainer.querySelector('[data-color-alpha]');
	const valueInput = pickerContainer.querySelector('[data-color-value]');
	const preview = pickerContainer.querySelector('[data-color-preview]');
	const applyBtn = pickerContainer.querySelector('[data-color-apply]');
	const closeBtn = pickerContainer.querySelector('[data-color-close]');
	const modeButtons = pickerContainer.querySelectorAll('[data-color-mode]');
	const swatches = pickerContainer.querySelectorAll('[data-swatch]');

	let currentHue = 38;
	let currentSat = 100;
	let currentLum = 50;
	let currentAlpha = 1;
	let currentMode = 'hex';

	const updateModeButtons = () => {
		modeButtons.forEach((btn) => {
			btn.classList.toggle('active', btn.dataset.colorMode === currentMode);
		});
	};

	const updatePreview = () => {
		const hex = hslToHex(currentHue, currentSat, currentLum);
		const rgb = hexToRgb(hex);
		preview.style.background = `rgba(${rgb.r},${rgb.g},${rgb.b},${currentAlpha})`;
		if (currentMode === 'hex') {
			valueInput.value = hex.toUpperCase();
		} else if (currentMode === 'rgb') {
			valueInput.value = `${rgb.r}, ${rgb.g}, ${rgb.b}`;
		} else if (currentMode === 'hsv') {
			valueInput.value = `${currentHue}, ${Math.round(currentSat)}, ${Math.round((100 - Math.abs(currentLum - 50) * 2))}`;
		} else {
			const [h, s, l] = rgbToHsl(rgb.r, rgb.g, rgb.b);
			valueInput.value = `${h}, ${s}, ${l}`;
		}

		gradient.style.background = `hsl(${currentHue}, 100%, 50%)`;
		alphaSlider.style.background = `linear-gradient(to right, rgba(${rgb.r},${rgb.g},${rgb.b},0), rgba(${rgb.r},${rgb.g},${rgb.b},1))`;
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
		updatePreview();
	});

	alphaSlider.addEventListener('input', (e) => {
		currentAlpha = parseInt(e.target.value) / 100;
		updatePreview();
	});

	valueInput.addEventListener('change', () => {
		if (currentMode === 'hex' && /^#[0-9A-F]{6}$/i.test(valueInput.value.trim())) {
			const rgb = hexToRgb(valueInput.value.trim());
			const [h, s, l] = rgbToHsl(rgb.r, rgb.g, rgb.b);
			currentHue = h;
			currentSat = s;
			currentLum = l;
			hueSlider.value = currentHue;
			updatePreview();
		}
	});

	modeButtons.forEach((btn) => {
		btn.addEventListener('click', () => {
			currentMode = btn.dataset.colorMode || 'hex';
			updateModeButtons();
			updatePreview();
		});
	});

	swatches.forEach(swatch => {
		swatch.addEventListener('click', () => {
			const color = swatch.dataset.swatch || '#000000';
			const rgb = hexToRgb(color);
			if (!rgb) return;
			const [h, s, l] = rgbToHsl(rgb.r, rgb.g, rgb.b);
			currentHue = h;
			currentSat = s;
			currentLum = l;
			hueSlider.value = currentHue;
			updatePreview();
		});
	});

	applyBtn.addEventListener('click', () => {
		const color = hslToHex(currentHue, currentSat, currentLum);
		focusCanvas(canvas);
		exec(cmd, color);
		syncCanvasToInput(canvas, input);
		pickerContainer.hidden = true;
	});

	closeBtn?.addEventListener('click', () => {
		pickerContainer.hidden = true;
	});

	button.addEventListener('click', (e) => {
		e.preventDefault();
		pickerContainer.hidden = !pickerContainer.hidden;
		if (!pickerContainer.hidden) {
			updateModeButtons();
			updatePreview();
		}
	});
}

function initEditorInstance(root) {
	const canvas = root.querySelector('[data-editor-canvas]');
	const input = root.querySelector('[data-editor-source]');
	const htmlEditor = root.querySelector('[data-editor-html-mode]');
	const htmlToggleLabel = root.querySelector('[data-editor-html-toggle-label]');
	const panel = root.querySelector('[data-editor-panel]');
	const previewPane = root.querySelector('[data-editor-preview-pane]');
	const previewCanvas = root.querySelector('[data-editor-preview-canvas]');
	const libraryModeButtons = root.querySelectorAll('[data-editor-library-mode]');
	const libraryViews = root.querySelectorAll('[data-editor-library-view]');
	const libraryList = root.querySelector('[data-editor-library-list]');
	const libraryStatus = root.querySelector('[data-editor-library-status]');
	const libraryAddType = root.querySelector('[data-editor-library-add-type]');
	const libraryAddLabel = root.querySelector('[data-editor-library-add-label]');
	const libraryAddIcon = root.querySelector('[data-editor-library-add-icon]');
	const libraryAddHtml = root.querySelector('[data-editor-library-add-html]');
	const libraryAddButton = root.querySelector('[data-editor-library-add]');
	const librarySaveButton = root.querySelector('[data-editor-library-save]');

	if (!canvas || !input) return;

	const form = root.closest('form');
	let mediaModal = null;
	let wantsMediaInsert = false;
	let isHtmlMode = false;
	let draggedHtml = '';
	const librarySaveUrl = panel?.dataset.editorLibrarySaveUrl || '';
	const librarySeedRaw = panel?.dataset.editorLibrary || '{"snippets":[],"blocks":[]}';
	let libraryState = { snippets: [], blocks: [] };
	try {
		const parsed = JSON.parse(librarySeedRaw);
		libraryState = {
			snippets: Array.isArray(parsed.snippets) ? parsed.snippets : [],
			blocks: Array.isArray(parsed.blocks) ? parsed.blocks : [],
		};
	} catch {
		libraryState = { snippets: [], blocks: [] };
	}

	const contextMenu = CONTEXT_MENU_ENABLED ? document.createElement('div') : null;
	if (contextMenu) {
		contextMenu.className = 'catmin-editor-context-menu card shadow-sm p-2';
		contextMenu.hidden = true;
		root.appendChild(contextMenu);
	}
	let contextTarget = null;
	let contextType = null;

	const renderContextMenu = (type) => {
		if (!contextMenu) {
			return;
		}

		if (type === 'image') {
			contextMenu.innerHTML = `
				<div class="small text-muted mb-2">Image</div>
				<div class="btn-group btn-group-sm mb-2 w-100">
					<button type="button" class="btn btn-outline-secondary" data-context-action="img-left">Gauche</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="img-center">Centre</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="img-right">Droite</button>
				</div>
				<div class="btn-group btn-group-sm w-100">
					<button type="button" class="btn btn-outline-secondary" data-context-action="img-top">Haut</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="img-middle">Milieu</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="img-bottom">Bas</button>
				</div>
			`;
			return;
		}

		if (type === 'accordion') {
			contextMenu.innerHTML = `
				<div class="small text-muted mb-2">Accordeon</div>
				<div class="btn-group btn-group-sm mb-2 w-100">
					<button type="button" class="btn btn-outline-secondary" data-context-action="acc-add">+ Section</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="acc-remove">- Section</button>
				</div>
				<div class="btn-group btn-group-sm w-100">
					<button type="button" class="btn btn-outline-secondary" data-context-action="acc-flush">Toggle Flush</button>
				</div>
			`;
			return;
		}

		if (type === 'column') {
			contextMenu.innerHTML = `
				<div class="small text-muted mb-2">Colonne</div>
				<div class="btn-group btn-group-sm mb-2 w-100">
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-rounded">Arrondi</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-pill">Pill</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-no-rounded">Carré</button>
				</div>
				<div class="btn-group btn-group-sm mb-2 w-100">
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-bg-light">Fond light</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-bg-primary">Fond primary</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-bg-warning">Fond warning</button>
				</div>
				<div class="btn-group btn-group-sm w-100">
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-border-1">Bordure 1</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-border-3">Bordure 3</button>
					<button type="button" class="btn btn-outline-secondary" data-context-action="col-border-5">Bordure 5</button>
				</div>
			`;
			return;
		}

		contextMenu.innerHTML = `
			<div class="small text-muted mb-2">Bloc texte</div>
			<div class="btn-group btn-group-sm w-100">
				<button type="button" class="btn btn-outline-secondary" data-context-action="text-left">Txt gauche</button>
				<button type="button" class="btn btn-outline-secondary" data-context-action="text-center">Txt centre</button>
				<button type="button" class="btn btn-outline-secondary" data-context-action="text-right">Txt droite</button>
			</div>
		`;
	};

	const resolveContextTarget = (target) => {
		const image = target.closest('img');
		if (image) {
			return { type: 'image', element: image };
		}

		const accordionItem = target.closest('.accordion-item');
		const accordion = target.closest('.accordion');
		if (accordionItem || accordion) {
			return { type: 'accordion', element: accordion || accordionItem.closest('.accordion') };
		}

		const column = target.closest('[class*="col-"]');
		if (column) {
			return { type: 'column', element: column };
		}

		const block = target.closest('div,section,article,figure,p,li');
		if (block) {
			return { type: 'block', element: block };
		}

		return null;
	};

	const setLibraryStatus = (message, isError = false) => {
		if (!libraryStatus) return;
		libraryStatus.textContent = message;
		libraryStatus.classList.toggle('text-danger', isError);
		libraryStatus.classList.toggle('text-success', !isError);
	};

	const toSafeText = (value) => String(value || '').replace(/[&<>"']/g, (m) => ({
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	}[m] || m));

	const iconMarkup = (icon, fallback) => {
		const iconValue = String(icon || '').trim();
		if (/^(https?:\/\/|\/)/i.test(iconValue)) {
			return `<img src="${toSafeText(iconValue)}" alt="" style="width:1rem;height:1rem;object-fit:contain;">`;
		}
		return `<i class="bi ${toSafeText(iconValue || fallback)}"></i>`;
	};

	const renderInsertButtons = () => {
		const snippetsPane = root.querySelector('[data-editor-pane="snippets"] > div');
		const blocksPane = root.querySelector('[data-editor-pane="blocks"] > div');
		const tooltipTargets = [];
		if (snippetsPane) {
			snippetsPane.classList.remove('d-grid');
			snippetsPane.classList.add('d-flex', 'flex-wrap', 'gap-2');
			snippetsPane.innerHTML = libraryState.snippets.map((item) => `
				<button type="button" class="btn btn-sm btn-light border catmin-editor-icon-btn" draggable="true" data-editor-draggable-item="1" data-editor-action="insert-html" data-bs-toggle="tooltip" data-bs-placement="top" title="${toSafeText(item.label || 'Snippet')}" data-editor-css="${toSafeText(item.css || '')}" data-editor-html="${toSafeText(item.html || '')}">
					${iconMarkup(item.icon, 'bi-stars')}
				</button>
			`).join('') || '<p class="small text-muted mb-0">Aucun snippet disponible.</p>';
			tooltipTargets.push(...snippetsPane.querySelectorAll('[data-bs-toggle="tooltip"]'));
		}
		if (blocksPane) {
			blocksPane.classList.remove('d-grid');
			blocksPane.classList.add('d-flex', 'flex-wrap', 'gap-2');
			blocksPane.innerHTML = libraryState.blocks.map((item) => `
				<button type="button" class="btn btn-sm btn-light border catmin-editor-icon-btn" draggable="true" data-editor-draggable-item="1" data-editor-action="insert-html" data-bs-toggle="tooltip" data-bs-placement="top" title="${toSafeText(item.label || 'Bloc')}" data-editor-css="${toSafeText(item.css || '')}" data-editor-html="${toSafeText(item.html || '')}">
					${iconMarkup(item.icon, 'bi-grid-3x3-gap')}
				</button>
			`).join('') || '<p class="small text-muted mb-0">Aucun bloc disponible.</p>';
			tooltipTargets.push(...blocksPane.querySelectorAll('[data-bs-toggle="tooltip"]'));
		}
		bindInsertInteractions();
		if (window.bootstrap?.Tooltip) {
			tooltipTargets.forEach((el) => window.bootstrap.Tooltip.getOrCreateInstance(el));
		}
	};

	const renderLibraryManager = () => {
		if (!libraryList) return;
		const rows = [
			...libraryState.snippets.map((item, idx) => ({ ...item, type: 'snippets', idx })),
			...libraryState.blocks.map((item, idx) => ({ ...item, type: 'blocks', idx })),
		];

		libraryList.innerHTML = rows.map((row) => `
			<div class="border rounded p-2 mb-2" data-lib-row data-lib-type="${row.type}" data-lib-idx="${row.idx}">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="badge text-bg-secondary">${row.type === 'snippets' ? 'Snippet' : 'Bloc'}</span>
					<button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-lib-delete>Supprimer</button>
				</div>
				<input type="text" class="form-control form-control-sm mb-2" data-lib-label value="${toSafeText(row.label || '')}" placeholder="Label">
				<input type="text" class="form-control form-control-sm mb-2" data-lib-icon value="${toSafeText(row.icon || '')}" placeholder="Icone bootstrap">
				<textarea class="form-control form-control-sm font-monospace" rows="3" data-lib-html placeholder="HTML">${toSafeText(row.html || '')}</textarea>
			</div>
		`).join('') || '<div class="small text-muted">Aucun code existant.</div>';

		libraryList.querySelectorAll('[data-lib-row]').forEach((rowEl) => {
			const type = rowEl.getAttribute('data-lib-type');
			const idx = Number.parseInt(rowEl.getAttribute('data-lib-idx') || '-1', 10);
			if (idx < 0 || (type !== 'snippets' && type !== 'blocks')) return;
			const target = libraryState[type][idx];
			if (!target) return;

			rowEl.querySelector('[data-lib-label]')?.addEventListener('input', (e) => {
				target.label = e.target.value;
			});
			rowEl.querySelector('[data-lib-icon]')?.addEventListener('input', (e) => {
				target.icon = e.target.value;
			});
			rowEl.querySelector('[data-lib-html]')?.addEventListener('input', (e) => {
				target.html = e.target.value;
			});
			rowEl.querySelector('[data-lib-delete]')?.addEventListener('click', () => {
				libraryState[type].splice(idx, 1);
				renderLibraryManager();
				renderInsertButtons();
				setLibraryStatus('Element supprimé localement. Pensez à enregistrer.');
			});
		});
	};

	const saveLibrary = async () => {
		if (!librarySaveUrl) {
			setLibraryStatus('URL de sauvegarde introuvable.', true);
			return;
		}
		const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
		const payload = {
			snippets: libraryState.snippets.filter((i) => (i.label || '').trim() && (i.html || '').trim()),
			blocks: libraryState.blocks.filter((i) => (i.label || '').trim() && (i.html || '').trim()),
		};
		setLibraryStatus('Enregistrement en cours...');
		try {
			const res = await fetch(librarySaveUrl, {
				method: 'PUT',
				headers: {
					'Content-Type': 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
					'X-CSRF-TOKEN': token,
					'Accept': 'application/json',
				},
				body: JSON.stringify(payload),
			});
			if (!res.ok) {
				throw new Error('Erreur HTTP');
			}
			setLibraryStatus('Bibliothèque enregistrée avec succès.');
			renderInsertButtons();
		} catch {
			setLibraryStatus('Erreur pendant la sauvegarde.', true);
		}
	};

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

	root.querySelectorAll('[data-editor-action="modal-link"]').forEach((button) => {
		button.addEventListener('click', () => {
			const modalIdRaw = (window.prompt('ID de la modal cible (ex: pricingModal)', 'exampleModal') || '').trim();
			if (!modalIdRaw) return;
			const label = (window.prompt('Texte du bouton', 'Ouvrir la modal') || '').trim() || 'Ouvrir la modal';
			const modalId = modalIdRaw.replace(/^#/, '');
			const html = `<a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#${modalId}">${label}</a>`;
			if (isHtmlMode && htmlEditor) {
				htmlEditor.value = `${htmlEditor.value}\n${html}`.trim();
				input.value = htmlEditor.value;
			} else {
				focusCanvas(canvas);
				insertHtmlWithBreak(html);
				syncCanvasToInput(canvas, input);
			}
			updateLivePreview();
		});
	});

	root.querySelectorAll('[data-editor-action="insert-bookmarks"]').forEach((button) => {
		button.addEventListener('click', () => {
			const html = `
			<nav class="catmin-bookmarks-floating catmin-bookmarks-auto">
				<div class="catmin-bookmarks-title">Sommaire</div>
				<ul class="catmin-bookmarks-list"></ul>
			</nav>
			`;
			if (isHtmlMode && htmlEditor) {
				htmlEditor.value = `${htmlEditor.value}\n${html}`.trim();
				input.value = htmlEditor.value;
			} else {
				focusCanvas(canvas);
				insertHtmlWithBreak(html);
				syncCanvasToInput(canvas, input);
			}
			updateLivePreview();
		});
	});

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
			const source = isHtmlMode && htmlEditor ? htmlEditor.value : (input.value || canvas.innerHTML || '');
			const decoded = decodeHtmlEntities(source);
			previewCanvas.innerHTML = decoded || '<p style="color:#ccc; text-align: center; padding: 2rem;">Rendu en direct</p>';
		}
	};

	const hideContextMenu = () => {
		if (!contextMenu) {
			return;
		}
		contextMenu.hidden = true;
		contextTarget = null;
		contextType = null;
	};

	const showContextMenu = (x, y, type, target) => {
		if (!contextMenu) {
			return;
		}
		contextTarget = target;
		contextType = type;
		renderContextMenu(type);
		contextMenu.hidden = false;
		contextMenu.style.position = 'fixed';
		contextMenu.style.zIndex = '1200';
		contextMenu.style.left = `${x}px`;
		contextMenu.style.top = `${y}px`;
	};

	const enterHtmlMode = () => {
		if (!htmlEditor) return;
		syncCanvasToInput(canvas, input);
		htmlEditor.value = input.value || canvas.innerHTML || '';
		canvas.classList.add('d-none');
		htmlEditor.classList.remove('d-none');
		isHtmlMode = true;
		if (htmlToggleLabel) htmlToggleLabel.textContent = 'Visuel';
		updateLivePreview();
	};

	const exitHtmlMode = () => {
		if (!htmlEditor) return;
		const raw = htmlEditor.value || '';
		canvas.innerHTML = raw;
		syncCanvasToInput(canvas, input);
		htmlEditor.classList.add('d-none');
		canvas.classList.remove('d-none');
		isHtmlMode = false;
		if (htmlToggleLabel) htmlToggleLabel.textContent = 'HTML';
		updateLivePreview();
	};

	canvas.addEventListener('input', updateLivePreview);
	canvas.addEventListener('blur', updateLivePreview);
	form?.addEventListener('submit', () => syncCanvasToInput(canvas, input));

	// Toggle HTML mode button
	root.querySelectorAll('[data-editor-action="toggle-html"]').forEach((button) => {
		button.addEventListener('click', () => {
			if (!isHtmlMode) {
				enterHtmlMode();
			} else {
				exitHtmlMode();
			}
		});
	});

	htmlEditor?.addEventListener('input', () => {
		input.value = htmlEditor.value;
		updateLivePreview();
	});

	const bindInsertInteractions = () => {
		root.querySelectorAll('[data-editor-draggable-item="1"]').forEach((button) => {
			if (button.dataset.editorDragBound === '1') return;
			button.dataset.editorDragBound = '1';
			button.addEventListener('dragstart', (event) => {
				const rawHtml = button.getAttribute('data-editor-html') || button.dataset.editorHtml || '';
				const rawCss = button.getAttribute('data-editor-css') || button.dataset.editorCss || '';
				draggedHtml = wrapHtmlWithCss(decodeHtmlEntities(rawHtml), decodeHtmlEntities(rawCss));
				event.dataTransfer?.setData('text/plain', draggedHtml);
				event.dataTransfer.effectAllowed = 'copy';
			});
		});

		root.querySelectorAll('[data-editor-action="insert-html"]').forEach((button) => {
			if (button.dataset.editorInsertBound === '1') return;
			button.dataset.editorInsertBound = '1';
			button.addEventListener('click', () => {
				const rawHtml = button.getAttribute('data-editor-html') || button.dataset.editorHtml || '';
				const rawCss = button.getAttribute('data-editor-css') || button.dataset.editorCss || '';
				const html = wrapHtmlWithCss(decodeHtmlEntities(rawHtml), decodeHtmlEntities(rawCss));
				if (isHtmlMode && htmlEditor) {
					htmlEditor.value = `${htmlEditor.value}\n${html}\n<p><br></p>`.trim();
					input.value = htmlEditor.value;
				} else {
					focusCanvas(canvas);
					insertHtmlWithBreak(html);
					syncCanvasToInput(canvas, input);
				}
				updateLivePreview();
			});
		});
	};

	libraryModeButtons.forEach((btn) => {
		btn.addEventListener('click', () => {
			const mode = btn.dataset.editorLibraryMode;
			libraryModeButtons.forEach((b) => b.classList.toggle('active', b === btn));
			libraryViews.forEach((view) => {
				view.hidden = view.dataset.editorLibraryView !== mode;
			});
			if (mode === 'manage') {
				renderLibraryManager();
			}
		});
	});

	libraryAddButton?.addEventListener('click', () => {
		const type = libraryAddType?.value === 'blocks' ? 'blocks' : 'snippets';
		const label = libraryAddLabel?.value?.trim() || '';
		const icon = libraryAddIcon?.value?.trim() || '';
		const html = libraryAddHtml?.value?.trim() || '';
		if (!label || !html) {
			setLibraryStatus('Label et HTML sont obligatoires pour ajouter.', true);
			return;
		}
		libraryState[type].push({ label, icon, html });
		if (libraryAddLabel) libraryAddLabel.value = '';
		if (libraryAddIcon) libraryAddIcon.value = '';
		if (libraryAddHtml) libraryAddHtml.value = '';
		renderLibraryManager();
		renderInsertButtons();
		setLibraryStatus('Element ajouté localement. Pensez à enregistrer.');
	});

	librarySaveButton?.addEventListener('click', () => {
		saveLibrary();
	});

	canvas.addEventListener('dragover', (event) => {
		event.preventDefault();
		event.dataTransfer.dropEffect = 'copy';
		canvas.classList.add('drag-over');
	});

	canvas.addEventListener('dragleave', () => {
		canvas.classList.remove('drag-over');
	});

	canvas.addEventListener('drop', (event) => {
		event.preventDefault();
		canvas.classList.remove('drag-over');
		const html = event.dataTransfer?.getData('text/plain') || draggedHtml;
		if (!html) return;
		focusCanvas(canvas);
		insertHtmlWithBreak(html);
		syncCanvasToInput(canvas, input);
		updateLivePreview();
	});

	bindInsertInteractions();

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
	canvas.addEventListener('input', updateLivePreview);

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

	if (CONTEXT_MENU_ENABLED && contextMenu) {
		canvas.addEventListener('contextmenu', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}
			const resolved = resolveContextTarget(target);
			if (!resolved || !resolved.element) {
				hideContextMenu();
				return;
			}

			event.preventDefault();
			showContextMenu(event.clientX, event.clientY, resolved.type, resolved.element);
		});

		contextMenu.addEventListener('click', (event) => {
		const btn = event.target.closest('[data-context-action]');
		if (!btn || !(btn instanceof HTMLElement) || !contextTarget) {
			return;
		}

		const action = btn.dataset.contextAction;
		if (contextType === 'image' && contextTarget.tagName === 'IMG') {
			const img = contextTarget;
			img.classList.remove('float-start', 'float-end', 'd-block', 'mx-auto', 'align-top', 'align-middle', 'align-bottom');
			if (action === 'img-left') {
				img.classList.add('float-start', 'me-2', 'mb-2');
			}
			if (action === 'img-center') {
				img.classList.add('d-block', 'mx-auto', 'mb-2');
			}
			if (action === 'img-right') {
				img.classList.add('float-end', 'ms-2', 'mb-2');
			}
			if (action === 'img-top') img.classList.add('align-top');
			if (action === 'img-middle') img.classList.add('align-middle');
			if (action === 'img-bottom') img.classList.add('align-bottom');
		} else if (contextType === 'accordion') {
			const accordion = contextTarget.classList.contains('accordion') ? contextTarget : contextTarget.closest('.accordion');
			if (!accordion) {
				return;
			}

			if (!accordion.id) {
				accordion.id = `acc_${Date.now()}`;
			}

			if (action === 'acc-add') {
				const idx = accordion.querySelectorAll('.accordion-item').length + 1;
				const sectionId = `${accordion.id}_item_${idx}`;
				const item = document.createElement('div');
				item.className = 'accordion-item';
				item.innerHTML = `
					<h2 class="accordion-header">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${sectionId}" aria-expanded="false" aria-controls="${sectionId}">Section ${idx}</button>
					</h2>
					<div id="${sectionId}" class="accordion-collapse collapse" data-bs-parent="#${accordion.id}">
						<div class="accordion-body">Contenu de la section ${idx}.</div>
					</div>
				`;
				accordion.appendChild(item);
			}

			if (action === 'acc-remove') {
				const items = accordion.querySelectorAll('.accordion-item');
				if (items.length > 1) {
					items[items.length - 1].remove();
				}
			}

			if (action === 'acc-flush') {
				accordion.classList.toggle('accordion-flush');
			}
		} else if (contextType === 'column') {
			const col = contextTarget;
			col.classList.remove(
				'rounded-0', 'rounded', 'rounded-1', 'rounded-2', 'rounded-3', 'rounded-pill',
				'bg-light', 'bg-primary', 'bg-warning',
				'border-0', 'border-1', 'border-2', 'border-3', 'border-4', 'border-5',
				'border-primary', 'border-secondary', 'border-success', 'border-warning', 'border-danger', 'border-info'
			);

			if (action === 'col-rounded') col.classList.add('rounded-3');
			if (action === 'col-pill') col.classList.add('rounded-pill');
			if (action === 'col-no-rounded') col.classList.add('rounded-0');
			if (action === 'col-bg-light') col.classList.add('bg-light', 'p-3');
			if (action === 'col-bg-primary') col.classList.add('bg-primary', 'text-light', 'p-3');
			if (action === 'col-bg-warning') col.classList.add('bg-warning', 'p-3');
			if (action === 'col-border-1') col.classList.add('border', 'border-1', 'border-primary');
			if (action === 'col-border-3') col.classList.add('border', 'border-3', 'border-primary');
			if (action === 'col-border-5') col.classList.add('border', 'border-5', 'border-primary');
		} else {
			contextTarget.classList.remove('text-start', 'text-center', 'text-end');
			if (action === 'text-left') contextTarget.classList.add('text-start');
			if (action === 'text-center') contextTarget.classList.add('text-center');
			if (action === 'text-right') contextTarget.classList.add('text-end');
		}

		syncCanvasToInput(canvas, input);
		updateLivePreview();
		hideContextMenu();
		});

		document.addEventListener('click', (event) => {
			if (!contextMenu.contains(event.target)) {
				hideContextMenu();
			}
		});
	}

	syncCanvasToInput(canvas, input);
	if (previewPane) {
		previewPane.hidden = false;
	}
	updateLivePreview();
}

export function initCatminEditor() {
	const fields = document.querySelectorAll('[data-catmin-editor-field][data-enabled="1"]');
	if (!fields.length) return;

	fields.forEach((field) => initEditorInstance(field));
}
