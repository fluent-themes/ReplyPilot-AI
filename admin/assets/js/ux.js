/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/
(function(){
  // Minimal toast (only defines if not present)
  if (typeof window.toast !== 'function') {
    window.toast = function(msg, ms){ 
      var t = document.getElementById('toast'); if(!t) return;
      t.textContent = msg; t.classList.add('show');
      setTimeout(function(){ t.classList.remove('show'); }, ms || 2200);
    };
  }

  // Modal: open via [data-modal-open="#id"], close via [data-modal-close] or backdrop click or Esc.
  function getTarget(sel){ try { return document.querySelector(sel); } catch(e){ return null; } }
  document.addEventListener('click', function(e){
    var openBtn = e.target.closest('[data-modal-open]');
    if (openBtn){
      var sel = openBtn.getAttribute('data-modal-open');
      var m = getTarget(sel);
      if(m){ m.classList.add('show'); m.setAttribute('aria-hidden', 'false'); }
    }
    var closeBtn = e.target.closest('[data-modal-close]');
    if(closeBtn){
      var mb = closeBtn.closest('.modal-backdrop');
      if(mb){ mb.classList.remove('show'); mb.setAttribute('aria-hidden', 'true'); }
    }
    var backdrop = e.target.classList.contains('modal-backdrop') ? e.target : null;
    if(backdrop && e.target === backdrop){ backdrop.classList.remove('show'); backdrop.setAttribute('aria-hidden', 'true'); }
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
      document.querySelectorAll('.modal-backdrop.show').forEach(function(mb){
        mb.classList.remove('show'); mb.setAttribute('aria-hidden', 'true');
      });
    }
  });

  // Sticky action bar: data-sticky-for="#formId" -> show when any input changes
  function setupSticky(el){
    var formSel = el.getAttribute('data-sticky-for');
    var form = getTarget(formSel);
    if(!form) return;
    var show = function(){ el.classList.add('show'); };
    var hide = function(){ el.classList.remove('show'); };
    var dirty = false;
    form.addEventListener('input', function(){ dirty = true; show(); });
    form.addEventListener('change', function(){ dirty = true; show(); });
    form.addEventListener('reset', function(){ dirty = false; hide(); });
    // If form submits, hide
    form.addEventListener('submit', function(){ hide(); });
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.sticky-actions[data-sticky-for]').forEach(setupSticky);
  });
})(); 
