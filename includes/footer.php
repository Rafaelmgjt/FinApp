</div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?> v<?php echo SYSTEM_VERSION; ?> - Todos os direitos reservados</p>
            <p style="font-size: 0.9rem; margin-top: 5px;">Desenvolvido por Rafael Miranda Gomes, com amor <i class="fas fa-heart" style="color: #dc3545;"></i> em PHP</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Função para confirmar exclusão
        function confirmarDelecao(id, tipo = 'registro') {
            return confirm('Tem certeza que deseja deletar este movimentação?');
        
        }
        
        // Função para formatar moeda
        function formatarMoeda(valor) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(valor);
        }
        
        // Função para validar email
        function validarEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Ocultar alerta após 5 segundos
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Máscara para valores
            $('.input-valor').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                let floatValue = (value / 100).toFixed(2);
                $(this).val(floatValue);
            });
        });
        
        // Função para export para Excel
        function exportarParaExcel(titulo) {
            let html = document.documentElement.innerHTML;
            let url = 'data:application/vnd.ms-excel;charset=UTF-8,' + encodeURIComponent(html);
            window.location.href = url;
        }
        
        // Função para print
        function imprimirPagina() {
            window.print();
        }

        // Theme toggle
        function updateThemeButton() {
            const btn = document.getElementById('themeToggleBtn');
            if (!btn) return;
            const isDark = document.body.classList.contains('dark-theme');
            btn.innerHTML = isDark ? '<i class="fas fa-sun"></i> Light' : '<i class="fas fa-moon"></i> Dark';
            btn.classList.toggle('btn-outline-light', !isDark);
            btn.classList.toggle('btn-outline-secondary', isDark);
        }

        function setTheme(theme) {
            document.body.classList.toggle('dark-theme', theme === 'dark');
            localStorage.setItem('theme', theme);
            updateThemeButton();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);

            const themeBtn = document.getElementById('themeToggleBtn');
            if (themeBtn) {
                themeBtn.addEventListener('click', function() {
                    const nextTheme = document.body.classList.contains('dark-theme') ? 'light' : 'dark';
                    setTheme(nextTheme);
                });
            }
        });
    </script>
</body>
</html>