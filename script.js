// Modal Cadastro
function abrirModal() {
    const modal = document.getElementById('modal-cadastro');
    if (modal) modal.classList.add('active');
}
function fecharModal() {
    const modal = document.getElementById('modal-cadastro');
    if (modal) modal.classList.remove('active');
}

// Modal Edição
function abrirModalEditar() {
    const modal = document.getElementById('modal-editar');
    if (modal) modal.classList.add('active');
}
function fecharModalEditar() {
    const modal = document.getElementById('modal-editar');
    if (modal) modal.classList.remove('active');
}

document.addEventListener("DOMContentLoaded", function() {
    
    // Fechar modais clicando no fundo
    ['modal-cadastro', 'modal-editar'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        }
    });

    // 1. Abrir/Fechar Menu de 3 Pontinhos
    document.addEventListener("click", function(e) {
        const btnDots = e.target.closest(".btn-dots");
        
        // Fecha todos os menus abertos
        document.querySelectorAll(".dropdown-menu.active").forEach(menu => {
            if (!btnDots || menu !== btnDots.nextElementSibling) {
                menu.classList.remove("active");
            }
        });

        // Se clicou em um botão de 3 pontinhos, abre/fecha o menu dele
        if (btnDots) {
            e.stopPropagation();
            const menu = btnDots.nextElementSibling;
            menu.classList.toggle("active");
        }
    });

    // 2. Preencher e Abrir Modal de Edição
    document.querySelectorAll(".btn-editar").forEach(btn => {
        btn.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            const nome = this.getAttribute("data-nome");
            const valor = this.getAttribute("data-valor");
            const vencimento = this.getAttribute("data-vencimento");

            document.getElementById("edit-id").value = id;
            document.getElementById("edit-nome").value = nome;
            document.getElementById("edit-valor").value = valor;
            document.getElementById("edit-vencimento").value = vencimento;

            abrirModalEditar();
        });
    });

    // 3. Salvar Edição via AJAX
    const formEditar = document.getElementById("form-editar");
    if (formEditar) {
        formEditar.addEventListener("submit", function(e) {
            e.preventDefault();

            const id = document.getElementById("edit-id").value;
            const nome = document.getElementById("edit-nome").value;
            const valor = document.getElementById("edit-valor").value;
            const dia_vencimento = document.getElementById("edit-vencimento").value;

            fetch("editar_gasto.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${encodeURIComponent(id)}&nome=${encodeURIComponent(nome)}&valor=${encodeURIComponent(valor)}&dia_vencimento=${encodeURIComponent(dia_vencimento)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert("Erro ao editar conta: " + (data.error || "Erro desconhecido"));
                }
            })
            .catch(err => {
                console.error("Erro na requisição:", err);
                alert("Erro ao se comunicar com o servidor.");
            });
        });
    }

    // 4. Atualizar Status (Pago / Pendente)
    document.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
        checkbox.addEventListener("change", function() {
            const id = this.getAttribute("data-id");
            const status = this.checked ? "Pago" : "Pendente";
            const card = this.closest('.gasto-card');

            fetch("atualizar_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (status === "Pago") {
                        card.classList.add("item-pago");
                    } else {
                        card.classList.remove("item-pago");
                    }
                    setTimeout(() => location.reload(), 300);
                } else {
                    alert("Erro ao atualizar status: " + (data.error || "Erro desconhecido"));
                    this.checked = !this.checked;
                }
            })
            .catch(err => {
                console.error("Erro:", err);
                this.checked = !this.checked;
            });
        });
    });

    // 5. Excluir Gasto
    document.querySelectorAll(".btn-excluir").forEach(botao => {
        botao.addEventListener("click", function(e) {
            e.preventDefault();
            const id = this.getAttribute("data-id");
            
            if (confirm("Tem certeza que deseja excluir esta conta?")) {
                fetch("excluir_gasto.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${encodeURIComponent(id)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const card = this.closest('.gasto-card');
                        if (card) {
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.9)';
                        }
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    } else {
                        alert("Erro ao excluir: " + (data.error || "Erro desconhecido"));
                    }
                })
                .catch(err => {
                    console.error("Erro:", err);
                    alert("Erro ao se comunicar com o servidor.");
                });
            }
        });
    });
});