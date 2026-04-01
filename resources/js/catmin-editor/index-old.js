// Inline formatting commands that can have a queryable state
const STATEFUL_CMDS = ['bold', 'italic', 'underline', 'strikeThrough'];

function exec(command, value = null) {
	document.execCommand(command, false, value);
}

function focusCanvas(canvas) {
	if (!canvas) {
		return;
	}
	canvas.focus();
}

function insertHtmlAtCursor(html) {
	if (!html) {
		return;
	}
	exec('insertHTML', html);
}

function syncCanvasToInput(canvas, input) {
	if (!canvas || !input) {
		return;
	}
	input.value = canvas.innerHTML.trim();
}

/**
 * Reset all toolbar button active states.
 */
function clearToolbarState(root) {
	root.querySelectorAll('[data-editor-cmd]').forEach((el) => {
		if (el.tagName !== 'SELECT') {
			el.classList.remove('active');
		}
	});
}

/**
 * Reflect current selection formatting in toolbar buttons.
 * e.g. if cursor is inside bold text → bold button gets .active
 */
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

/**
 * Show the inline link popup associated with this editor root.
 * Saves the current selection so it can be restored when applying.
 */
function showLinkPopup(root, savedRange) {
	const popup = root.querySelector('[data-editor-link-popup]');
	const urlInput = root.querySelector('[data-editor-link-input]');
	if (!popup || !urlInput) {
		return;
	}
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

	if (!canvas || !input) {
		return;
	}

	const form = root.closest('form');
	let mediaModal = null;
	let wantsMediaInsert = false;

	// ── Toolbar commands ──────────────────────────────────────────────────
	root.querySelectorAll('[data-editor-cmd]').forEach((button) => {
		if (button.tagName === 'SELECT') {
			button.addEventListener('change', () => {
				focusCanvas(canvas);
				exec(button.dataset.editorCmd, `<${button.value}>`);
				syncCanvasToInput(canvas, input);
				// Reset select back to first option after applying
				button.selectedIndex = 0;
				clearToolbarState(root);
			});
			return;
		}

		const colorInput = button.querySelector('input[type="color"]');
		if (colorInput) {
			colorInput.addEventListener('input', () => {
				focusCanvas(canvas);
				exec(colorInput.dataset.editorCmd, colorInput.value);
				syncCanvasToInput(canvas, input);
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

	// ── Inline link popup ─────────────────────────────────────────────────
	root.querySelectorAll('[data-editor-action="link"]').forEach((button) => {
		button.addEventListener('click', () => {
			// Save selection before the popup steal focus
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
			// Restore saved selection then apply link
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

	// ── Panel toggle ──────────────────────────────────────────────────────
	root.querySelectorAll('[data-editor-action="toggle-panel"]').forEach((button) => {
		button.addEventListener('click', () => {
			if (!panel) {
				return;
			}
			panel.hidden = !panel.hidden;
			root.classList.toggle('catmin-editor--panel-open', !panel.hidden);
		});
	});

	// ── Panel tabs (Snippets / Blocs) ──────────────────────────────────
	const tabButtons = root.querySelectorAll('[data-editor-tab]');
	const tabPanes = root.querySelectorAll('[data-editor-pane]');
	if (tabButtons.length && tabPanes.length) {
		tabButtons.forEach((button) => {
			button.addEventListener('click', () => {
				const target = button.dataset.editorTab;
				if (!target) {
					return;
				}

				tabButtons.forEach((btn) => btn.classList.remove('active'));
				button.classList.add('active');

				tabPanes.forEach((pane) => {
					pane.hidden = pane.dataset.editorPane !== target;
				});
			});
		});
	}

	// ── Preview button ─────────────────────────────────────────────────
	root.querySelectorAll('[data-editor-action="preview"]').forEach((button) => {
		button.addEventListener('click', () => {
			const inputId = root.dataset.editorInput;
			const previewContent = document.getElementById(`editor-preview-${inputId}`);
			if (previewContent) {
				previewContent.innerHTML = canvas.innerHTML;
			}
			const modalElement = document.getElementById(`editor-preview-modal-${inputId}`);
			if (modalElement && window.bootstrap?.Modal) {
				const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
				modal.show();
			}
		});
	});

	// ── Insert HTML snippets ──────────────────────────────────────────────
	root.querySelectorAll('[data-editor-action="insert-html"]').forEach((button) => {
		button.addEventListener('click', () => {
			focusCanvas(canvas);
			insertHtmlAtCursor(button.dataset.editorHtml || '');
			syncCanvasToInput(canvas, input);
		});
	});

	// ── Media picker ──────────────────────────────────────────────────────
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

	// ── Update toolbar state on selection change ──────────────────────────
	document.addEventListener('selectionchange', () => {
		// Only update when focus is inside this canvas
		if (canvas.contains(document.activeElement) || document.activeElement === canvas) {
			updateToolbarState(root);
		}
	});

	canvas.addEventListener('keyup', () => updateToolbarState(root));
	canvas.addEventListener('mouseup', () => updateToolbarState(root));

	// ── Sync ──────────────────────────────────────────────────────────────
	canvas.addEventListener('input', () => syncCanvasToInput(canvas, input));
	canvas.addEventListener('blur', () => syncCanvasToInput(canvas, input));

	form?.addEventListener('submit', () => syncCanvasToInput(canvas, input));

	window.addEventListener('catmin:media-selected', (event) => {
		if (!wantsMediaInsert) {
			return;
		}

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
	if (!fields.length) {
		return;
	}

	fields.forEach((field) => initEditorInstance(field));
}
