</div> <div id="glass-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 id="modal-title">Confirm Action</h3>
            <p id="modal-desc">Are you sure you want to proceed?</p>
            
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button id="btn-confirm" class="btn-confirm">Yes, Proceed</button>
            </div>
        </div>
    </div>

    <style>
        /* Overlay: Darkens the background */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        /* The Box: Glass & Pop */
        .modal-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 24px;
            width: 360px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.8);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-overlay.active .modal-box {
            transform: scale(1);
        }

        /* Icon & Text */
        .modal-icon {
            font-size: 32px;
            color: var(--warning-orange); /* Assumes CSS variables from top layout */
            margin-bottom: 15px;
        }
        .modal-box h3 { margin: 0 0 10px 0; color: #1D1D1F; }
        .modal-box p { margin: 0 0 25px 0; color: #86868B; font-size: 14px; }

        /* Buttons */
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        
        .btn-cancel {
            background: rgba(0,0,0,0.05);
            color: #1D1D1F;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-confirm {
            background: #FF3B30; /* Danger Red */
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
        }
    </style>

    <script>
        // ==========================================
        // 1. GLASS MODAL LOGIC (Existing)
        // ==========================================
        let pendingForm = null;

        document.addEventListener("DOMContentLoaded", function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + "s";
            });
        });

        function confirmAction(event, message, isDanger = true) {
            event.preventDefault(); 
            pendingForm = event.target.closest('form'); 
            document.getElementById('modal-desc').innerText = message;
            document.getElementById('glass-modal').classList.add('active');
        }

        document.getElementById('btn-confirm').addEventListener('click', function() {
            if(pendingForm) { pendingForm.submit(); }
            closeModal();
        });

        function closeModal() {
            document.getElementById('glass-modal').classList.remove('active');
            pendingForm = null;
        }


        // ==========================================
        // 2. GLOBAL EVENT-DRIVEN NOTIFICATIONS (New)
        // ==========================================
        let globalLastNotifId = 0;
        
        function globalPollNotifications() {
            fetch('check_notifications.php')
            .then(response => {
                // If the user isn't logged in or the file is missing, exit quietly
                if (!response.ok) throw new Error("API not reachable");
                return response.json();
            })
            .then(data => {
                // Safely update badge if it exists in the top navbar
                const badge = document.getElementById('notif-badge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.style.display = 'flex';
                        badge.innerText = data.unread_count;
                    } else {
                        badge.style.display = 'none';
                    }
                }
                
                // Pop a Toast if there's a new, unread notification
                if (data.latest && data.latest.Notif_ID > globalLastNotifId) {
                    globalLastNotifId = data.latest.Notif_ID;
                    
                    let toastIcon = 'info';
                    if (data.latest.Type === 'Warning' || data.latest.Type === 'Conflict') toastIcon = 'error';
                    if (data.latest.Type === 'Deadline') toastIcon = 'warning';
                    if (data.latest.Type === 'Assignment') toastIcon = 'success';

                    // Ensure SweetAlert2 is loaded before trying to fire it
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: toastIcon,
                            title: data.latest.Message,
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true
                        });
                    }
                }
            })
            .catch(err => { /* Silent fail to avoid polluting the console */ });
        }

        // Check for new notifications immediately, then every 30 seconds
        setTimeout(globalPollNotifications, 1000); // 1-second delay lets the UI load first
        setInterval(globalPollNotifications, 30000);
    </script>

</body>
</html>