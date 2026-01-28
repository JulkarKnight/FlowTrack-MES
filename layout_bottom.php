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
            color: var(--warning-orange);
            margin-bottom: 15px;
        }
        .modal-box h3 { margin: 0 0 10px 0; color: var(--text-primary); }
        .modal-box p { margin: 0 0 25px 0; color: var(--text-secondary); font-size: 14px; }

        /* Buttons */
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        
        .btn-cancel {
            background: rgba(0,0,0,0.05);
            color: var(--text-primary);
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-confirm {
            background: var(--danger-red);
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
        let pendingForm = null;

        // 1. Entry Animations for Cards
        document.addEventListener("DOMContentLoaded", function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + "s";
            });
        });

        // 2. Global Function to Open Modal
        function confirmAction(event, message, isDanger = true) {
            event.preventDefault(); // Stop the form immediately
            
            pendingForm = event.target.closest('form'); // Remember which form was clicked
            
            // Update UI text
            document.getElementById('modal-desc').innerText = message;
            
            // Show Modal
            document.getElementById('glass-modal').classList.add('active');
        }

        // 3. Handle "Yes" Click
        document.getElementById('btn-confirm').addEventListener('click', function() {
            if(pendingForm) {
                pendingForm.submit(); // Actually submit the form now
            }
            closeModal();
        });

        // 4. Close Modal
        function closeModal() {
            document.getElementById('glass-modal').classList.remove('active');
            pendingForm = null;
        }
    </script>

</body>
</html>