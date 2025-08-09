
document.addEventListener('DOMContentLoaded',()=>{
  const form=document.querySelector('form');
  if(!form)return;
  form.addEventListener('submit',e=>{
    const btn=form.querySelector('button');
    btn.disabled=true;btn.textContent='Sending...';
  });
});
