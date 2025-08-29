/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/
(function(){
  function initTabs(){
    document.querySelectorAll('.tabs').forEach(function(tabs){
      var nav = tabs.querySelector('.tab-nav');
      var panels = tabs.querySelectorAll('.tab-panels .tab-panel');
      if(!nav || !panels.length) return;

      // set roles if not present
      nav.setAttribute('role', 'tablist');
      panels.forEach(function(p){ p.setAttribute('role', 'tabpanel'); });

      var links = nav.querySelectorAll('[data-tab-target], a[href^="#"]');

      function idForLink(a){
        if(!a) return null;
        return a.getAttribute('data-tab-target') || (a.getAttribute('href') || '').replace(/^.*#/, '');
      }

      function activate(id){
        if(!id) return;
        panels.forEach(function(p){
          var show = (p.id === id);
          p.hidden = !show;
          p.setAttribute('aria-hidden', show ? 'false' : 'true');
        });
        links.forEach(function(a){
          var isActive = idForLink(a) === id;
          a.classList.toggle('active', isActive);
          a.setAttribute('aria-selected', isActive ? 'true' : 'false');
          a.setAttribute('role', 'tab');
        });
      }

      var initial = null;
      links.forEach(function(a){
        if(a.classList.contains('active') && !initial) initial = idForLink(a);
      });
      if(!initial && panels[0]) initial = panels[0].id;
      activate(initial);

      nav.addEventListener('click', function(e){
        var a = e.target.closest('[data-tab-target], a[href^="#"]');
        if(!a) return;
        e.preventDefault();
        var id = idForLink(a);
        if(!id) return;
        activate(id);
        try {
          if(history && history.replaceState){
            var hash = '#' + id;
            var url = location.pathname + location.search + hash;
            history.replaceState(null, '', url);
          }
        } catch(e) { console.error('Failed to update URL hash:', e); }
      });
    });
  }

  function initDropdowns(){
    var openDD = null;

    function setOpen(dd, open){
      if(!dd) return;
      dd.classList.toggle('open', !!open);
      dd.setAttribute('aria-expanded', open ? 'true' : 'false');
      var menu = dd.querySelector('.dropdown-menu');
      if(menu){ menu.hidden = !open; }
    }

    document.addEventListener('click', function(e){
      var toggle = e.target.closest('.dropdown-toggle');
      if(toggle){
        var dd = toggle.closest('.dropdown');
        if(dd){
          var willOpen = !dd.classList.contains('open');
          if(openDD && openDD !== dd){ setOpen(openDD, false); }
          setOpen(dd, willOpen);
          openDD = willOpen ? dd : null;
        }
        return;
      }
      // clicked elsewhere
      if(openDD && !e.target.closest('.dropdown')){
        setOpen(openDD, false);
        openDD = null;
      }
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape' && openDD){
        setOpen(openDD, false);
        openDD = null;
      }
    });
  }

  function init(){
    initTabs();
    initDropdowns();
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
