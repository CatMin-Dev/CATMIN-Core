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
(el.tagName !== 'SELECT') {
ction updateToolbarState(root) {
clearToolbarState(root);
STATEFUL_CMDS.forEach((cmd) => {
(document.queryCommandState(cmd)) {
st btn = root.querySelector(`[data-editor-cmd="${cmd}"]`);
(btn) {
.classList.add('active');
ction showLinkPopup(root, savedRange) {
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
 = true;
ge = null;
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
(button.tagName === 'SELECT') {
.addEventListener('change', () => {
vas(canvas);
.dataset.editorCmd, `<${button.value}>`);
ncCanvasToInput(canvas, input);
.selectedIndex = 0;
;
.addEventListener('click', () => {
vas(canvas);
st cmd = button.dataset.editorCmd;
st val = button.dataset.editorValue;
(cmd === 'formatBlock' && val) {
`<${val}>`);
else {
val || null);
ncCanvasToInput(canvas, input);
Link action
root.querySelectorAll('[data-editor-action="link"]').forEach((button) => {
.addEventListener('click', () => {
st sel = window.getSelection();
savedRange = null;
(sel && sel.rangeCount > 0) {
ge = sel.getRangeAt(0).cloneRange();
kPopup(root, savedRange);
st linkApplyBtn = root.querySelector('[data-editor-link-apply]');
const linkCancelBtn = root.querySelector('[data-editor-link-cancel]');
const linkInput = root.querySelector('[data-editor-link-input]');

if (linkApplyBtn && linkInput) {
st applyLink = () => {
st popup = root.querySelector('[data-editor-link-popup]');
st url = linkInput.value.trim();
(!url) {
kPopup(root);
;
st savedRange = popup?._savedRange;
(savedRange) {
st sel = window.getSelection();
ges();
ge(savedRange);
vas(canvas);
k', url);
ncCanvasToInput(canvas, input);
kPopup(root);
kApplyBtn.addEventListener('click', applyLink);
kInput.addEventListener('keydown', (e) => {
(e.key === 'Enter') {
tDefault();
Link();
(e.key === 'Escape') {
kPopup(root);
(linkCancelBtn) {
kCancelBtn.addEventListener('click', () => hideLinkPopup(root));
}

// Panel toggle
root.querySelectorAll('[data-editor-action="toggle-panel"]').forEach((button) => {
.addEventListener('click', () => {
(!panel) return;
el.hidden = !panel.hidden;
-editor--panel-open', !panel.hidden);
Panel tabs (Snippets / Blocs)
const tabButtons = root.querySelectorAll('[data-editor-tab]');
const tabPanes = root.querySelectorAll('[data-editor-pane]');
if (tabButtons.length && tabPanes.length) {
s.forEach((button) => {
.addEventListener('click', () => {
st target = button.dataset.editorTab;
(!target) return;

s.forEach((btn) => btn.classList.remove('active'));
.classList.add('active');

es.forEach((pane) => {
e.hidden = pane.dataset.editorPane !== target;
Color picker
root.querySelectorAll('[data-editor-action="color-picker"]').forEach((button) => {
st cmd = button.dataset.editorColorCmd;
st pickerEl = root.querySelector(`[data-editor-color-picker="${cmd}"]`);
(!pickerEl) return;

=> {
st cbtn = document.createElement('button');
.type = 'button';
.style.backgroundColor = color;
.addEventListener('click', (e) => {
tDefault();
vas(canvas);
color);
ncCanvasToInput(canvas, input);
 = true;
dChild(cbtn);
.addEventListener('click', (e) => {
tDefault();
 = !pickerEl.hidden;
Live preview
const previewPane = root.querySelector('[data-editor-preview-pane]');
const previewCanvas = root.querySelector('[data-editor-preview-canvas]');
if (previewPane && previewCanvas) {
st updatePreview = () => {
vas.innerHTML = canvas.innerHTML || '<p style="color:#999;">Aucun contenu</p>';
vas.addEventListener('input', updatePreview);
vas.addEventListener('blur', updatePreview);
uerySelectorAll('[data-editor-action="toggle-preview"]').forEach((button) => {
.addEventListener('click', () => {
st isHidden = previewPane.hidden;
e.hidden = !isHidden;
(!isHidden) updatePreview();
Insert HTML
root.querySelectorAll('[data-editor-action="insert-html"]').forEach((button) => {
.addEventListener('click', () => {
vas(canvas);
sertHtmlAtCursor(button.dataset.editorHtml || '');
ncCanvasToInput(canvas, input);
Media picker
root.querySelectorAll('[data-editor-action="media-picker"]').forEach((button) => {
.addEventListener('click', () => {
st modalElement = document.getElementById('catmin-media-picker-modal');
(!modalElement || !window.bootstrap?.Modal) {
dow.alert('Le picker media est indísponible sur cette page.');
;
tsMediaInsert = true;
= window.bootstrap.Modal.getOrCreateInstance(modalElement);
Toolbar state update on selection change
document.addEventListener('selectionchange', () => {
(canvas.contains(document.activeElement) || document.activeElement === canvas) {
vas.addEventListener('keyup', () => updateToolbarState(root));
canvas.addEventListener('mouseup', () => updateToolbarState(root));

// Sync on input/blur/submit
canvas.addEventListener('input', () => syncCanvasToInput(canvas, input));
canvas.addEventListener('blur', () => syncCanvasToInput(canvas, input));
form?.addEventListener('submit', () => syncCanvasToInput(canvas, input));

// Media selected event
window.addEventListener('catmin:media-selected', (event) => {
(!wantsMediaInsert) return;

st media = event.detail || {};
st previewUrl = media.preview_url || '';
st fallbackLabel = media.original_name || 'media';

vas(canvas);
(previewUrl) {
sertHtmlAtCursor(`<img src="${previewUrl}" alt="${fallbackLabel}">`);
else {
sertHtmlAtCursor(`<a href="#">${fallbackLabel}</a>`);
ncCanvasToInput(canvas, input);

tsMediaInsert = false;
ncCanvasToInput(canvas, input);
}

export function initCatminEditor() {
const fields = document.querySelectorAll('[data-catmin-editor-field][data-enabled="1"]');
if (!fields.length) return;

fields.forEach((field) => initEditorInstance(field));
}
