// Account Settings JS

document.addEventListener('DOMContentLoaded', () => {
  bindEvents();
  loadAccount();
});

function bindEvents() {
  const editBtn = document.getElementById('editBtn');
  const saveBtn = document.getElementById('saveBtn');
  editBtn.addEventListener('click', enableEditing);
  saveBtn.addEventListener('click', saveAccount);
}

function enableEditing() {
  ['nameInput','emailInput','passwordInput','saveBtn'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.disabled = false;
  });
}

async function loadAccount() {
  try {
    const fd = new FormData();
    fd.append('action','get_account');
    const res = await fetch('AccountSettings.php',{method:'POST',body:fd});
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'Failed to load account');
    const {name,email,type} = json.data;
    document.getElementById('infoName').textContent = name;
    document.getElementById('infoEmail').textContent = email;
    document.getElementById('infoType').textContent = type;
    document.getElementById('nameInput').value = name;
    document.getElementById('emailInput').value = email;
    document.getElementById('passwordInput').value = '';
  } catch (e) {
    notify(e.message,'error');
  }
}

async function saveAccount() {
  try {
    const name = document.getElementById('nameInput').value.trim();
    const email = document.getElementById('emailInput').value.trim();
    const password = document.getElementById('passwordInput').value;
    if (!name || !email) {
      notify('Name and Email are required','error');
      return;
    }
    const fd = new FormData();
    fd.append('action','update_account');
    fd.append('name',name);
    fd.append('email',email);
    fd.append('password',password);
    const res = await fetch('AccountSettings.php',{method:'POST',body:fd});
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'Update failed');
    // Refresh info
    document.getElementById('infoName').textContent = name;
    document.getElementById('infoEmail').textContent = email;
    // Disable again
    ['nameInput','emailInput','passwordInput','saveBtn'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.disabled = true;
    });
    document.getElementById('passwordInput').value = '';
    notify('Account updated','success');
  } catch (e) {
    notify(e.message,'error');
  }
}

function notify(message,type='info'){
  const n=document.createElement('div');
  n.style.cssText=`position:fixed;top:20px;right:20px;padding:12px 18px;background:${type==='success'?'#48bb78':type==='error'?'#f56565':'#4299e1'};color:white;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:10000;`;
  n.textContent=message;document.body.appendChild(n);setTimeout(()=>{n.remove()},2500);
}
