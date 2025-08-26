/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/
(function(){
  function toast(msg, ms=2200){
    const t = document.getElementById('toast');
    if(!t) return;
    t.textContent = msg; t.classList.add('show');
    setTimeout(()=> t.classList.remove('show'), ms);
  }

  // Expand / collapse
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.js-toggle');
    if(btn){
      const row = btn.closest('tr');
      const id = row?.dataset.id;
      const panel = document.getElementById('d-'+id);
      if(panel){
        panel.classList.toggle('show');
        btn.textContent = panel.classList.contains('show') ? 'Hide' : 'View';
      }
    }

    const copyBtn = e.target.closest('.js-copy');
    if(copyBtn){
      const targetId = copyBtn.getAttribute('data-target');
      const panel = document.getElementById(targetId);
      const txt = panel?.querySelector('textarea[name=\"ai_reply\"]')?.value || '';
      if(navigator.clipboard){
        navigator.clipboard.writeText(txt).then(()=> toast('Copied reply'));
      } else {
        const ta = document.createElement('textarea');
        ta.value = txt; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); toast('Copied reply'); } catch(e){}
        document.body.removeChild(ta);
      }
    }
  });

  // Show status from query param
  const params = new URLSearchParams(location.search);
  const status = params.get('status');
  if(status === 'sent') toast('Email sent');
  if(status === 'failed') toast('Email failed');
  if(status === 'updated') toast('Saved');
})();