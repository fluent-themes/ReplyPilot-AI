/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/
document.addEventListener('DOMContentLoaded',()=>{
  const form=document.querySelector('form');
  if(!form)return;
  
  // Check if form should use AJAX (has data-ajax attribute)
  const useAjax = form.dataset.ajax === 'true';
  
  form.addEventListener('submit', async (e)=>{
    const btn=form.querySelector('button');
    if(btn){
      btn.disabled=true;
      btn.textContent='Sending...';
    }
    
    // If AJAX mode is enabled
    if(useAjax && window.fetch) {
      e.preventDefault();
      
      try {
        const formData = new FormData(form);
        const response = await fetch('/ajax-submit.php', {
          method: 'POST',
          body: formData
        });
        
        const data = await response.json();
        
        if(data.ok) {
          // Success handling
          if(data.ai_reply) {
            const resultDiv = document.getElementById('ajax-result');
            if(resultDiv) {
              resultDiv.innerHTML = `<div class="alert alert-success">${data.ai_reply}</div>`;
              resultDiv.style.display = 'block';
            }
          }
          if(data.ticket_url) {
            window.location.href = data.ticket_url;
          }
        } else {
          // Error handling
          const errorDiv = document.getElementById('ajax-error');
          if(errorDiv) {
            errorDiv.innerHTML = `<div class="alert alert-danger">${data.error?.message || 'An error occurred'}</div>`;
            errorDiv.style.display = 'block';
          }
        }
      } catch(err) {
        console.error('AJAX error:', err);
        const errorDiv = document.getElementById('ajax-error');
        if(errorDiv) {
          errorDiv.innerHTML = `<div class="alert alert-danger">Network error. Please try again.</div>`;
          errorDiv.style.display = 'block';
        }
      } finally {
        if(btn) {
          btn.disabled = false;
          btn.textContent = 'Submit';
        }
      }
    }
    // Otherwise, let form submit normally
  });
});
