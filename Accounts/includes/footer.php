            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="js/invoice_functions.js?v=<?= time() ?>"></script>
    <script src="js/receipt_functions.js?v=<?= time() ?>"></script>
    <script src="js/app.js?v=<?= time() ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const activePage = "<?= isset($current_page) ? $current_page : 'dashboard' ?>";
            if (typeof switchPage === 'function') {
                switchPage(activePage);
            }
        });
    </script>
</body>
</html>