/**
 * WYSIWYG Editor Module
 * Uses contenteditable with selection preservation
 */

const editorState = {};
let savedRange = null;

// Prevent toolbar buttons from stealing focus
document.addEventListener('mousedown', function(e) {
    const btn = e.target.closest('.tb-btn, .color-pick');
    if (btn) e.preventDefault();
});

function saveSelection() {
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
}

function restoreSelection() {
    if (savedRange) {
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
    }
}

function initEditor(editorId, hiddenId, sourceId) {
    const editor = document.getElementById(editorId);
    const hidden = document.getElementById(hiddenId);
    
    editorState[editorId] = { isSource: false, editorId, hiddenId, sourceId };

    // Save selection on every interaction
    editor.addEventListener('mouseup', saveSelection);
    editor.addEventListener('keyup', saveSelection);
    editor.addEventListener('focus', saveSelection);

    // Sync to hidden on every input
    editor.addEventListener('input', function() {
        hidden.value = editor.innerHTML;
        saveSelection();
        updateWordCount(editorId);
    });

    // Clean paste
    editor.addEventListener('paste', function(e) {
        e.preventDefault();
        const html = (e.clipboardData || window.clipboardData).getData('text/html');
        if (html) {
            document.execCommand('insertHTML', false, sanitizePaste(html));
        } else {
            const plain = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, plain);
        }
        hidden.value = editor.innerHTML;
    });

    // Initial sync
    hidden.value = editor.innerHTML;
    updateWordCount(editorId);
}

function sanitizePaste(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    tmp.querySelectorAll('script,style,meta,link').forEach(el => el.remove());
    tmp.querySelectorAll('*').forEach(el => {
        el.removeAttribute('class');
        el.removeAttribute('id');
    });
    return tmp.innerHTML;
}

function updateWordCount(editorId) {
    if (editorId === 'content-visual') {
        const editor = document.getElementById(editorId);
        const text = editor.innerText || '';
        const count = text.trim() ? text.trim().split(/\s+/).length : 0;
        const wc = document.getElementById('word-count');
        if (wc) wc.innerText = count;
    }
}

function execCmd(editorId, command, value) {
    const editor = document.getElementById(editorId);
    restoreSelection();
    editor.focus();
    document.execCommand(command, false, value || null);
    editorState[editorId] && (document.getElementById(editorState[editorId].hiddenId).value = editor.innerHTML);
    saveSelection();
}

function execHeading(editorId) {
    const editor = document.getElementById(editorId);
    restoreSelection();
    editor.focus();

    const sel = window.getSelection();
    if (!sel.rangeCount) return;

    let node = sel.anchorNode;
    if (node.nodeType === 3) node = node.parentElement;
    while (node && node !== editor && !['H2','H3','H4','P','DIV'].includes(node.tagName)) {
        node = node.parentElement;
    }
    const current = (node && node !== editor) ? node.tagName : '';

    let next = 'H2';
    if (current === 'H2') next = 'H3';
    else if (current === 'H3') next = 'H4';
    else if (current === 'H4') next = 'P';

    document.execCommand('formatBlock', false, '<' + next + '>');
    document.getElementById(editorState[editorId].hiddenId).value = editor.innerHTML;
    saveSelection();
}

function execForeColor(editorId) {
    const color = document.getElementById('fc-' + editorId).value;
    execCmd(editorId, 'foreColor', color);
}

function execBackColor(editorId) {
    const color = document.getElementById('bc-' + editorId).value;
    execCmd(editorId, 'hiliteColor', color);
}

function execLink(editorId) {
    const editor = document.getElementById(editorId);
    restoreSelection();
    editor.focus();

    const url = prompt('Nhập đường dẫn URL (vd: https://hvu.edu.vn):');
    if (url) {
        document.execCommand('createLink', false, url);
        const sel = window.getSelection();
        if (sel.anchorNode) {
            let a = sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentElement : sel.anchorNode;
            a = a.closest ? a.closest('a') : a;
            if (a && a.tagName === 'A') a.target = '_blank';
        }
    }
    document.getElementById(editorState[editorId].hiddenId).value = editor.innerHTML;
    saveSelection();
}

function execInsertImage(editorId, url) {
    const editor = document.getElementById(editorId);
    restoreSelection();
    editor.focus();
    document.execCommand('insertHTML', false,
        '<img src="' + url + '" alt="Ảnh bài viết" style="max-width:100%;height:auto;margin:8px 0;border:1px solid #ccc;">');
    document.getElementById(editorState[editorId].hiddenId).value = editor.innerHTML;
    saveSelection();
}

function toggleSource(editorId) {
    const state = editorState[editorId];
    const editor = document.getElementById(state.editorId);
    const source = document.getElementById(state.sourceId);
    const hidden = document.getElementById(state.hiddenId);
    const btn = document.getElementById('src-btn-' + editorId);

    if (!state.isSource) {
        source.value = editor.innerHTML;
        editor.style.display = 'none';
        source.style.display = 'block';
        source.focus();
        if (btn) { btn.classList.add('bg-blue-600'); btn.querySelector('i').classList.replace('text-[#555]', 'text-white'); }
    } else {
        editor.innerHTML = source.value;
        hidden.value = source.value;
        source.style.display = 'none';
        editor.style.display = 'block';
        editor.focus();
        if (btn) { btn.classList.remove('bg-blue-600'); btn.querySelector('i').classList.replace('text-white', 'text-[#555]'); }
    }
    state.isSource = !state.isSource;
}
