import 'bootstrap';

function escapeHtml(value) {
	return String(value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function renderPickerPreview(container, item) {
	if (!container) {
		return;
	}

	const empty = container.querySelector('[data-media-picker-empty]');
	if (empty) {
		empty.remove();
	}

	const preview = item.preview_url
		? `<img src="${escapeHtml(item.preview_url)}" alt="preview" class="catmin-media-thumb">`
		: `<span class="badge text-bg-light">${escapeHtml((item.extension || 'file').toUpperCase())}</span>`;

	container.innerHTML = `
		<div class="catmin-media-selected card border-0 bg-body-tertiary">
			<div class="card-body d-flex align-items-center gap-3 py-2 px-3">
				${preview}
				<div class="min-w-0">
					<p class="mb-0 fw-semibold text-truncate">${escapeHtml(item.original_name || 'media')}</p>
					<p class="mb-0 small text-muted">#${escapeHtml(item.id)} · ${escapeHtml(item.size_human || '')}</p>
				</div>
			</div>
		</div>
	`;
}

function resetPickerPreview(field) {
	const preview = field.querySelector('[data-media-picker-preview]');
	if (!preview) {
		return;
	}

	preview.innerHTML = '<div class="catmin-media-picker-empty text-muted small" data-media-picker-empty>Aucun media selectionne.</div>';
}

function initMediaPicker() {
	const modal = document.getElementById('catmin-media-picker-modal');
	if (!modal) {
		return;
	}

	const endpoint = modal.dataset.pickerEndpoint;
	const itemEndpointTemplate = modal.dataset.itemEndpointTemplate;
	const searchInput = document.getElementById('catmin-media-picker-search');
	const kindSelect = document.getElementById('catmin-media-picker-kind');
	const runButton = document.getElementById('catmin-media-picker-run');
	const prevButton = document.getElementById('catmin-media-picker-prev');
	const nextButton = document.getElementById('catmin-media-picker-next');
	const results = document.getElementById('catmin-media-picker-results');
	const state = document.getElementById('catmin-media-picker-state');
	const pageState = document.getElementById('catmin-media-picker-page');

	if (!endpoint || !itemEndpointTemplate || !searchInput || !kindSelect || !runButton || !prevButton || !nextButton || !results || !state || !pageState) {
		return;
	}

	let activeField = null;
	let currentPage = 1;
	let nextPageUrl = null;
	let prevPageUrl = null;

	const setBusy = (busy, message = '') => {
		runButton.disabled = busy;
		prevButton.disabled = busy || !prevPageUrl;
		nextButton.disabled = busy || !nextPageUrl;
		state.textContent = message;
	};

	const cardTemplate = (item) => {
		const preview = item.preview_url
			? `<img src="${escapeHtml(item.preview_url)}" alt="preview" class="catmin-media-picker-card-thumb">`
			: `<div class="catmin-media-picker-card-fallback">${escapeHtml((item.extension || 'file').toUpperCase())}</div>`;

		return `
			<article class="card h-100 border-0 bg-body-tertiary">
				<div class="card-body d-grid gap-2">
					${preview}
					<div>
						<p class="mb-0 fw-semibold text-truncate">${escapeHtml(item.original_name || 'media')}</p>
						<p class="mb-0 small text-muted">#${escapeHtml(item.id)} · ${escapeHtml(item.size_human || '')}</p>
					</div>
					<button type="button" class="btn btn-sm btn-primary mt-auto" data-media-pick='${escapeHtml(JSON.stringify(item))}'>Choisir</button>
				</div>
			</article>
		`;
	};

	const fetchResults = async (url = null) => {
		const requestUrl = url || `${endpoint}?q=${encodeURIComponent(searchInput.value.trim())}&kind=${encodeURIComponent(kindSelect.value)}&page=${currentPage}`;

		setBusy(true, 'Chargement...');
		try {
			const response = await fetch(requestUrl, {
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json',
				},
			});

			if (!response.ok) {
				throw new Error('Erreur de chargement');
			}

			const payload = await response.json();
			const items = Array.isArray(payload.data) ? payload.data : [];
			const meta = payload.meta || {};
			nextPageUrl = payload.links?.next || null;
			prevPageUrl = payload.links?.prev || null;
			currentPage = Number(meta.current_page || 1);

			if (!items.length) {
				results.innerHTML = '<div class="alert alert-light border mb-0">Aucun media trouve pour ces filtres.</div>';
			} else {
				results.innerHTML = items.map(cardTemplate).join('');
			}

			pageState.textContent = `Page ${meta.current_page || 1} / ${meta.last_page || 1}`;
			setBusy(false, `${meta.total || 0} media(s)`);
		} catch (_error) {
			results.innerHTML = '<div class="alert alert-danger mb-0">Impossible de charger la bibliotheque media.</div>';
			setBusy(false, 'Erreur reseau');
		}
	};

	document.querySelectorAll('[data-media-picker-open]').forEach((button) => {
		button.addEventListener('click', () => {
			activeField = button.closest('[data-media-picker-field]');
			currentPage = 1;
			fetchResults();
		});
	});

	runButton.addEventListener('click', () => {
		currentPage = 1;
		fetchResults();
	});

	searchInput.addEventListener('keydown', (event) => {
		if (event.key === 'Enter') {
			event.preventDefault();
			currentPage = 1;
			fetchResults();
		}
	});

	prevButton.addEventListener('click', () => {
		if (prevPageUrl) {
			fetchResults(prevPageUrl);
		}
	});

	nextButton.addEventListener('click', () => {
		if (nextPageUrl) {
			fetchResults(nextPageUrl);
		}
	});

	results.addEventListener('click', (event) => {
		const button = event.target.closest('[data-media-pick]');
		if (!button || !activeField) {
			return;
		}

		const input = activeField.querySelector('input[type="hidden"]');
		const previewContainer = activeField.querySelector('[data-media-picker-preview]');
		if (!input || !previewContainer) {
			return;
		}

		const item = JSON.parse(button.dataset.mediaPick || '{}');
		input.value = item.id || '';
		renderPickerPreview(previewContainer, item);

		const modalInstance = window.bootstrap?.Modal.getOrCreateInstance(modal);
		modalInstance?.hide();
	});

	document.querySelectorAll('[data-media-picker-clear]').forEach((button) => {
		button.addEventListener('click', () => {
			const field = button.closest('[data-media-picker-field]');
			if (!field) {
				return;
			}

			const input = field.querySelector('input[type="hidden"]');
			if (input) {
				input.value = '';
			}
			resetPickerPreview(field);
		});
	});

	const preloadSelected = async () => {
		const fields = document.querySelectorAll('[data-media-picker-field]');
		for (const field of fields) {
			const input = field.querySelector('input[type="hidden"]');
			const previewContainer = field.querySelector('[data-media-picker-preview]');
			if (!input || !previewContainer || !input.value) {
				continue;
			}

			try {
				const url = itemEndpointTemplate.replace('__ID__', encodeURIComponent(input.value));
				const response = await fetch(url, {
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
					},
				});

				if (!response.ok) {
					continue;
				}

				const payload = await response.json();
				if (payload?.data?.id) {
					renderPickerPreview(previewContainer, payload.data);
				}
			} catch (_error) {
				// Non-blocking preview preload.
			}
		}
	};

	preloadSelected();
}

