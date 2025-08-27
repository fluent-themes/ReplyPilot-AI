/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/
document.addEventListener('DOMContentLoaded',()=>{
  const form=document.querySelector('form');
  if(!form)return;
  form.addEventListener('submit',e=>{
    const btn=form.querySelector('button');
    btn.disabled=true;btn.textContent='Sending...';
  });
});
