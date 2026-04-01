// Inline formatting commands that can have a queryable state
const STATEFUL_CMDS = ['bold', 'italic', 'underline', 'strikeThrough'];

// Bootstrap 5 color palette
const BOOTSTRAP_COLORS = [
	'#0d6efd', '#6c757d', '#198754', '#ffc107', '#fd7e14', '#dc3545',
	'#0dcaf0', '#f8f9fa', '#212529', '#ff69b4', '#9c27b0', '#3f51b5',
];

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

function initEditorInstance(root) {
	const canvas = root.querySelector('[data-editor-canvas]');
	const input = root.querySelector('[data-editor-source]');
	const panel = root.querySelector('[data-editor-panel]');

	if (!canvas || !input) return;

	const form = root.closest('form');
	let mediaModal = null;
	let wantsMediaInsert = false;

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

	// Color picker
	root.querySelectorAll('[data-editor-action="color-picker"]').forEach((button) => {
		const cmd = button.dataset.editorColorCmd;
		const pickerEl = root.querySelector(`[data-editor-color-picker="${cmd}"]`);
		if (!pickerEl) return;

		BOOTSTRAP_COLORS.forEach((color) => {
			const cbtn = document.createElement('button');
			cbtn.type = 'button';
			cbtn.style.backgroundColor = color;
			cbtn.addEventListener('click', (e) => {
				e.preventDefault();
				focusCanvas(canvas);
				exec(cmd, color);
				syncCanvasToInput(canvas, input);
				pickerEl.hidden = true;
			});
			pickerEl.appendChild(cbtn);
		});

		button.addEventListener('click', (e) => {
			e.preventDefault();
			pickerEl.hidden = !pickerEl.hidden;
		});
	});

	// Live preview
	const previewPane = root.querySelector('[data-editor-preview-pane]');
	const previewCanvas = root.querySelector('[data-editor-preview-canvas]');
	if (previewPane && previewCanvas) {
		const updatePreview = () => {
			previewCanvas.innerHTML = canvas.innerHTML || '<p style="color:#999;">Aucun contenu</p>';
		};
		updatePreview();
		canvas.addEventListener('input', updatePreview);
		canvas.addEventListener('blur', updatePreview);
		root.querySelectorAll('[data-editor-action="toggle-preview"]').forEach((button) => {
			button.addEventListener('click', () => {
				const isHidden = previewPane.hidden;
				previewPane.hidden = !isHidden;
				if (!isHidden) updatePreview();
			});
		});
	}

	// Insert HTML
	root.querySelectorAll('[data-editor-action="insert-html"]').forEach((button) => {
		button.addEventListener('click', () => {
			focusCanvas(canvas);
			insertHtmlAtCursor(button.dataset.editorHtml || '');
			syncCanvasToInput(canvas, input);
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

	// Sync on input/blur/submit
	canvas.addEventListener('input', () => syncCanvasToInput(canvas, input));
	canvas.addEventListener('blur', () => syncCanvasToInput(canvas, input));
	form?.addEventListener('submit', () => syncCanvasToInput(canvas, input));

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

		wantsMediaInsert = false;
		mediaModal?.hide();
	});

	syncCanvasToInput(canvas, input);
}

export function initCatminEditor() {
	const fields = document.querySelectorAll('[data-catmin-editor-field][data-enabled="1"]');
	if (!fields.length) return;

	fields.forEach((field) => initEditorInstance(field));
}
