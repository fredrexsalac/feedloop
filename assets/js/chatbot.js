(function(){
  let open = false;
  const STORAGE_KEY = 'fl_chat_history_landing';
  const state = { messages: [], sending: false, lastSentAt: 0 };
  const bubble = document.createElement('div');
  bubble.className = 'fl-chatbot-bubble';
  bubble.innerHTML = '<img src="assets/chatbot/chatbot-icon.png" alt="Chatbot">';

  const panel = document.createElement('div');
  panel.className = 'fl-chatbot-panel';
  panel.innerHTML = `
    <div class="fl-chatbot-header">
      <div class="fl-chatbot-title"><i class="fas fa-robot me-2"></i>FeedLoop Assistant</div>
      <button class="btn btn-sm btn-light" id="flChatClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="fl-chatbot-body" id="flChatBody"></div>
    <div class="fl-chatbot-footer-note">Private to you • Uses on-device model when available</div>
    <div class="fl-chatbot-input">
      <textarea id="flChatInput" placeholder="Ask about this page, forms, or your account..."></textarea>
      <button class="btn btn-primary" id="flChatSend"><i class="fas fa-paper-plane"></i></button>
    </div>
  `;

  function ensureAttached(){
    if (!document.querySelector('.fl-chatbot-bubble')) document.body.appendChild(bubble);
    if (!document.querySelector('.fl-chatbot-panel')) document.body.appendChild(panel);
  }

  function scrollToBottom(){
    const body = document.getElementById('flChatBody');
    body && (body.scrollTop = body.scrollHeight);
  }

  function renderMessage(role, text){
    const body = document.getElementById('flChatBody');
    if (!body) return;
    const wrap = document.createElement('div');
    wrap.className = 'fl-chat-msg ' + (role === 'user' ? 'user' : 'assistant');
    wrap.innerHTML = `<div class="bubble">${escapeHtml(text)}</div>`;
    body.appendChild(wrap);
    scrollToBottom();
    persist();
  }

  function escapeHtml(s){
    return (s||'').replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]);});
  }

  function collectContext(){
    const title = document.title;
    const url = location.href;
    const metas = Array.from(document.querySelectorAll('meta[name], meta[property]')).map(m=>({n:m.getAttribute('name')||m.getAttribute('property'),c:m.getAttribute('content')}));
    const headings = Array.from(document.querySelectorAll('h1,h2,h3')).slice(0,20).map(h=>h.innerText.trim());
    const nav = Array.from(document.querySelectorAll('nav a')).slice(0,30).map(a=>({t:a.innerText.trim(),href:a.href}));
    const forms = Array.from(document.querySelectorAll('form')).map(f=>({id:f.id,action:f.action,inputs:f.querySelectorAll('input,select,textarea').length}));
    const stats = Array.from(document.querySelectorAll('.card, .stat, .badge')).slice(0,30).map(el=>el.innerText.trim()).filter(Boolean);
    const selection = String(window.getSelection && window.getSelection());
    // Visible page text (grounding): collapse whitespace and cap length
    let pageText = '';
    let pageTextFiltered = '';
    let sections = {};
    try {
      pageText = (document.body.innerText || '')
        .replace(/\s+/g,' ')
        .trim();
      if (pageText.length > 8000) pageText = pageText.slice(0, 8000);
      // Build filtered clone excluding noisy UI
      const clone = document.body.cloneNode(true);
      const selectorsToRemove = [
        'nav', 'header', 'footer', 'script', 'style', 'noscript', 'svg', 'img',
        'button', 'a', '.btn', '.navbar', '.dropdown-menu', '.hero-cta', '.modal', '.toast'
      ];
      clone.querySelectorAll(selectorsToRemove.join(',')).forEach(el=>el.remove());
      pageTextFiltered = (clone.innerText || '').replace(/\s+/g,' ').trim();
      if (pageTextFiltered.length > 8000) pageTextFiltered = pageTextFiltered.slice(0,8000);
      // Prefer key sections when present
      ['about','features','faq'].forEach(id=>{
        const el = document.getElementById(id);
        if (el) {
          const t = el.innerText.replace(/\s+/g,' ').trim();
          if (t) sections[id] = t.slice(0, 4000);
        }
      });
    } catch(e) {}
    return { title, url, metas, headings, nav, forms, stats, selection, page_text: pageText, page_text_filtered: pageTextFiltered, sections };
  }

  async function send(){
    if (state.sending) return; // avoid parallel sends
    const now = Date.now();
    if (now - state.lastSentAt < 500) return; // basic rate limit
    const input = document.getElementById('flChatInput');
    const text = (input.value || '').trim();
    if (!text) return;
    input.value = '';
    state.messages.push({role:'user', content:text});
    renderMessage('user', text);

    const context = collectContext();
    showTyping(true);
    state.sending = true; state.lastSentAt = now;
    try {
      const res = await fetch('api/chatbot/query.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ messages: state.messages.slice(-12), context })
      });
      const data = await res.json();
      const reply = (data && data.reply) ? data.reply : 'I could not generate a response.';
      state.messages.push({role:'assistant', content: reply});
      renderMessage('assistant', reply);
    } catch(err) {
      renderMessage('assistant', 'Network error. Please try again.');
    } finally {
      showTyping(false);
      state.sending = false;
    }
  }

  function toggle(){
    open = !open;
    panel.style.display = open ? 'flex' : 'none';
    if (open && state.messages.length === 0) {
      const welcome = 'Hi! I can analyze this page and help with FeedLoop. What would you like to know?';
      state.messages.push({ role: 'assistant', content: welcome });
      renderMessage('assistant', welcome);
      renderSuggestions();
    }
    // Do not restore old history on reopen; session resets per refresh
  }

  // History restore removed to ensure fresh session each refresh

  function renderSuggestions(){
    const body = document.getElementById('flChatBody');
    if (!body) return;
    const box = document.createElement('div');
    box.className = 'fl-chat-msg assistant';
    const suggestions = [
      'What can I do on this page?',
      'How do I submit a form?',
      'What’s different for users vs admins?',
      'How is my data protected?',
    ];
    box.innerHTML = `<div class="bubble"><strong>Quick suggestions</strong><br>${suggestions.map(s=>`<button class="btn btn-sm btn-outline-primary m-1" data-q="${s.replace(/"/g,'&quot;')}">${s}</button>`).join('')}</div>`;
    body.appendChild(box);
    box.querySelectorAll('button').forEach(btn=>btn.addEventListener('click', ()=>{
      const v = btn.getAttribute('data-q');
      const input = document.getElementById('flChatInput');
      input.value = v;
      send();
    }));
    scrollToBottom();
  }

  function showTyping(on){
    const body = document.getElementById('flChatBody');
    if (!body) return;
    let t = document.getElementById('flTyping');
    if (on){
      if (!t){
        t = document.createElement('div');
        t.id = 'flTyping';
        t.className = 'fl-chat-msg assistant';
        t.innerHTML = '<div class="bubble"><span class="spinner-border spinner-border-sm me-2"></span>Thinking…</div>';
        body.appendChild(t);
      }
      scrollToBottom();
    } else if (t) {
      t.remove();
    }
  }

  function persist(){
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state.messages.slice(-50))); } catch(e) {}
  }

  function loadPersisted(){
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw){ state.messages = JSON.parse(raw) || []; }
    } catch(e) {}
  }

  document.addEventListener('DOMContentLoaded', function(){
    ensureAttached();
    // Reset chat on refresh
    try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    bubble.addEventListener('click', toggle);
    panel.querySelector('#flChatClose').addEventListener('click', toggle);
    panel.querySelector('#flChatSend').addEventListener('click', send);
    panel.querySelector('#flChatInput').addEventListener('keydown', function(e){
      if (e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); send(); }
    });
  });
})();