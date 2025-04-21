</main>
    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-center md:text-left">© <?php echo date('Y'); ?> Evaluation System. All rights reserved.</p>
                <div class="mt-4 md:mt-0 flex space-x-4">
                    <a href="index.php" class="hover:text-gray-300">
                        <i class="fas fa-book"></i> Documentation
                    </a>
                    <a href="#" class="hover:text-gray-300" onclick="showHelpPopup(event)">
                        <i class="fas fa-question-circle"></i> Help
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <div id="helpModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold mb-4">Need Help?</h2>
                <p>Contact your administrator for assistance with any issues or questions you may have.</p>
                <button onclick="closeHelpPopup()" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">Close</button>
            </div>
        </div>
    </div>

    <script>
    // Common JavaScript functions
    function confirmDelete(event) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    });

    function showHelpPopup(event) {
        event.preventDefault();
        document.getElementById('helpModal').classList.remove('hidden');
    }

    function closeHelpPopup() {
        document.getElementById('helpModal').classList.add('hidden');
    }
    </script>
</body>
</html>