function initMediaDropzone() {
	const zones = document.querySelectorAll('[data-media-dropzone]');
	if (!zones.length) {
		return;
	}

	zones.forEach((zone) => {
		const input = zone.querySelector('input[type="file"]');
		const browse = zone.querySelector('[data-media-dropzone-browse]');
		const feedback = zone.querySelector('[data-media-dropzone-feedback]');

		if (!input || !feedback) {
			return;
		}

		const updateFeedback = () => {
			const files = Array.from(input.files || []);
			if (!files.length) {
				feedback.textContent = 'Aucun fichier selectionne.';
				return;
			}

			if (files.length === 1) {
				feedback.textContent = `1 fichier pret: ${files[0].name}`;
				return;
			}

			feedback.textContent = `${files.length} fichiers prets.`;
		};

		const preventDefaults = (event) => {
			event.preventDefault();
			event.stopPropagation();
		};

		['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
			zone.addEventListener(eventName, preventDefaults);
		});

		['dragenter', 'dragover'].forEach((eventName) => {
			zone.addEventListener(eventName, () => zone.classList.add('is-dragover'));
		});

		['dragleave', 'drop'].forEach((eventName) => {
			zone.addEventListener(eventName, () => zone.classList.remove('is-dragover'));
		});

		zone.addEventListener('drop', (event) => {
			if (!event.dataTransfer?.files?.length) {
				return;
			}

			input.files = event.dataTransfer.files;
			updateFeedback();
		});

		browse?.addEventListener('click', (event) => {
			event.preventDefault();
			input.click();
		});

		zone.addEventListener('click', (event) => {
			if (event.target === input) {
				return;
			}
			input.click();
		});

		input.addEventListener('change', updateFeedback);
	});
}

function initNotifications() {
	const notifications = document.querySelectorAll('[data-catmin-notification]');
	if (!notifications.length) {
		return;
	}

	const closeNotification = (notification) => {
		if (!notification || notification.dataset.catminClosed === '1') {
			return;
		}

		notification.dataset.catminClosed = '1';
		notification.classList.add('catmin-notify--closing');
		notification.addEventListener('animationend', () => notification.remove(), { once: true });
	};

	notifications.forEach((notification) => {
		const closeButton = notification.querySelector('[data-catmin-notification-close]');
		const timerBar = notification.querySelector('[data-catmin-notification-timer]');
		const timeout = Number.parseInt(notification.dataset.timeout || '0', 10);

		closeButton?.addEventListener('click', () => closeNotification(notification));

		if (timerBar && timeout > 0) {
			timerBar.style.transition = `transform ${timeout}ms linear`;
			requestAnimationFrame(() => {
				timerBar.style.transform = 'scaleX(0)';
			});

			window.setTimeout(() => {
				closeNotification(notification);
			}, timeout);
		}
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initMediaDropzone();
	initMediaPicker();
	initNotifications();
});
