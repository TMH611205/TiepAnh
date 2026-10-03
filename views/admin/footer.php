        </main>
    </div>

    <script>
        (function () {
            var button = document.getElementById('adminMenuButton');
            var sidebar = document.getElementById('adminSidebar');
            var backdrop = document.getElementById('adminBackdrop');

            function setOpen(open) {
                sidebar.classList.toggle('open', open);
                backdrop.classList.toggle('open', open);
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            button.addEventListener('click', function () {
                setOpen(!sidebar.classList.contains('open'));
            });
            backdrop.addEventListener('click', function () {
                setOpen(false);
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setOpen(false);
                }
            });

            // Xác nhận các thao tác nguy hiểm: <form data-confirm="...">
            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!window.confirm(form.dataset.confirm)) {
                        event.preventDefault();
                    }
                });
            });
        })();
    </script>
    <script src="<?= asset('js/reveal.js') ?>"></script>
</body>

</html>
