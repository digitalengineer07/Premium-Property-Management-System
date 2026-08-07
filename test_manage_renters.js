
    const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
    
    // Custom Success Modal Logic
    let successCallback = null;
    function showSuccessMessage(msg, callback) {
        document.getElementById('successMessageText').textContent = msg;
        const modal = document.getElementById('successModal');
        const panel = modal.querySelector('.panel');
        modal.style.display = 'flex';
        // Trigger reflow
        void modal.offsetWidth;
        modal.style.opacity = '1';
        panel.style.transform = 'scale(1)';
        successCallback = callback;
    }

    function closeSuccessModal() {
        const modal = document.getElementById('successModal');
        const panel = modal.querySelector('.panel');
        modal.style.opacity = '0';
        panel.style.transform = 'scale(0.8)';
        setTimeout(() => {
            modal.style.display = 'none';
            if (successCallback) successCallback();
        }, 300);
    }

    document.getElementById('renterFilter')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('#renterTable tr');
        rows.forEach(row => {
            let name = row.innerText.toLowerCase();
            row.style.display = name.includes(term) ? '' : 'none';
        });
    });

    let currentResetId = null;

    function resetPassword(id, name) {
        currentResetId = id;
        document.getElementById('resetUserId').value = id;
        document.getElementById('resetUsername').textContent = `Set a new password for ${name}`;
        document.getElementById('newPasswordInput').value = "123456";
        document.getElementById('passwordModal').style.display = 'flex';
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
        currentResetId = null;
    }

    async function submitPasswordReset() {
        const id = document.getElementById('resetUserId').value;
        const newPass = document.getElementById('newPasswordInput').value;
        
        if (!newPass) {
            alert("Password cannot be empty");
            return;
        }

        try {
            const res = await fetch('reset-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&new_password=${encodeURIComponent(newPass)}&csrf=${CSRF_TOKEN}`
            });
            const data = await res.json();
            if (data.success) {
                alert("Password updated successfully!");
                closePasswordModal();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert("Network error occurred.");
        }
    }

    function moveOutRenter(id, name) {
        document.getElementById('moveOutUserId').value = id;
        document.getElementById('moveOutUsername').textContent = `Archive resident ${name}?`;
        document.getElementById('moveOutDateInput').value = new Date().toISOString().split('T')[0];
        document.getElementById('moveOutModal').style.display = 'flex';
    }

    function closeMoveOutModal() {
        document.getElementById('moveOutModal').style.display = 'none';
    }

    async function submitMoveOut() {
        const id = document.getElementById('moveOutUserId').value;
        const moveDate = document.getElementById('moveOutDateInput').value;
        
        if (!moveDate) {
            alert("Move Out Date is required");
            return;
        }

        try {
            const res = await fetch('ajax_move_out.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${id}&move_out_date=${encodeURIComponent(moveDate)}`
            });
            const data = await res.json();
            if (data.success) {
                closeMoveOutModal();
                showSuccessMessage(data.message, () => {
                    location.reload();
                });
            } else {
                alert(data.error);
            }
        } catch (e) {
            alert("Network error occurred.");
        }
    }

    async function deleteRenter(id, name) {
        if (!confirm(`Are you sure you want to PERMANENTLY delete ${name}? This will also delete all their bills and cannot be undone.`)) return;
        
        try {
            const res = await fetch('delete-renter.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&csrf=${CSRF_TOKEN}`
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                const alert = document.getElementById('deleteAlert');
                alert.textContent = data.message;
                alert.style.display = 'block';
            }
        } catch (e) {
            alert("Network error occurred.");
        }
    }

    document.querySelectorAll('.pwd-toggle').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if(input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            }
        });
    });
